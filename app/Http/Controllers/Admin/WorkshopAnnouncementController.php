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

class WorkshopAnnouncementController extends Controller
{
    public function store(Workshop $workshop): JsonResponse
    {
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
