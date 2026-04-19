<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class VersionController extends Controller
{
    public const CACHE_KEY = 'app:version';

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'version' => Cache::get(self::CACHE_KEY, 'unknown'),
            ],
        ]);
    }
}
