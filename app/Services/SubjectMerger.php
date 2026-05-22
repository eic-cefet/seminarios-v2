<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class SubjectMerger
{
    public function __construct(
        private readonly SlugService $slugService,
    ) {}

    /**
     * Merge the given source subjects into the target.
     *
     * Rewires `seminar_subject` pivot rows onto the target (deduping to avoid
     * violating the pivot's unique constraint), then soft-deletes each source
     * via per-model events so the Auditable trait fires `subject.soft_deleted`
     * audit rows. Optionally renames the target.
     *
     * @param  array<int>  $sourceIds
     */
    public function merge(Subject $target, array $sourceIds, ?string $newName = null): Subject
    {
        return DB::transaction(function () use ($target, $sourceIds, $newName) {
            if ($newName !== null && $newName !== '') {
                $target->name = $newName;
                $target->slug = $this->slugService->generateUnique(
                    $newName, Subject::class, 'slug', $target->id
                );
                $target->save();
            }

            if (! empty($sourceIds)) {
                $seminarIds = DB::table('seminar_subject')
                    ->whereIn('subject_id', $sourceIds)
                    ->pluck('seminar_id')
                    ->unique();

                $insertData = $seminarIds->map(fn ($seminarId) => [
                    'seminar_id' => $seminarId,
                    'subject_id' => $target->id,
                ])->toArray();

                if (! empty($insertData)) {
                    DB::table('seminar_subject')->insertOrIgnore($insertData);
                }

                DB::table('seminar_subject')
                    ->whereIn('subject_id', $sourceIds)
                    ->delete();

                // Use ->each so per-model events (Auditable trait) fire on soft-delete.
                Subject::whereIn('id', $sourceIds)->get()->each->delete();
            }

            return $target->refresh();
        });
    }

    /**
     * Return the distinct seminar ids attached to any of the given subject ids.
     *
     * Useful for callers that need to record audit metadata about which
     * seminars were rewired during a merge.
     *
     * @param  array<int>  $subjectIds
     * @return array<int>
     */
    public function affectedSeminarIds(array $subjectIds): array
    {
        if (empty($subjectIds)) {
            return [];
        }

        return DB::table('seminar_subject')
            ->whereIn('subject_id', $subjectIds)
            ->pluck('seminar_id')
            ->unique()
            ->values()
            ->all();
    }
}
