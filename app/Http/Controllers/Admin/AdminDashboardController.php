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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $upcomingSeminars = Seminar::with(['seminarType', 'seminarLocation'])
            ->where('scheduled_at', '>=', now())
            ->where('active', true)
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $latestRatings = Rating::with(['seminar:id,name,slug', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $nearCapacitySeminars = Seminar::with(['seminarLocation'])
            ->withCount('registrations')
            ->whereHas('seminarLocation', function ($q) {
                $q->whereNotNull('max_vacancies');
            })
            ->where('scheduled_at', '>=', now())
            ->where('active', true)
            ->get()
            ->filter(function ($seminar) {
                $maxVacancies = $seminar->seminarLocation->max_vacancies;

                return $maxVacancies > 0 && $seminar->registrations_count >= ($maxVacancies * 0.8);
            })
            ->take(5)
            ->values();

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
                'counts' => [
                    'users' => User::count(),
                    'seminars' => Seminar::count(),
                    'registrations' => Registration::count(),
                    'subjects' => Subject::count(),
                ],
            ],
        ]);
    }

    public function seminars(Request $request): AnonymousResourceCollection
    {
        $seminars = Seminar::query()
            ->select(['id', 'name', 'slug', 'scheduled_at'])
            ->orderByDesc('scheduled_at')
            ->get();

        return AdminSeminarResource::collection($seminars);
    }
}
