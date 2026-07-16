<?php

namespace App\Gamification;

final readonly class GamificationSnapshot
{
    /**
     * @param  array<string, int>  $metrics
     */
    private function __construct(private array $metrics) {}

    /**
     * @param  array<string, int>  $metrics
     */
    public static function fromMetrics(array $metrics): self
    {
        $attendanceCount = $metrics['attendance_count'] ?? 0;
        $evaluationCount = $metrics['evaluation_count'] ?? 0;

        $metrics['feedback_champion'] = (int) (
            $attendanceCount >= 10
            && $evaluationCount * 100 >= $attendanceCount * 90
        );

        return new self($metrics);
    }

    public function metric(string $key): int
    {
        return $this->metrics[$key] ?? 0;
    }
}
