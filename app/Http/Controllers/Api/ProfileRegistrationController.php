<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCertificateResource;
use App\Http\Resources\UserRegistrationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileRegistrationController extends Controller
{
    /**
     * Get the authenticated user's registrations (seminars attended)
     */
    public function registrations(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $this->getPerPage($request);

        $paginator = $user->registrations()
            ->whereHas('seminar')
            ->with(['seminar.seminarType', 'seminar.seminarLocation'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => UserRegistrationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get the authenticated user's certificates
     */
    public function certificates(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $this->getPerPage($request);

        $paginator = $user->registrations()
            ->whereHas('seminar')
            ->whereNotNull('certificate_code')
            ->where('present', true)
            ->with(['seminar.seminarType'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => UserCertificateResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
