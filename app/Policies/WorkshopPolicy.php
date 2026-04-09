<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function view(User $user, Workshop $workshop): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function update(User $user, Workshop $workshop): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function delete(User $user, Workshop $workshop): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
