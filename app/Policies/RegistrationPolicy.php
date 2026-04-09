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
        return $user->hasRole(Role::Admin) || $this->isOwnerOfSeminar($user, $registration);
    }

    public function updatePresence(User $user, Registration $registration): bool
    {
        return $user->hasRole(Role::Admin) || $this->isOwnerOfSeminar($user, $registration);
    }

    private function isOwnerOfSeminar(User $user, Registration $registration): bool
    {
        $registration->loadMissing('seminar');

        return $user->hasRole(Role::Teacher)
            && $registration->seminar?->created_by === $user->id;
    }
}
