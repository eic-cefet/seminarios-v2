<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserLgpdResource;
use App\Jobs\AnonymizeUserJob;
use App\Jobs\ExportUserDataJob;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLgpdController extends Controller
{
    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $user->load(['consents', 'dataExportRequests']);

        return response()->json([
            'data' => new AdminUserLgpdResource($user),
        ]);
    }

    public function export(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $exportRequest = $user->dataExportRequests()->create([
            'status' => DataExportRequest::STATUS_QUEUED,
        ]);

        AuditLog::record(
            event: AuditEvent::DataExportRequested,
            auditable: $exportRequest,
            eventData: ['on_behalf_of_user_id' => $user->id, 'requested_by' => 'admin'],
        );

        ExportUserDataJob::dispatch($exportRequest->id);

        return response()->json(
            ['data' => ['data_export_request_id' => $exportRequest->id]],
            202,
        );
    }

    public function anonymize(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($user->anonymization_requested_at === null) {
            $user->forceFill(['anonymization_requested_at' => now()])->save();
        }

        AuditLog::record(
            event: AuditEvent::AccountDeletionExecuted,
            auditable: $user,
            eventData: ['reason' => $validated['reason']],
        );

        AnonymizeUserJob::dispatch($user->id);

        return response()->json([
            'message' => 'Anonimização agendada.',
        ], 202);
    }
}
