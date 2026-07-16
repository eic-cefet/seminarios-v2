<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminStudentGamificationController extends Controller
{
    public function __invoke(User $student, GamificationService $gamification): JsonResponse
    {
        abort_unless($student->isUser(), 404);

        Gate::authorize('viewGamification', $student);

        return response()->json([
            'data' => $gamification->profileFor($student),
        ]);
    }
}
