<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\SystemInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminSystemInfoController extends Controller
{
    private const CACHE_KEY = 'admin:system:info';

    private const CACHE_TTL_SECONDS = 30;

    public function __construct(private readonly SystemInfoService $systemInfo) {}

    public function show(Request $request): JsonResponse
    {
        if (! $request->user()?->hasRole(Role::Admin)) {
            throw ApiException::forbidden();
        }

        $data = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => $this->systemInfo->collect(),
        );

        return response()->json(['data' => $data]);
    }
}
