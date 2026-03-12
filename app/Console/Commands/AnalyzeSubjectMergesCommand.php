<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Subject;
use App\Services\AiService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyzeSubjectMergesCommand extends Command
{
    use TracksAuditContext;

    protected $signature = 'subjects:ai-merge {--dry-run : Only show suggestions without merging}';

    protected $description = 'Use AI to identify and merge duplicate subjects';

    public function handle(): int
    {
        $this->setAuditContext();

        $ai = app(AiService::class);

        if (! $ai) {
            $this->error('AI service is not configured. Set AI_API_KEY in your environment.');

            return self::FAILURE;
        }

        $subjects = Subject::withCount('seminars')->orderBy('name')->get();

        if ($subjects->count() < 2) {
            $this->info('Not enough subjects to analyze.');

            return self::SUCCESS;
        }

        $this->info("Analyzing {$subjects->count()} subjects for potential merges...");

        $groups = $this->findMergeGroups($ai, $subjects);

        if (empty($groups)) {
            $this->info('No merge suggestions found.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($groups).' merge group(s).');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $mergedCount = 0;

        foreach ($groups as $i => $group) {
            $merged = $this->processGroup($ai, $subjects, $group, $i + 1, $isDryRun);

            if ($merged) {
                $mergedCount++;
            }
        }

        $this->newLine();

        if ($isDryRun) {
            $this->info('Dry run complete. No changes were made.');
        } else {
            $this->info("Done. Merged {$mergedCount} group(s).");
        }

        return self::SUCCESS;
    }

    private function findMergeGroups(AiService $ai, Collection $subjects): array
    {
        $subjectList = $subjects->map(fn (Subject $s) => [
            'id' => $s->id,
            'name' => $s->name,
        ])->values()->toArray();

        $systemPrompt = 'You are a data deduplication assistant for academic seminar topics. '
            .'Analyze the list of topics and identify groups that refer to the same or very similar concept and should be merged. '
            .'Consider spelling variations, abbreviations, language differences (Portuguese/English), and semantic similarity. '
            .'Return a JSON array of groups, where each group is an array of topic IDs that should be merged. '
            .'Only include groups with 2 or more topics. If no merges are needed, return an empty array []. '
            .'Return ONLY valid JSON, no explanations or markdown.';

        $response = $ai->chat($systemPrompt, json_encode($subjectList), 4096);

        $parsed = json_decode($response, true);

        if (! is_array($parsed)) {
            $this->warn('AI returned invalid JSON. Skipping.');

            return [];
        }

        // Validate that all IDs exist in our subject list
        $validIds = $subjects->pluck('id')->toArray();

        return collect($parsed)
            ->map(fn ($group) => collect($group)
                ->filter(fn ($id) => in_array($id, $validIds))
                ->values()
                ->toArray()
            )
            ->filter(fn ($group) => count($group) >= 2)
            ->values()
            ->toArray();
    }

    private function processGroup(AiService $ai, Collection $subjects, array $groupIds, int $groupNumber, bool $isDryRun): bool
    {
        $groupSubjects = $subjects->whereIn('id', $groupIds);

        $this->info("Group {$groupNumber}:");
        foreach ($groupSubjects as $subject) {
            $this->line("  - [{$subject->id}] {$subject->name} ({$subject->seminars_count} seminars)");
        }

        $names = $groupSubjects->pluck('name')->toArray();
        $suggestedName = $this->suggestMergeName($ai, $names);

        if (! $suggestedName) {
            $this->warn('Could not get a name suggestion. Skipping group.');
            $this->newLine();

            return false;
        }

        $this->info("  Suggested name: {$suggestedName}");

        if ($isDryRun) {
            $this->newLine();

            return false;
        }

        if (! $this->confirm("Merge this group into \"{$suggestedName}\"?")) {
            $this->line('  Skipped.');
            $this->newLine();

            return false;
        }

        // Pick the subject with the most seminars as the target
        $target = $groupSubjects->sortByDesc('seminars_count')->first();
        $sourceIds = $groupSubjects->where('id', '!=', $target->id)->pluck('id')->toArray();

        $this->mergeSubjects($target, $sourceIds, $suggestedName);

        $this->info("  Merged into [{$target->id}] {$suggestedName}.");
        $this->newLine();

        return true;
    }

    private function suggestMergeName(AiService $ai, array $names): ?string
    {
        $systemPrompt = 'You are a naming assistant for academic seminar topics. '
            .'Given a list of topic names that are being merged, suggest a single concise topic name that best represents all of them. '
            .'Return ONLY the suggested name, nothing else.';

        try {
            return $ai->chat($systemPrompt, implode(', ', $names), 256);
        } catch (\RuntimeException) {
            return null;
        }
    }

    private function mergeSubjects(Subject $target, array $sourceIds, string $newName): void
    {
        DB::transaction(function () use ($target, $sourceIds, $newName) {
            $target->name = $newName;
            $target->save();

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

            Subject::whereIn('id', $sourceIds)->each(fn (Subject $s) => $s->delete());

            AuditLog::record(AuditEvent::AiSubjectsMerged, auditable: $target, eventData: [
                'target_id' => $target->id,
                'source_ids' => $sourceIds,
                'new_name' => $newName,
                'affected_seminar_ids' => $seminarIds->values()->toArray(),
            ]);
        });
    }
}
