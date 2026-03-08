<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'seminar_id',
        'user_id',
        'score',
        'comment',
        'sentiment',
        'sentiment_analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'sentiment_analyzed_at' => 'datetime',
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
