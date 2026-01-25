<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seminar extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'seminar_location_id',
        'workshop_id',
        'seminar_type_id',
        'scheduled_at',
        'room_link',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seminarLocation(): BelongsTo
    {
        return $this->belongsTo(SeminarLocation::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function seminarType(): BelongsTo
    {
        return $this->belongsTo(SeminarType::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seminar_speaker');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'seminar_subject');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function presenceLink(): HasOne
    {
        return $this->hasOne(PresenceLink::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Scope a query to only include active seminars.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include upcoming seminars.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>=', now());
    }

    /**
     * Scope a query to only include expired seminars.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<', now());
    }
}
