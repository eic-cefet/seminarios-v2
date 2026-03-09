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
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
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

        $nearCapacitySeminars = Seminar::with(['seminarLocation'])
            ->withCount('registrations')
            ->join('seminar_locations', 'seminars.seminar_location_id', '=', 'seminar_locations.id')
            ->whereNotNull('seminar_locations.max_vacancies')
            ->where('seminar_locations.max_vacancies', '>', 0)
            ->upcoming()
            ->active()
            ->havingRaw('registrations_count >= seminar_locations.max_vacancies * 0.8')
            ->select('seminars.*')
            ->limit(5)
            ->get();

        $latestRegistrations = Registration::with(['user:id,name,email', 'seminar:id,name,slug'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'upcomingSeminars' => AdminSeminarResource::collection($upcomingSeminars),
                'latestRatings' => AdminRatingResource::collection($latestRatings),
                'nearCapacity' => AdminSeminarResource::collection($nearCapacitySeminars),
                'latestRegistrations' => AdminRegistrationResource::collection($latestRegistrations),
                'counts' => DB::table(DB::raw('(SELECT 1) as dual'))
                    ->selectRaw('(SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) as users')
                    ->selectRaw('(SELECT COUNT(*) FROM seminars WHERE deleted_at IS NULL) as seminars')
                    ->selectRaw('(SELECT COUNT(*) FROM registrations) as registrations')
                    ->selectRaw('(SELECT COUNT(*) FROM subjects) as subjects')
                    ->first(),
            ],
        ]);
    }
}
