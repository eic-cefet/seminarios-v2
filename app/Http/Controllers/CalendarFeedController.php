<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Services\IcsGenerationService;
use Symfony\Component\HttpFoundation\Response;

class CalendarFeedController extends Controller
{
    public function publicFeed(IcsGenerationService $icsGenerationService): Response
    {
        $seminars = Seminar::query()
            ->with('seminarLocation')
            ->active()
            ->where('scheduled_at', '>=', now()->subDays(30))
            ->orderBy('scheduled_at')
            ->get();

        $content = $icsGenerationService->generateForSeminars($seminars, 'Seminários EIC');

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="seminarios-eic.ics"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
