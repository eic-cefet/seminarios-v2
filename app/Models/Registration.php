<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'seminar_id',
        'user_id',
        'present',
        'reminder_sent',
        'certificate_code',
        'certificate_sent',
        'evaluation_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'present' => 'boolean',
            'reminder_sent' => 'boolean',
            'certificate_sent' => 'boolean',
            'evaluation_sent_at' => 'datetime',
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
