<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin, Role::Teacher]);
    }

    public function viewAnyExternal(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function viewStudentDashboard(User $user, User $model): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        if (! $user->hasRole(Role::Teacher)) {
            return false;
        }

        return $model->registrations()
            ->whereHas('seminar', fn ($query) => $query->where('created_by', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin) && $user->id !== $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin) && $user->id !== $model->id;
    }

    public function viewLgpdData(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function exportLgpdData(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function anonymizeUser(User $actor): bool
    {
        return $actor->hasRole(Role::Admin);
    }
}
