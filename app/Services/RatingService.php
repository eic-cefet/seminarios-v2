<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\Seminar;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class RatingService
{
    public const WINDOW_DAYS = 30;

    public function windowStart(): CarbonImmutable
    {
        return now()->subDays(self::WINDOW_DAYS)->startOfDay()->toImmutable();
    }

    public function isWithinWindow(Seminar $seminar): bool
    {
        return $seminar->scheduled_at !== null
            && $seminar->scheduled_at >= $this->windowStart()
            && $seminar->scheduled_at <= now();
    }

    public function pendingEvaluationsFor(User $user): Collection
    {
        $windowStart = $this->windowStart();

        return $user->registrations()
            ->where('present', true)
            ->whereHas('seminar', function ($query) use ($windowStart) {
                $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>=', $windowStart)
                    ->where('scheduled_at', '<=', now());
            })
            ->whereDoesntHave('seminar.ratings', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['seminar.seminarType', 'seminar.seminarLocation'])
            ->get();
    }

    public function averageScore(Seminar $seminar): ?float
    {
        $avg = Rating::query()->where('seminar_id', $seminar->id)->avg('score');

        return $avg === null ? null : (float) $avg;
    }
}
