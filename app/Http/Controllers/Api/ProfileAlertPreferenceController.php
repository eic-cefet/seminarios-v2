<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlertPreferenceUpdateRequest;
use App\Http\Resources\AlertPreferenceResource;
use App\Models\AlertPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileAlertPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $pref = $user->alertPreference()->with(['seminarTypes:id', 'subjects:id'])->first();

        if (! $pref) {
            return response()->json([
                'data' => [
                    'optedIn' => false,
                    'seminarTypeIds' => [],
                    'subjectIds' => [],
                ],
            ]);
        }

        return response()->json([
            'data' => (new AlertPreferenceResource($pref))->toArray($request),
        ]);
    }

    public function update(AlertPreferenceUpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $pref = DB::transaction(function () use ($user, $validated) {
            $pref = AlertPreference::updateOrCreate(
                ['user_id' => $user->id],
                ['opted_in' => $validated['opted_in']],
            );

            $pref->seminarTypes()->sync($validated['seminar_type_ids'] ?? []);
            $pref->subjects()->sync($validated['subject_ids'] ?? []);

            return $pref;
        });

        $pref->load(['seminarTypes:id', 'subjects:id']);

        return response()->json([
            'message' => 'Preferências atualizadas com sucesso.',
            'data' => (new AlertPreferenceResource($pref))->toArray($request),
        ]);
    }
}
