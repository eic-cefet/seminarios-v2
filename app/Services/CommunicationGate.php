<?php

namespace App\Services;

use App\Enums\CommunicationCategory;
use App\Models\User;

class CommunicationGate
{
    public function canEmail(User $user, CommunicationCategory $category): bool
    {
        return $user->wantsCommunication($category);
    }
}
