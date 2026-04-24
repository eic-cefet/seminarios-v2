<?php

namespace App\Models;

use App\Enums\ConsentType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'anonymous_id',
        'type',
        'granted',
        'version',
        'ip_address',
        'user_agent',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConsentType::class,
            'granted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
