<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCertificateResource;
use App\Http\Resources\UserRegistrationResource;
use App\Models\Seminar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileRegistrationController extends Controller
{
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

    public function schedule(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $this->getPerPage($request);

        $paginator = $user->registrations()
            ->whereHas('seminar', fn ($query) => $query->active()->upcoming())
            ->with(['seminar.seminarType', 'seminar.seminarLocation'])
            ->orderBy(
                Seminar::query()
                    ->select('scheduled_at')
                    ->whereColumn('seminars.id', 'registrations.seminar_id'),
            )
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
}
