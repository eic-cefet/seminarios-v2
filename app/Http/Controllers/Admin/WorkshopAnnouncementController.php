<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminWorkshopResource;
use App\Jobs\BroadcastWorkshopLaunchJob;
use App\Models\AuditLog;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class WorkshopAnnouncementController extends Controller
{
    // The announcement feature shipped on 2026-04-26. Workshops created before this
    // date predate the feature and could spam users about content they may have
    // already seen — only allow announcing workshops created on or after the cutoff.
    private const ANNOUNCE_CUTOFF = '2026-04-26';

    public function store(Workshop $workshop): JsonResponse
    {
        Gate::authorize('announce', $workshop);

        if ($workshop->created_at->lt(Carbon::parse(self::ANNOUNCE_CUTOFF)->startOfDay())) {
            throw ApiException::workshopTooOldToAnnounce();
        }

        $claimed = Workshop::where('id', $workshop->id)
            ->whereNull('announcement_sent_at')
            ->update(['announcement_sent_at' => now()]);

        if ($claimed === 0) {
            throw ApiException::workshopAlreadyAnnounced();
        }

        BroadcastWorkshopLaunchJob::dispatch($workshop->id);

        $workshop->refresh();
        AuditLog::record(AuditEvent::WorkshopAnnounced, auditable: $workshop);

        return response()->json([
            'message' => 'Workshop anunciado com sucesso.',
            'data' => new AdminWorkshopResource($workshop),
        ]);
    }
}
