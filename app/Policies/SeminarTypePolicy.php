<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\SeminarType;
use App\Models\User;

class SeminarTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function view(User $user, SeminarType $seminarType): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function update(User $user, SeminarType $seminarType): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function delete(User $user, SeminarType $seminarType): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
