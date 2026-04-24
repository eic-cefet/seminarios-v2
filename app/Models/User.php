<?php

namespace App\Models;

use App\Enums\CommunicationCategory;
use App\Enums\Role;
use App\Models\Concerns\Auditable;
use App\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPassword
{
    use Auditable, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected array $auditExclude = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'anonymization_requested_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    public function studentData(): HasOne
    {
        return $this->hasOne(UserStudentData::class);
    }

    public function speakerData(): HasOne
    {
        return $this->hasOne(UserSpeakerData::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function seminarsAsSpeaker(): BelongsToMany
    {
        return $this->belongsToMany(Seminar::class, 'seminar_speaker');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function socialIdentities(): HasMany
    {
        return $this->hasMany(SocialIdentity::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function alertPreference(): HasOne
    {
        return $this->hasOne(AlertPreference::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(MfaTrustedDevice::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(Role::Teacher);
    }

    public function isUser(): bool
    {
        return $this->hasRole(Role::User);
    }

    public function isAnonymizationPending(): bool
    {
        return $this->anonymization_requested_at !== null && $this->anonymized_at === null;
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function wantsCommunication(CommunicationCategory $category): bool
    {
        $pref = $this->alertPreference;

        if ($pref === null) {
            return $category->defaultWhenMissing();
        }

        return (bool) $pref->{$category->column()};
    }
}
