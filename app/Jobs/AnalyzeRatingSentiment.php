<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\Rating;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeRatingSentiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Rating $rating
    ) {}

    public function handle(): void
    {
        $this->setAuditContext();

        if (empty($this->rating->comment)) {
            return;
        }

        if ($this->rating->sentiment_analyzed_at !== null) {
            return;
        }

        $ai = app(AiService::class);

        if (! $ai) {
            Log::error('AI service is not configured. Set AI_API_KEY in your environment.');

            return;
        }

        $systemPrompt = 'You are a sentiment analyzer for academic seminar reviews. Analyze the following review and provide a brief sentiment summary in Portuguese (Brazilian). Include: overall sentiment (positivo/negativo/neutro/misto), key points mentioned. Keep it under 100 words. Return ONLY the analysis.';

        $userMessage = "Nota: {$this->rating->score}/5\nComentário: {$this->rating->comment}";

        $result = $ai->chat($systemPrompt, $userMessage, 512);

        $this->rating->update([
            'sentiment' => $result,
            'sentiment_analyzed_at' => now(),
        ]);

        AuditLog::record(AuditEvent::SentimentAnalysisCompleted, AuditEventType::System, $this->rating, [
            'rating_id' => $this->rating->id,
        ]);
    }
}
