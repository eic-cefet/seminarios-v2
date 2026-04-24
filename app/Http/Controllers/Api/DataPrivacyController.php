<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DataExportRequestResource;
use App\Jobs\ExportUserDataJob;
use App\Mail\AccountDeletionCancelled;
use App\Mail\AccountDeletionConfirmation;
use App\Mail\AccountDeletionScheduled;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

        $token = Str::random(64);
        $cacheKey = 'lgpd.deletion-confirm:'.$token;
        $ttl = now()->addHour();

        Cache::put($cacheKey, $user->id, $ttl);

        $confirmUrl = url('/confirmar-exclusao/'.$token);

        Mail::to($user->email)->queue(new AccountDeletionConfirmation(
            user: $user,
            confirmUrl: $confirmUrl,
            expiresAt: $ttl,
        ));

        AuditLog::record(
            event: AuditEvent::AccountDeletionConfirmationSent,
            auditable: $user,
        );

        return response()->json([
            'message' => 'Enviamos um link de confirmação para o seu e-mail. Clique nele em até 1 hora para concluir a exclusão.',
        ]);
    }

    public function confirmDeletion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
        ]);

        $cacheKey = 'lgpd.deletion-confirm:'.$validated['token'];
        $cachedUserId = Cache::get($cacheKey);

        if ($cachedUserId === null) {
            throw ApiException::validation([
                'token' => ['O link de confirmação é inválido ou expirou.'],
            ]);
        }

        $requestingUser = $request->user();

        if ((int) $cachedUserId !== (int) $requestingUser->id) {
            // Do not consume the token — the legitimate owner should still be
            // able to confirm with the same link.
            throw ApiException::forbidden('Este link pertence a outra conta.');
        }

        // Lock the row + consume the token inside a transaction so concurrent
        // confirm requests can never both pass the "not yet flagged" check.
        [$user, $alreadyFlagged] = DB::transaction(
            function () use ($requestingUser, $cacheKey) {
                $locked = User::query()
                    ->whereKey($requestingUser->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $already = $locked->anonymization_requested_at !== null;

                if (! $already) {
                    $locked->forceFill(['anonymization_requested_at' => now()])->save();
                }

                Cache::forget($cacheKey);

                return [$locked, $already];
            },
        );

        if ($alreadyFlagged) {
            return response()->json([
                'message' => 'Sua solicitação já foi confirmada.',
                'scheduled_for' => $user->anonymization_requested_at
                    ->addDays((int) config('lgpd.retention.account_deletion_grace_days', 30))
                    ->toIso8601String(),
            ]);
        }

        $graceDays = (int) config('lgpd.retention.account_deletion_grace_days', 30);
        $scheduledFor = now()->addDays($graceDays);

        AuditLog::record(
            event: AuditEvent::AccountDeletionRequested,
            auditable: $user,
        );

        Mail::to($user->email)->queue(new AccountDeletionScheduled($user, $scheduledFor));

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
