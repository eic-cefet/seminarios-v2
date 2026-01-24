<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Workshop $workshop): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Workshop $workshop): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Workshop $workshop): bool
    {
        return $user->hasRole('admin');
    }
}
