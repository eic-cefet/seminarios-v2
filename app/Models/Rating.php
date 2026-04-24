<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'seminar_id',
        'user_id',
        'score',
        'comment',
        'sentiment',
        'sentiment_analyzed_at',
        'ai_analysis_consent',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'sentiment_analyzed_at' => 'datetime',
            'ai_analysis_consent' => 'boolean',
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
