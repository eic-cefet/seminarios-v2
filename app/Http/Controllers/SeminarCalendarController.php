<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Services\IcsGenerationService;
use Symfony\Component\HttpFoundation\Response;

class SeminarCalendarController extends Controller
{
    public function __invoke(string $slug, IcsGenerationService $icsGenerationService): Response
    {
        $seminar = Seminar::query()
            ->with('seminarLocation')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $content = $icsGenerationService->generateForSeminar($seminar);
        $filename = 'seminario-'.$seminar->slug.'.ics';

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
