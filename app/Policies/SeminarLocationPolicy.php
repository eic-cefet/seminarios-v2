<?php

namespace App\Policies;

use App\Models\SeminarLocation;
use App\Models\User;

class SeminarLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, SeminarLocation $location): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, SeminarLocation $location): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, SeminarLocation $location): bool
    {
        return $user->hasRole('admin');
    }
}
