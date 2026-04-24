<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlertPreferenceUpdateRequest;
use App\Http\Resources\AlertPreferenceResource;
use App\Models\AlertPreference;
use App\Models\AuditLog;
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
                    'newSeminarAlert' => false,
                    'seminarTypeIds' => [],
                    'subjectIds' => [],
                    'seminarReminder7d' => true,
                    'seminarReminder24h' => true,
                    'evaluationPrompt' => true,
                    'announcements' => true,
                    'certificateReady' => true,
                    'seminarRescheduled' => true,
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
                [
                    'new_seminar_alert' => $validated['new_seminar_alert'],
                    'seminar_reminder_7d' => $validated['seminar_reminder_7d'],
                    'seminar_reminder_24h' => $validated['seminar_reminder_24h'],
                    'evaluation_prompt' => $validated['evaluation_prompt'],
                    'announcements' => $validated['announcements'],
                    'certificate_ready' => $validated['certificate_ready'],
                    'seminar_rescheduled' => $validated['seminar_rescheduled'],
                ],
            );

            $pref->seminarTypes()->sync($validated['seminar_type_ids'] ?? []);
            $pref->subjects()->sync($validated['subject_ids'] ?? []);

            return $pref;
        });

        $pref->load(['seminarTypes:id', 'subjects:id']);

        AuditLog::record(AuditEvent::UserCommunicationPreferencesUpdated, auditable: $pref);

        return response()->json([
            'message' => 'Preferências atualizadas com sucesso.',
            'data' => (new AlertPreferenceResource($pref))->toArray($request),
        ]);
    }
}
