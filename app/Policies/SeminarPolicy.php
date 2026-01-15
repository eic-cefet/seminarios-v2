<?php

namespace App\Policies;

use App\Models\Seminar;
use App\Models\User;

class SeminarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'teacher']);
    }

    public function view(User $user, Seminar $seminar): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('teacher') && $seminar->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'teacher']);
    }

    public function update(User $user, Seminar $seminar): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('teacher') && $seminar->created_by === $user->id;
    }

    public function delete(User $user, Seminar $seminar): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('teacher') && $seminar->created_by === $user->id;
    }

    public function restore(User $user, Seminar $seminar): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Seminar $seminar): bool
    {
        return $user->hasRole('admin');
    }
}
