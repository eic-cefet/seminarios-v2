<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Models\Subject;
use App\Models\Workshop;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $latestPublicContentUpdatedAt = $this->latestTimestamp([
            Seminar::query()->active()->max('updated_at'),
            Subject::query()
                ->whereHas('seminars', fn ($query) => $query->where('active', true))
                ->max('updated_at'),
            Workshop::query()
                ->whereHas('seminars', fn ($query) => $query->where('active', true))
                ->max('updated_at'),
        ]);

        $urls = [
            [
                'loc' => $this->absoluteUrl('/'),
                'lastmod' => $latestPublicContentUpdatedAt,
            ],
            [
                'loc' => $this->absoluteUrl('/apresentacoes'),
                'lastmod' => $latestPublicContentUpdatedAt,
            ],
            [
                'loc' => $this->absoluteUrl('/topicos'),
                'lastmod' => $latestPublicContentUpdatedAt,
            ],
            [
                'loc' => $this->absoluteUrl('/workshops'),
                'lastmod' => $latestPublicContentUpdatedAt,
            ],
            ...Seminar::query()
                ->active()
                ->orderBy('scheduled_at', 'desc')
                ->get(['slug', 'updated_at'])
                ->map(fn (Seminar $seminar) => [
                    'loc' => $this->absoluteUrl("/seminario/{$seminar->slug}"),
                    'lastmod' => $this->formatTimestamp($seminar->updated_at),
                ])
                ->all(),
            ...Subject::query()
                ->whereHas('seminars', fn ($query) => $query->where('active', true))
                ->orderBy('name')
                ->get(['id', 'slug', 'updated_at'])
                ->map(fn (Subject $subject) => [
                    'loc' => $this->absoluteUrl("/topico/{$subject->slug}"),
                    'lastmod' => $this->formatTimestamp($subject->updated_at),
                ])
                ->all(),
            ...Workshop::query()
                ->whereHas('seminars', fn ($query) => $query->where('active', true))
                ->orderBy('name')
                ->get(['id', 'slug', 'updated_at'])
                ->map(fn (Workshop $workshop) => [
                    'loc' => $this->absoluteUrl("/workshop/{$workshop->slug}"),
                    'lastmod' => $this->formatTimestamp($workshop->updated_at),
                ])
                ->all(),
        ];

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function absoluteUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        if ($path === '/') {
            return "{$baseUrl}/";
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * @param  array<int, CarbonInterface|string|null>  $timestamps
     */
    private function latestTimestamp(array $timestamps): ?string
    {
        $latestTimestamp = collect($timestamps)
            ->filter()
            ->map(fn (CarbonInterface|string $timestamp) => $this->asCarbon($timestamp))
            ->sortDesc()
            ->first();

        return $latestTimestamp?->toAtomString();
    }

    private function formatTimestamp(CarbonInterface|string|null $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        return $this->asCarbon($timestamp)->toAtomString();
    }

    private function asCarbon(CarbonInterface|string $timestamp): CarbonInterface
    {
        if ($timestamp instanceof CarbonInterface) {
            return $timestamp;
        }

        return Carbon::parse($timestamp);
    }
}
