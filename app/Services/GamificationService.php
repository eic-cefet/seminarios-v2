<?php

namespace App\Services;

use App\Enums\BadgeKey;
use App\Enums\ExperienceReason;
use App\Gamification\BadgeCatalog;
use App\Gamification\BadgeDefinition;
use App\Gamification\ExperienceLevel;
use App\Gamification\GamificationSyncResult;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserExperienceEvent;
use App\Notifications\BadgesUnlockedNotification;
use App\Support\Locking\LockKey;
use App\Support\Locking\Mutex;
use Illuminate\Support\Facades\DB;
use Throwable;

class GamificationService
{
    private const array CATEGORY_KEYS = [
        'Participação' => 'participacao',
        'Exploração' => 'exploracao',
        'Ritmo' => 'ritmo',
        'Constância' => 'constancia',
        'Workshops' => 'workshops',
        'Contribuição' => 'contribuicao',
    ];

    public function __construct(
        private readonly GamificationSnapshotBuilder $snapshotBuilder,
        private readonly BadgeCatalog $badgeCatalog,
        private readonly ExperienceLevel $experienceLevel,
    ) {}

    public function sync(User $user, bool $notify = true): GamificationSyncResult
    {
        $result = Mutex::for(LockKey::gamificationUser($user->id))->protect(
            fn (): GamificationSyncResult => DB::transaction(
                fn (): GamificationSyncResult => $this->reconcile($user),
            ),
        );

        if ($notify && $result->newBadges !== []) {
            try {
                $user->notify(new BadgesUnlockedNotification($result->newBadges));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $result;
    }

    /**
     * @return array{
     *     progress: array{total_xp: int, level: int, rank: string, current_level_xp: int, next_level_xp: int, progress_percent: int},
     *     summary: array{earned_badges: int, total_badges: int},
     *     categories: array<int, array{key: string, label: string, badges: array<int, array<string, mixed>>}>,
     *     recent_badges: array<int, array<string, mixed>>
     * }
     */
    public function profileFor(User $user): array
    {
        $definitions = $this->badgeCatalog->all();
        $earnedBadges = $user->badges()->get()->keyBy(
            fn (UserBadge $badge): string => $badge->badge_key->value,
        );
        $categories = [];

        foreach ($definitions as $definition) {
            $categories[$definition->category] ??= [
                'key' => self::CATEGORY_KEYS[$definition->category],
                'label' => $definition->category,
                'badges' => [],
            ];
            $categories[$definition->category]['badges'][] = $this->serializeBadge(
                $definition,
                $earnedBadges->get($definition->key->value),
            );
        }

        $recentBadges = $user->badges()
            ->orderByDesc('earned_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (UserBadge $badge): array => $this->serializeBadge(
                $definitions[$badge->badge_key->value],
                $badge,
            ))
            ->values()
            ->all();
        $totalXp = (int) $user->experienceEvents()->sum('points');

        return [
            'progress' => [
                'total_xp' => $totalXp,
                ...$this->experienceLevel->fromXp($totalXp),
            ],
            'summary' => [
                'earned_badges' => $earnedBadges->count(),
                'total_badges' => count($definitions),
            ],
            'categories' => array_values($categories),
            'recent_badges' => $recentBadges,
        ];
    }

    private function reconcile(User $user): GamificationSyncResult
    {
        $totalBefore = (int) $user->experienceEvents()->sum('points');
        $existingBadges = $user->badges()->get()->keyBy(
            fn (UserBadge $badge): string => $badge->badge_key->value,
        );
        $snapshot = $this->snapshotBuilder->for($user);
        $definitions = $this->badgeCatalog->all();
        $desiredBadgeKeys = $this->badgeCatalog->earnedBy($snapshot);
        $desiredSources = $snapshot->experienceSources();

        foreach ($desiredBadgeKeys as $badgeKey) {
            $definition = $definitions[$badgeKey->value];
            $desiredSources[] = [
                'reason' => ExperienceReason::BadgeBonus,
                'source_key' => "badge:{$badgeKey->value}",
                'points' => config("gamification.badge_tier_bonus.{$definition->tier}"),
            ];
        }

        $this->reconcileExperienceEvents($user, $desiredSources);
        $newBadges = $this->reconcileBadges(
            $user,
            $desiredBadgeKeys,
            $existingBadges->all(),
            $definitions,
        );
        $totalAfter = (int) $user->experienceEvents()->sum('points');

        return new GamificationSyncResult(
            xpEarned: max(0, $totalAfter - $totalBefore),
            totalXp: $totalAfter,
            level: $this->experienceLevel->fromXp($totalAfter),
            newBadges: $newBadges,
        );
    }

    /**
     * @param  array<int, array{reason: ExperienceReason, source_key: string, points: int}>  $desiredSources
     */
    private function reconcileExperienceEvents(User $user, array $desiredSources): void
    {
        $existingEvents = $user->experienceEvents()->get()->keyBy(
            fn (UserExperienceEvent $event): string => $this->sourceIdentity($event->reason, $event->source_key),
        );
        $desiredIdentities = [];

        foreach ($desiredSources as $source) {
            $identity = $this->sourceIdentity($source['reason'], $source['source_key']);
            $desiredIdentities[$identity] = true;
            $existing = $existingEvents->get($identity);

            if ($existing === null) {
                $user->experienceEvents()->create($source);

                continue;
            }

            if ($existing->points !== $source['points']) {
                $existing->update(['points' => $source['points']]);
            }
        }

        foreach ($existingEvents as $identity => $event) {
            if (! isset($desiredIdentities[$identity])) {
                $event->delete();
            }
        }
    }

    /**
     * @param  array<int, BadgeKey>  $desiredBadgeKeys
     * @param  array<string, UserBadge>  $existingBadges
     * @param  array<string, BadgeDefinition>  $definitions
     * @return array<int, array<string, mixed>>
     */
    private function reconcileBadges(
        User $user,
        array $desiredBadgeKeys,
        array $existingBadges,
        array $definitions,
    ): array {
        $desiredValues = [];
        $newBadges = [];

        foreach ($desiredBadgeKeys as $badgeKey) {
            $desiredValues[$badgeKey->value] = true;

            if (isset($existingBadges[$badgeKey->value])) {
                continue;
            }

            $badge = $user->badges()->create([
                'badge_key' => $badgeKey,
                'earned_at' => now(),
            ]);
            $newBadges[] = $this->serializeBadge($definitions[$badgeKey->value], $badge);
        }

        foreach ($existingBadges as $badgeKey => $badge) {
            if (! isset($desiredValues[$badgeKey])) {
                $badge->delete();
            }
        }

        return $newBadges;
    }

    private function sourceIdentity(ExperienceReason $reason, string $sourceKey): string
    {
        return "{$reason->value}|{$sourceKey}";
    }

    /**
     * @return array{key: string, name: string, description: string, category: string, tier: string, icon: string, earned: bool, earned_at: ?string}
     */
    private function serializeBadge(BadgeDefinition $definition, ?UserBadge $badge): array
    {
        return [
            'key' => $definition->key->value,
            'name' => $definition->name,
            'description' => $definition->description,
            'category' => $definition->category,
            'tier' => $definition->tier,
            'icon' => $definition->icon,
            'earned' => $badge !== null,
            'earned_at' => $badge?->earned_at?->toIso8601String(),
        ];
    }
}
