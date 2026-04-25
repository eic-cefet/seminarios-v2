<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Support\Locking\LockKey;
use App\Support\Locking\Mutex;
use Illuminate\Database\UniqueConstraintViolationException;

class RegistrationService
{
    public function register(User $user, Seminar $seminar): Registration
    {
        if ($seminar->scheduled_at->isPast()) {
            throw ApiException::seminarExpired();
        }

        return Mutex::for(LockKey::seminarRegistration($seminar->id), ttlSeconds: 5, waitSeconds: 5)
            ->protect(function () use ($user, $seminar): Registration {
                try {
                    return $seminar->registrations()->create([
                        'user_id' => $user->id,
                        'present' => false,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    throw ApiException::alreadyRegistered();
                }
            });
    }

    public function unregister(User $user, Seminar $seminar): void
    {
        if ($seminar->scheduled_at->isToday() || $seminar->scheduled_at->isPast()) {
            throw ApiException::unregisterBlocked();
        }

        $registration = $seminar->registrations()->where('user_id', $user->id)->first();

        if (! $registration) {
            throw ApiException::notRegistered();
        }

        $registration->delete();
    }
}
