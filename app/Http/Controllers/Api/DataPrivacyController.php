<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DataExportRequestResource;
use App\Jobs\ExportUserDataJob;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataPrivacyController extends Controller
{
    public function indexExports(Request $request): JsonResponse
    {
        $exports = $request->user()
            ->dataExportRequests()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => DataExportRequestResource::collection($exports),
        ]);
    }

    public function requestExport(Request $request): JsonResponse
    {
        $user = $request->user();

        $recent = $user->dataExportRequests()
            ->where('created_at', '>=', now()->subDay())
            ->whereIn('status', [
                DataExportRequest::STATUS_QUEUED,
                DataExportRequest::STATUS_RUNNING,
                DataExportRequest::STATUS_COMPLETED,
            ])
            ->exists();

        if ($recent) {
            throw ApiException::tooManyAttempts(
                'Você já solicitou uma exportação de dados nas últimas 24 horas. Aguarde para pedir de novo.',
            );
        }

        $exportRequest = $user->dataExportRequests()->create([
            'status' => DataExportRequest::STATUS_QUEUED,
        ]);

        AuditLog::record(
            event: AuditEvent::DataExportRequested,
            auditable: $exportRequest,
        );

        ExportUserDataJob::dispatch($exportRequest->id);

        return response()->json(
            ['data' => new DataExportRequestResource($exportRequest)],
            202,
        );
    }
}
