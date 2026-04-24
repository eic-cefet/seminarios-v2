<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\AlertPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seminarTypes(): BelongsToMany
    {
        return $this->belongsToMany(SeminarType::class, 'alert_preference_seminar_type');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'alert_preference_subject');
    }
}
