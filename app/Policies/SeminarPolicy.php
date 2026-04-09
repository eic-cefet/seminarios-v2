<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Seminar;
use App\Models\User;

class SeminarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin, Role::Teacher]);
    }

    public function view(User $user, Seminar $seminar): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Teacher) && $seminar->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin, Role::Teacher]);
    }

    public function update(User $user, Seminar $seminar): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Teacher) && $seminar->created_by === $user->id;
    }

    public function delete(User $user, Seminar $seminar): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Teacher) && $seminar->created_by === $user->id;
    }

    public function restore(User $user, Seminar $seminar): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function forceDelete(User $user, Seminar $seminar): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
