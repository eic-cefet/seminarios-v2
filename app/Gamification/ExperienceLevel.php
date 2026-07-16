<?php

namespace App\Gamification;

final class ExperienceLevel
{
    /**
     * @return array{level: int, rank: string, current_level_xp: int, next_level_xp: int, progress_percent: int}
     */
    public function fromXp(int $xp): array
    {
        $xp = max(0, $xp);
        $level = 1;

        while ($xp >= $this->thresholdFor($level + 1)) {
            $level++;
        }

        $currentLevelXp = $this->thresholdFor($level);
        $nextLevelXp = $this->thresholdFor($level + 1);
        $progressPercent = intdiv(
            ($xp - $currentLevelXp) * 100,
            $nextLevelXp - $currentLevelXp,
        );

        return [
            'level' => $level,
            'rank' => $this->rankFor($level),
            'current_level_xp' => $currentLevelXp,
            'next_level_xp' => $nextLevelXp,
            'progress_percent' => max(0, min(100, $progressPercent)),
        ];
    }

    private function thresholdFor(int $level): int
    {
        return intdiv(100 * ($level - 1) * $level, 2);
    }

    private function rankFor(int $level): string
    {
        /** @var array<int, string> $ranks */
        $ranks = config('gamification.ranks');
        $rank = $ranks[1];

        foreach ($ranks as $minimumLevel => $candidate) {
            if ($minimumLevel <= $level) {
                $rank = $candidate;
            }
        }

        return $rank;
    }
}
