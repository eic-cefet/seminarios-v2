<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\AlertPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertPreference extends Model
{
    /** @use HasFactory<AlertPreferenceFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'opted_in',
        'seminar_type_ids',
        'subject_ids',
    ];

    protected function casts(): array
    {
        return [
            'opted_in' => 'boolean',
            'seminar_type_ids' => 'array',
            'subject_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
