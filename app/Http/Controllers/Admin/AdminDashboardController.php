<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminRatingResource;
use App\Http\Resources\Admin\AdminRegistrationResource;
use App\Http\Resources\Admin\AdminSeminarResource;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    private const CACHE_KEY = 'admin:dashboard:stats';

    private const CACHE_TTL_SECONDS = 60;

    public function stats(Request $request): JsonResponse
    {
        $data = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildStatsPayload($request),
        );

        return response()->json(['data' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatsPayload(Request $request): array
    {
        $upcomingSeminars = Seminar::with(['seminarType', 'seminarLocation'])
            ->upcoming()
            ->active()
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $latestRatings = Rating::with(['seminar:id,name,slug', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $nearCapacitySeminars = Seminar::query()
            ->with(['seminarLocation'])
            ->join('seminar_locations', 'seminars.seminar_location_id', '=', 'seminar_locations.id')
            ->whereNotNull('seminar_locations.max_vacancies')
            ->where('seminar_locations.max_vacancies', '>', 0)
            ->upcoming()
            ->active()
            ->whereRaw('(select count(*) from registrations where registrations.seminar_id = seminars.id) >= (seminar_locations.max_vacancies * 0.8)')
            ->select('seminars.*')
            ->withCount('registrations')
            ->limit(5)
            ->get();

        $latestRegistrations = Registration::with(['user:id,name,email', 'seminar:id,name,slug'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'upcomingSeminars' => AdminSeminarResource::collection($upcomingSeminars)->toArray($request),
            'latestRatings' => AdminRatingResource::collection($latestRatings)->toArray($request),
            'nearCapacity' => AdminSeminarResource::collection($nearCapacitySeminars)->toArray($request),
            'latestRegistrations' => AdminRegistrationResource::collection($latestRegistrations)->toArray($request),
            'counts' => [
                'users' => User::count(),
                'seminars' => Seminar::count(),
                'registrations' => Registration::count(),
                'subjects' => Subject::count(),
            ],
        ];
    }
}
