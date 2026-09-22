<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\MeUserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Gate;

class ImpersonationController extends Controller
{
    public function store(Request $request, User $user): JsonResponse
    {
        Gate::authorize('impersonate', $user);

        $admin = Auth::guard('web')->user();
        if (! $request->hasSession() || ! $admin || $admin->id !== $request->user()->id
            || $request->session()->has('impersonation')) {
            throw ApiException::forbidden('Use uma sessão de administrador para acessar como usuário.');
        }

        AuditLog::record(AuditEvent::ImpersonationStarted, auditable: $user, userId: $admin->id);

        $this->switchUser($request, $user);
        $request->session()->put('impersonation', [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
        ]);

        return response()->json(['user' => (new MeUserResource($user))->resolve()]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $guard = Auth::guard('web');
        $impersonation = $request->hasSession() ? $request->session()->get('impersonation') : null;

        if (! is_array($impersonation) || ! isset($impersonation['admin_id'], $impersonation['user_id'])
            || (int) ($guard->id() ?? $request->session()->get($guard->getName())) !== (int) $impersonation['user_id']) {
            throw ApiException::forbidden('Nenhum acesso como usuário está ativo.');
        }

        $admin = User::find($impersonation['admin_id']);
        if (! $admin || ! $admin->hasRole(Role::Admin) || $admin->isAnonymized()) {
            $guard->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ApiException::forbidden('A conta de administrador não está mais disponível. Entre novamente.');
        }

        AuditLog::record(
            AuditEvent::ImpersonationStopped,
            auditable: User::withTrashed()->find($impersonation['user_id']),
            userId: $admin->id,
        );

        $this->switchUser($request, $admin);

        return response()->json(['user' => (new MeUserResource($admin))->resolve()]);
    }

    private function switchUser(Request $request, User $user): void
    {
        $guard = Auth::guard('web');
        $guard->logoutCurrentDevice();
        Cookie::queue(Cookie::forget($guard->getRecallerName()));
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $guard->login($user);
        Auth::forgetGuards();
    }
}
