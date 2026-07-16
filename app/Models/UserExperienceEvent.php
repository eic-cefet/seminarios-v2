<?php

namespace App\Models;

use App\Enums\ExperienceReason;
use App\Models\Concerns\Auditable;
use Database\Factories\UserExperienceEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExperienceEvent extends Model
{
    /** @use HasFactory<UserExperienceEventFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'reason',
        'source_key',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ExperienceReason::class,
            'points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
