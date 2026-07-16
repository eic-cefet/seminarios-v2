<?php

namespace App\Models;

use App\Enums\BadgeKey;
use App\Models\Concerns\Auditable;
use Database\Factories\UserBadgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    /** @use HasFactory<UserBadgeFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'badge_key',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'badge_key' => BadgeKey::class,
            'earned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
