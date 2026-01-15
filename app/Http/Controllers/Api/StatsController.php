<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seminar;
use App\Models\Subject;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'subjects' => Subject::count(),
                'seminars' => Seminar::count(),
                'workshops' => Workshop::count(),
            ],
        ]);
    }
}
