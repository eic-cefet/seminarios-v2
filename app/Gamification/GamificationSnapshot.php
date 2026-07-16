<?php

namespace App\Gamification;

use App\Enums\ExperienceReason;

final readonly class GamificationSnapshot
{
    /**
     * @param  array<string, int>  $metrics
     * @param  array<array{reason: ExperienceReason, source_key: string, points: int}>  $experienceSources
     */
    private function __construct(
        private array $metrics,
        private array $experienceSources,
    ) {}

    /**
     * @param  array<string, int>  $metrics
     * @param  array<array{reason: ExperienceReason, source_key: string, points: int}>  $experienceSources
     */
    public static function fromMetrics(array $metrics, array $experienceSources = []): self
    {
        $attendanceCount = $metrics['attendance_count'] ?? 0;
        $evaluationCount = $metrics['evaluation_count'] ?? 0;

        $metrics['feedback_champion'] = (int) (
            $attendanceCount >= 10
            && $evaluationCount * 100 >= $attendanceCount * 90
        );

        return new self($metrics, $experienceSources);
    }

    public function metric(string $key): int
    {
        return $this->metrics[$key] ?? 0;
    }

    /**
     * @return array<array{reason: ExperienceReason, source_key: string, points: int}>
     */
    public function experienceSources(): array
    {
        return $this->experienceSources;
    }
}
