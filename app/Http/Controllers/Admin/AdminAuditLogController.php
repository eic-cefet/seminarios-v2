<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Exports\AuditLogExport;
use App\Http\Controllers\Admin\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Jobs\DeleteS3FileJob;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AdminAuditLogController extends Controller
{
    use EscapesLikeWildcards;

    public function summary(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $request->validate([
            'days' => 'sometimes|integer|in:7,30,60,90,365',
        ]);

        $days = $request->integer('days', 30);
        $from = Carbon::now()->subDays($days)->startOfDay();

        $summary = AuditLog::where('created_at', '>=', $from)
            ->selectRaw("count(*) as total")
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
        $from = Carbon::now()->subDays($days)->startOfDay();

        $query = AuditLog::where('created_at', '>=', $from)
            ->latest('created_at');

        $this->applyFilters($query, $request);

        $timestamp = now()->format('YmdHis');
        $filename = "reports/{$timestamp}-audit-logs-{$days}d.xlsx";

        // FromQuery lets Maatwebsite Excel chunk the query automatically
        Excel::store(
            new AuditLogExport(clone $query, $days),
            $filename,
            's3',
            \Maatwebsite\Excel\Excel::XLSX
        );

        $url = Storage::disk('s3')->temporaryUrl($filename, now()->addHour());

        DeleteS3FileJob::dispatch($filename)->delay(now()->addHours(2));

        AuditLog::record(AuditEvent::ReportGenerated, eventData: [
            'type' => 'audit_logs',
            'days' => $days,
            'format' => 'excel',
        ]);

        return response()->json([
            'message' => 'Relatório gerado com sucesso',
            'url' => $url,
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

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->filled('event_name')) {
            $query->where('event_name', $request->input('event_name'));
        }

        if ($request->filled('search')) {
            $search = $this->escapeLike($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }
    }
}
