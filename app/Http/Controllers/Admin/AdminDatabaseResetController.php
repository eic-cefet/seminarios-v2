<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetDatabaseRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\DatabaseResetService;
use App\Support\Locking\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AdminDatabaseResetController extends Controller
{
    public function __invoke(
        ResetDatabaseRequest $request,
        DatabaseResetService $databaseReset,
    ): JsonResponse {
        /** @var User $admin */
        $admin = $request->user();
        $actor = ['id' => $admin->id, 'email' => $admin->email];

        try {
            $databaseReset->reset();
        } catch (LockTimeoutException) {
            throw ApiException::conflict('Já existe uma recriação do banco em andamento.');
        } catch (ApiException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ApiException::serverError();
        }

        Auth::guard('web')->logout();
        Auth::guard('sanctum')->forgetUser();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $this->recordAudit($actor);

        return response()->json([
            'message' => 'Banco de dados recriado e populado com sucesso.',
        ]);
    }

    /** @param array{id: int, email: string} $actor */
    private function recordAudit(array $actor): void
    {
        try {
            $seededUserId = User::query()
                ->where('email', $actor['email'])
                ->value('id');

            AuditLog::record(
                AuditEvent::DatabaseReset,
                eventData: [
                    'environment' => app()->environment(),
                    'triggered_by_user_id' => $actor['id'],
                    'triggered_by_email' => $actor['email'],
                ],
                userId: $seededUserId,
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
