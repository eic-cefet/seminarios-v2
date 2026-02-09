<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function view(User $user, Registration $registration): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function updatePresence(User $user, Registration $registration): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
