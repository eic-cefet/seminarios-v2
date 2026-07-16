<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileGamificationController extends Controller
{
    public function __invoke(Request $request, GamificationService $gamification): JsonResponse
    {
        return response()->json([
            'data' => $gamification->profileFor($request->user()),
        ]);
    }
}
