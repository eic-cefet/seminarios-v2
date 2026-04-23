<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarAlertDispatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'seminar_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }
}
