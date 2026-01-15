<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    protected $fillable = [
        'seminar_id',
        'user_id',
        'present',
        'reminder_sent',
        'certificate_code',
        'certificate_sent',
    ];

    protected function casts(): array
    {
        return [
            'present' => 'boolean',
            'reminder_sent' => 'boolean',
            'certificate_sent' => 'boolean',
        ];
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
