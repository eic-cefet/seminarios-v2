<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSpeakerData extends Model
{
    use Auditable, HasFactory;

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
