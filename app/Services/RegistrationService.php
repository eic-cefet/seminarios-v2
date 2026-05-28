<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Mail\SeminarRegistrationConfirmation;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Support\Locking\LockKey;
use App\Support\Locking\Mutex;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Mail;

class RegistrationService
{
    public function register(User $user, Seminar $seminar): Registration
    {
        logger()->info('REG_TRACE enter', ['seminar' => $seminar->id, 'user' => $user->id]);

        if ($seminar->scheduled_at->isPast()) {
            throw ApiException::seminarExpired();
        }

        logger()->info('REG_TRACE before mutex');

        $registration = Mutex::for(LockKey::seminarRegistration($seminar->id), ttlSeconds: 5, waitSeconds: 5)
            ->protect(function () use ($user, $seminar): Registration {
                logger()->info('REG_TRACE inside mutex');
                try {
                    return $seminar->registrations()->create([
                        'user_id' => $user->id,
                        'present' => false,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    throw ApiException::alreadyRegistered();
                }
            });

        logger()->info('REG_TRACE after mutex, before mail', ['registration' => $registration->id]);

        Mail::to($user)->queue(new SeminarRegistrationConfirmation($user, $seminar));

        logger()->info('REG_TRACE after mail, returning');

        return $registration;
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
