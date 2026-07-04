<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileCalendarFeedController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->calendar_feed_token) {
            $user->forceFill(['calendar_feed_token' => Str::random(48)])->save();
        }

        return response()->json([
            'data' => $this->feedUrls($user),
        ]);
    }

    public function rotate(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill(['calendar_feed_token' => Str::random(48)])->save();

        AuditLog::record(AuditEvent::CalendarFeedRotated, auditable: $user);

        return response()->json([
            'message' => 'Novo link gerado com sucesso.',
            'data' => $this->feedUrls($user),
        ]);
    }

    /**
     * @return array{personal_url: string, public_url: string}
     */
    private function feedUrls(User $user): array
    {
        return [
            'personal_url' => route('calendar.personal-feed', ['token' => $user->calendar_feed_token]),
            'public_url' => route('calendar.public-feed'),
        ];
    }
}
