<?php

namespace App\Console\Commands;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BackfillGamificationCommand extends Command
{
    private const CHUNK_SIZE = 100;

    protected $signature = 'gamification:backfill {--user= : Reconcile only one user ID}';

    protected $description = 'Reconcile lifetime gamification progress from historical participation';

    public function handle(GamificationService $gamification): int
    {
        $userOptionProvided = $this->input->hasParameterOption('--user');
        $requestedUserId = $this->option('user');
        $processedUsers = 0;
        $xpEarned = 0;
        $newBadges = 0;

        if ($userOptionProvided && (! is_string($requestedUserId) || preg_match('/\A[1-9][0-9]*\z/D', $requestedUserId) !== 1)) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        if ($userOptionProvided) {
            $user = User::query()->find($requestedUserId);

            if ($user === null) {
                $this->error('Usuário não encontrado.');

                return self::FAILURE;
            }

            $progressBar = $this->output->createProgressBar(1);
            $progressBar->start();
            $this->reconcileUser($gamification, $user, $processedUsers, $xpEarned, $newBadges);
            $progressBar->advance();
            $progressBar->finish();
            $this->newLine();
        } else {
            $query = User::query()
                ->where(function (Builder $query): void {
                    $query->whereHas('registrations', fn (Builder $registrations): Builder => $registrations->where('present', true))
                        ->orWhereHas('ratings')
                        ->orWhereHas('badges')
                        ->orWhereHas('experienceEvents');
                })
                ->orderBy('id');
            $progressBar = $this->output->createProgressBar((clone $query)->count());
            $progressBar->start();

            $query->chunkById(self::CHUNK_SIZE, function (Collection $users) use (
                $gamification,
                $progressBar,
                &$processedUsers,
                &$xpEarned,
                &$newBadges,
            ): void {
                foreach ($users as $user) {
                    $this->reconcileUser($gamification, $user, $processedUsers, $xpEarned, $newBadges);
                    $progressBar->advance();
                }
            });

            $progressBar->finish();
            $this->newLine();
        }

        AuditLog::record(AuditEvent::GamificationBackfilled, AuditEventType::System, eventData: [
            'processed_users' => $processedUsers,
            'xp_earned' => $xpEarned,
            'new_badges' => $newBadges,
            'requested_user_id' => $userOptionProvided ? (int) $requestedUserId : null,
        ]);

        $this->info("Gamificação reconciliada para {$processedUsers} usuário(s).");

        return self::SUCCESS;
    }

    private function reconcileUser(
        GamificationService $gamification,
        User $user,
        int &$processedUsers,
        int &$xpEarned,
        int &$newBadges,
    ): void {
        $result = $gamification->sync($user, notify: false);
        $processedUsers++;
        $xpEarned += $result->xpEarned;
        $newBadges += count($result->newBadges);
    }
}
