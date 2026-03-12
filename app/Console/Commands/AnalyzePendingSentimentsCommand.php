<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use Illuminate\Console\Command;

class AnalyzePendingSentimentsCommand extends Command
{
    protected $signature = 'ratings:analyze-sentiments';

    protected $description = 'Dispatch sentiment analysis jobs for ratings with comments that have not been analyzed yet';

    public function handle(): int
    {
        $ratings = Rating::query()
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->whereNull('sentiment_analyzed_at')
            ->get();

        if ($ratings->isEmpty()) {
            $this->info('No pending ratings to analyze.');

            return self::SUCCESS;
        }

        $this->info("Dispatching {$ratings->count()} sentiment analysis job(s)...");

        foreach ($ratings as $rating) {
            AnalyzeRatingSentiment::dispatch($rating);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
