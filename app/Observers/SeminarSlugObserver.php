<?php

namespace App\Observers;

use App\Models\Seminar;
use App\Models\SeminarSlugHistory;
use Illuminate\Support\Facades\DB;

/**
 * Records every slug a seminar stops using, so old links keep resolving.
 * When a slug becomes current again (a seminar reclaims it), the matching
 * history row is retired first — the live seminars table always wins.
 */
class SeminarSlugObserver
{
    public function updated(Seminar $seminar): void
    {
        if (! $seminar->wasChanged('slug')) {
            return;
        }

        $oldSlug = $seminar->getOriginal('slug');

        DB::transaction(function () use ($seminar, $oldSlug): void {
            SeminarSlugHistory::query()->where('slug', $seminar->slug)->delete();

            if ($oldSlug === null || $oldSlug === $seminar->slug) {
                return;
            }

            SeminarSlugHistory::query()->updateOrCreate(
                ['slug' => $oldSlug],
                ['seminar_id' => $seminar->id],
            );
        });
    }
}
