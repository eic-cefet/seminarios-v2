<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Value object representing the date boundaries of an academic semester
 * expressed as "YYYY.S" (e.g. "2026.1" for the first half of 2026).
 *
 * Semester 1 → Jan 1 00:00:00 .. Jun 30 23:59:59
 * Semester 2 → Jul 1 00:00:00 .. Dec 31 23:59:59
 */
final class SemesterRange
{
    public function __construct(
        public readonly int $year,
        public readonly int $half,
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    public static function fromString(string $semester): self
    {
        if (! preg_match('/^(\d{4})\.([12])$/', $semester, $m)) {
            throw new InvalidArgumentException("Invalid semester format: {$semester}");
        }

        [$year, $half] = [(int) $m[1], (int) $m[2]];

        if ($half === 1) {
            return new self(
                $year,
                $half,
                Carbon::parse("{$year}-01-01 00:00:00"),
                Carbon::parse("{$year}-06-30 23:59:59"),
            );
        }

        return new self(
            $year,
            $half,
            Carbon::parse("{$year}-07-01 00:00:00"),
            Carbon::parse("{$year}-12-31 23:59:59"),
        );
    }

    public function startString(): string
    {
        return $this->start->format('Y-m-d H:i:s');
    }

    public function endString(): string
    {
        return $this->end->format('Y-m-d H:i:s');
    }
}
