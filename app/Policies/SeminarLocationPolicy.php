<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\SeminarLocation;
use App\Models\User;

class SeminarLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function view(User $user, SeminarLocation $location): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function update(User $user, SeminarLocation $location): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function delete(User $user, SeminarLocation $location): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
