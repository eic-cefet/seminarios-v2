<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\AlertPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AlertPreference extends Model
{
    /** @use HasFactory<AlertPreferenceFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'new_seminar_alert',
        'seminar_reminder_7d',
        'seminar_reminder_24h',
        'evaluation_prompt',
        'announcements',
        'certificate_ready',
        'seminar_rescheduled',
        'workshop_announcements',
    ];

    protected function casts(): array
    {
        return [
            'new_seminar_alert' => 'boolean',
            'seminar_reminder_7d' => 'boolean',
            'seminar_reminder_24h' => 'boolean',
            'evaluation_prompt' => 'boolean',
            'announcements' => 'boolean',
            'certificate_ready' => 'boolean',
            'seminar_rescheduled' => 'boolean',
            'workshop_announcements' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seminarTypes(): MorphToMany
    {
        return $this->morphedByMany(
            SeminarType::class,
            'settable',
            'alert_preference_new_seminar_alert_settings',
            'alert_preference_id',
        );
    }

    public function subjects(): MorphToMany
    {
        return $this->morphedByMany(
            Subject::class,
            'settable',
            'alert_preference_new_seminar_alert_settings',
            'alert_preference_id',
        );
    }
}
