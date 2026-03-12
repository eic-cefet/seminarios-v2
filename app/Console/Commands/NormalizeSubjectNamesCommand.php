<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Subject;
use App\Services\AiService;
use Illuminate\Console\Command;

class NormalizeSubjectNamesCommand extends Command
{
    use TracksAuditContext;

    protected $signature = 'subjects:ai-normalize {--dry-run : Only show suggestions without applying}';

    protected $description = 'Use AI to fix subject names (accents, letter case, spacing, typos)';

    public function handle(): int
    {
        $this->setAuditContext();

        $ai = app(AiService::class);

        if (! $ai) {
            $this->error('AI service is not configured. Set AI_API_KEY in your environment.');

            return self::FAILURE;
        }

        $subjects = Subject::orderBy('name')->get();

        if ($subjects->isEmpty()) {
            $this->info('No subjects found.');

            return self::SUCCESS;
        }

        $this->info("Analyzing {$subjects->count()} subject name(s)...");

        $fixes = $this->findFixes($ai, $subjects);

        if (empty($fixes)) {
            $this->info('No fixes needed.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($fixes).' name(s) to fix.');
        $this->newLine();

        $isDryRun = $this->option('dry-run');

        $this->table(
            ['ID', 'Current Name', 'Suggested Fix'],
            collect($fixes)->map(fn ($fix) => [$fix['id'], $fix['old'], $fix['new']]),
        );

        $this->newLine();

        if ($isDryRun) {
            $this->info('Dry run complete. No changes were made.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Apply all fixes?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $applied = 0;

        foreach ($fixes as $fix) {
            $subject = $subjects->firstWhere('id', $fix['id']);

            if (! $subject) {
                continue;
            }

            $oldName = $subject->name;
            $subject->update(['name' => $fix['new']]);
            $applied++;

            AuditLog::record(AuditEvent::AiSubjectsNormalized, auditable: $subject, eventData: [
                'old_name' => $oldName,
                'new_name' => $fix['new'],
            ]);
        }

        $this->info("Done. Applied {$applied} fix(es).");

        return self::SUCCESS;
    }

    private function findFixes(AiService $ai, $subjects): array
    {
        $subjectList = $subjects->map(fn (Subject $s) => [
            'id' => $s->id,
            'name' => $s->name,
        ])->values()->toArray();

        $systemPrompt = 'You are a text normalization assistant for academic seminar topic names in Brazilian Portuguese. '
            .'Analyze each topic name and fix issues like: missing or wrong accents (e.g. "Inteligencia" → "Inteligência"), '
            .'incorrect letter case (e.g. "machine learning" → "Machine Learning"), '
            .'missing or extra spaces (e.g. "MachineLearning" → "Machine Learning"), '
            .'and obvious typos. '
            .'Return a JSON array of objects with "id" and "name" (the corrected name) for ONLY the names that need fixing. '
            .'If no names need fixing, return an empty array []. '
            .'Return ONLY valid JSON, no explanations or markdown.';

        try {
            $response = $ai->chat($systemPrompt, json_encode($subjectList), 4096);
        } catch (\RuntimeException) {
            $this->warn('AI request failed.');

            return [];
        }

        $parsed = json_decode($response, true);

        if (! is_array($parsed)) {
            $this->warn('AI returned invalid JSON. Skipping.');

            return [];
        }

        $validIds = $subjects->pluck('id')->toArray();

        return collect($parsed)
            ->filter(fn ($item) => is_array($item)
                && isset($item['id'], $item['name'])
                && in_array($item['id'], $validIds)
                && $subjects->firstWhere('id', $item['id'])?->name !== $item['name']
            )
            ->map(fn ($item) => [
                'id' => $item['id'],
                'old' => $subjects->firstWhere('id', $item['id'])->name,
                'new' => $item['name'],
            ])
            ->values()
            ->toArray();
    }
}
