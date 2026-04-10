<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function export(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
