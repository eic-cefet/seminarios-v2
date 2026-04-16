<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Exports\AuditLogExport;
use App\Mail\ReportReady;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateAuditLogReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public User $user,
        public int $days,
        public ?string $eventType = null,
        public ?string $eventName = null,
        public ?string $search = null,
    ) {}

    public function handle(): void
    {
        $this->setAuditContext();

        $from = Carbon::now()->subDays($this->days)->startOfDay();

        $query = AuditLog::where('created_at', '>=', $from)
            ->latest('created_at');

        if ($this->eventType !== null) {
            $query->where('event_type', $this->eventType);
        }

        if ($this->eventName !== null) {
            $query->where('event_name', $this->eventName);
        }

        if ($this->search !== null) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search);
            $query->where(function ($q) use ($escaped) {
                $q->where('event_name', 'like', "%{$escaped}%")
                    ->orWhere('origin', 'like', "%{$escaped}%")
                    ->orWhere('ip_address', 'like', "%{$escaped}%")
                    ->orWhereHas('user', function ($q) use ($escaped) {
                        $q->where('name', 'like', "%{$escaped}%");
                    });
            });
        }

        $timestamp = now()->format('YmdHis');
        $filename = "reports/{$timestamp}-audit-logs-{$this->days}d.xlsx";

        Excel::store(
            new AuditLogExport(clone $query, $this->days),
            $filename,
            's3',
            \Maatwebsite\Excel\Excel::XLSX
        );

        if (! Storage::disk('s3')->exists($filename)) {
            throw new \RuntimeException("Failed to store report on S3: {$filename}");
        }

        $url = Storage::disk('s3')->temporaryUrl($filename, now()->addHours(2));

        DeleteS3FileJob::dispatch($filename)->delay(now()->addHours(2));

        Mail::to($this->user->email)->send(new ReportReady(
            reportName: "Logs de Auditoria — últimos {$this->days} dias",
            downloadUrl: $url,
        ));

        AuditLog::record(AuditEvent::ReportGenerated, AuditEventType::System, eventData: [
            'type' => 'audit_logs',
            'days' => $this->days,
            'format' => 'excel',
            'recipient' => $this->user->email,
        ]);
    }
}
