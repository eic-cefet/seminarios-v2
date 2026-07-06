<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Models\User;
use App\Services\IcsGenerationService;
use Symfony\Component\HttpFoundation\Response;

class CalendarFeedController extends Controller
{
    public function publicFeed(IcsGenerationService $icsGenerationService): Response
    {
        $seminars = Seminar::query()
            ->with(['seminarLocation', 'seminarType'])
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

    public function personalFeed(string $token, IcsGenerationService $icsGenerationService): Response
    {
        $user = User::query()
            ->where('calendar_feed_token', $token)
            ->firstOrFail();

        $seminars = Seminar::query()
            ->with(['seminarLocation', 'seminarType'])
            ->active()
            ->whereHas('registrations', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('scheduled_at')
            ->get();

        $content = $icsGenerationService->generateForSeminars($seminars, 'Seminários EIC — Minha agenda');

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="minha-agenda.ics"',
            'Cache-Control' => 'private',
        ]);
    }
}
