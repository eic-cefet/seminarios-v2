<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateAuditLogReportJob;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AdminAuditLogController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $request->validate([
            'days' => 'sometimes|integer|in:7,30,60,90,365',
        ]);

        $days = $request->integer('days', 30);
        $from = Carbon::now()->subDays($days)->startOfDay();

        $summary = AuditLog::where('created_at', '>=', $from)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when event_type = 'manual' then 1 else 0 end) as manual_count")
            ->selectRaw("sum(case when event_type = 'system' then 1 else 0 end) as system_count")
            ->first();

        $topEvents = AuditLog::where('created_at', '>=', $from)
            ->selectRaw('event_name, count(*) as count')
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'event_name');

        return response()->json([
            'data' => [
                'total' => (int) $summary->total,
                'manual_count' => (int) $summary->manual_count,
                'system_count' => (int) $summary->system_count,
                'top_events' => $topEvents,
            ],
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        Gate::authorize('export', AuditLog::class);

        $request->validate($this->filterRules());

        $days = $request->integer('days', 30);

        GenerateAuditLogReportJob::dispatch(
            $request->user(),
            $days,
            $request->filled('event_type') ? $request->input('event_type') : null,
            $request->filled('event_name') ? $request->input('event_name') : null,
            $request->filled('search') ? $request->input('search') : null,
        );

        AuditLog::record(AuditEvent::ReportQueued, eventData: [
            'type' => 'audit_logs',
            'days' => $days,
            'format' => 'excel',
        ]);

        return response()->json([
            'message' => 'Relatório sendo gerado. Você receberá um e-mail em breve.',
        ]);
    }

    public function eventNames(): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $names = AuditLog::select('event_name')
            ->distinct()
            ->orderBy('event_name')
            ->pluck('event_name');

        return response()->json(['data' => $names]);
    }

    private function filterRules(): array
    {
        return [
            'days' => 'sometimes|integer|in:7,30,60,90,365',
            'event_type' => 'sometimes|string|in:manual,system',
            'event_name' => 'sometimes|string|max:100',
            'search' => 'sometimes|string|max:255',
        ];
    }
}
