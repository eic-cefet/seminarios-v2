<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SeminarVisibilityService
{
    /** Admins see all seminars; teachers see only their own. */
    public function visibleSeminars(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    /** Same scoping projected through the registrations -> seminar relation. */
    public function visibleRegistrations(Builder $registrationQuery, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $registrationQuery;
        }

        return $registrationQuery->whereHas('seminar', function (Builder $q) use ($user) {
            $q->where('created_by', $user->id);
        });
    }
}
