<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\FormatsUserResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\StudentDataUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use FormatsUserResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $emailChanged = $user->email !== $validated['email'];

        $user->update($validated);

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    public function updateStudentData(StudentDataUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->studentData) {
            $user->studentData->update($validated);
        } else {
            $user->studentData()->create($validated);
        }

        return response()->json([
            'message' => 'Dados atualizados com sucesso.',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ApiException::mismatchedCredentials();
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Senha atualizada com sucesso.',
        ]);
    }
}
