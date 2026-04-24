<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DataExportRequestResource;
use App\Jobs\ExportUserDataJob;
use App\Mail\AccountDeletionCancelled;
use App\Mail\AccountDeletionScheduled;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

    public function requestDeletion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->anonymization_requested_at !== null) {
            throw ApiException::conflict('Já existe uma solicitação de exclusão pendente.');
        }

        if (! Hash::check($validated['password'], $user->password)) {
            throw ApiException::mismatchedCredentials();
        }

        $user->forceFill(['anonymization_requested_at' => now()])->save();

        $graceDays = (int) config('lgpd.retention.account_deletion_grace_days', 30);
        $scheduledFor = now()->addDays($graceDays);

        AuditLog::record(
            event: AuditEvent::AccountDeletionRequested,
            auditable: $user,
        );

        Mail::to($user->email)->queue(
            new AccountDeletionScheduled($user, $scheduledFor),
        );

        return response()->json([
            'message' => 'Sua conta será excluída em '.$scheduledFor->format('d/m/Y').'.',
            'scheduled_for' => $scheduledFor->toIso8601String(),
        ]);
    }

    public function cancelDeletion(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->anonymization_requested_at === null) {
            throw ApiException::notFound('Não há solicitação de exclusão pendente.');
        }

        $user->forceFill(['anonymization_requested_at' => null])->save();

        AuditLog::record(
            event: AuditEvent::AccountDeletionCancelled,
            auditable: $user,
        );

        Mail::to($user->email)->queue(
            new AccountDeletionCancelled($user),
        );

        return response()->json(['message' => 'Solicitação de exclusão cancelada.']);
    }
}
