<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PresenceLink extends Model
{
    use Auditable, HasFactory;

    protected $table = 'seminar_presence_links';

    protected $fillable = [
        'seminar_id',
        'uuid',
        'active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PresenceLink $link) {
            $link->uuid ??= Str::uuid()->toString();
        });
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->active && ! $this->isExpired();
    }

    /**
     * Schedule-aligned expiry for inactive links being resynced after a
     * seminar reschedule (see syncExpiryWithSchedule): scheduled_at + 4h,
     * with no now()+1h clamp — an inactive link is unusable until
     * activation, which recomputes the expiry via computeActivationExpiry.
     */
    public static function computeScheduledExpiry(?Carbon $scheduledAt): ?Carbon
    {
        return $scheduledAt?->copy()->addHours(4);
    }

    /**
     * Expiry applied whenever a link becomes (or stays) active:
     * the later of scheduled_at + 4h and now + 1h.
     */
    public static function computeActivationExpiry(?Carbon $scheduledAt): Carbon
    {
        $scheduledExpiry = $scheduledAt?->copy()->addHours(4);
        $minimumExpiry = now()->addHour();

        return $scheduledExpiry && $scheduledExpiry->gt($minimumExpiry)
            ? $scheduledExpiry
            : $minimumExpiry;
    }

    /**
     * Realign expires_at after the seminar's scheduled_at changed.
     * Deactivated links (expires_at already null) are left untouched;
     * activation recomputes their expiry anyway. The null-schedule guard
     * is defensive only: seminars.scheduled_at is a non-nullable column.
     */
    public function syncExpiryWithSchedule(): void
    {
        $this->loadMissing('seminar');

        if ($this->seminar->scheduled_at === null) {
            return; // @codeCoverageIgnore
        }

        if ($this->active) {
            $this->update(['expires_at' => self::computeActivationExpiry($this->seminar->scheduled_at)]);

            return;
        }

        if ($this->expires_at !== null) {
            $this->update(['expires_at' => self::computeScheduledExpiry($this->seminar->scheduled_at)]);
        }
    }
}
