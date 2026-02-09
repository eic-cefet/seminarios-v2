<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function merge(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
