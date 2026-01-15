<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSpeakerData extends Model
{
    protected $table = 'user_speaker_data';

    protected $fillable = [
        'user_id',
        'slug',
        'institution',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
