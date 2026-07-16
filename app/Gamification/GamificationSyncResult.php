<?php

namespace App\Gamification;

final readonly class GamificationSyncResult
{
    /**
     * @param  array{level: int, rank: string, current_level_xp: int, next_level_xp: int, progress_percent: int}  $level
     * @param  array<int, array<string, mixed>>  $newBadges
     */
    public function __construct(
        public int $xpEarned,
        public int $totalXp,
        public array $level,
        public array $newBadges,
    ) {}
}
