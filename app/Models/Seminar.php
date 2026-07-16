<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seminar extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    public const ALLOWED_DURATIONS = [30, 60, 120, 240];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'seminar_location_id',
        'workshop_id',
        'seminar_type_id',
        'scheduled_at',
        'duration_minutes',
        'room_link',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'active' => 'boolean',
        ];
    }

    protected function scheduledAt(): Attribute
    {
        return Attribute::set(
            fn (mixed $value): mixed => empty($value)
                ? $value
                : $this->asDateTime($value)
                    ->setTimezone(config('app.timezone'))
                    ->format($this->getDateFormat()),
        );
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

    public function alertDispatches(): HasMany
    {
        return $this->hasMany(SeminarAlertDispatch::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>=', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<', now());
    }

    /**
     * Slug-bound routes ({seminar:slug} in routes/external.php) keep
     * resolving after a rename: when no live seminar owns the slug, fall
     * back to slug history. The current slug always wins; trashed
     * seminars stay unresolvable because the relation applies the
     * SoftDeletes global scope.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $resolved = parent::resolveRouteBinding($value, $field);

        if ($resolved !== null || ($field ?? $this->getRouteKeyName()) !== 'slug') {
            return $resolved;
        }

        return SeminarSlugHistory::query()
            ->where('slug', $value)
            ->first()
            ?->seminar;
    }
}
