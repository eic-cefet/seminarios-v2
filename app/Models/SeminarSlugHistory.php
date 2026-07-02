<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A slug the seminar used in the past. Kept so previously delivered
 * links (emails, ICS URLs, persisted notification action_urls, external
 * API references) keep resolving after a rename. The current slug always
 * wins over history on lookup.
 */
class SeminarSlugHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'seminar_id',
        'slug',
    ];

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public static function seminarIdFor(string $slug): ?int
    {
        $seminarId = self::query()->where('slug', $slug)->value('seminar_id');

        return $seminarId === null ? null : (int) $seminarId;
    }
}
