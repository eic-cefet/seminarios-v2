<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin, Role::Teacher]);
    }

    public function view(User $user, Registration $registration): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Teacher)
            && $registration->seminar->created_by === $user->id;
    }

    public function updatePresence(User $user, Registration $registration): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Teacher)
            && $registration->seminar->created_by === $user->id;
    }
}
