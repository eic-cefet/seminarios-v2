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

    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
        $user = $request->user();

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $emailChanged = $user->email !== $validated['email'];

        $user->update($validated);

        // If email changed, clear verification
        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
        }

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Update the authenticated user's student data
     */
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

    /**
     * Update the authenticated user's password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
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
