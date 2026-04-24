<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Mail\ReportReady;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Services\UserDataExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $dataExportRequestId) {}

    public function handle(UserDataExportService $service): void
    {
        $this->setAuditContext();

        $request = DataExportRequest::query()->findOrFail($this->dataExportRequestId);
        $request->markRunning();
        $user = $request->user;

        $tmp = $service->writeZip($user);

        $filename = sprintf(
            'lgpd-exports/%d/%s-%s.zip',
            $user->id,
            now()->format('Y-m-d'),
            Str::random(10),
        );

        $stream = fopen($tmp, 'r');
        Storage::disk('s3')->put($filename, $stream, ['visibility' => 'private']);
        if (is_resource($stream)) {
            fclose($stream);
        }
        $size = filesize($tmp) ?: null;
        @unlink($tmp);

        if (! Storage::disk('s3')->exists($filename)) {
            throw new \RuntimeException("Failed to store data export on S3: {$filename}");
        }

        $expiresAt = now()->addDay();
        $request->markCompleted($filename, $expiresAt, $size);

        $url = Storage::disk('s3')->temporaryUrl($filename, $expiresAt);

        DeleteS3FileJob::dispatch($filename)->delay($expiresAt);

        Mail::to($user->email)->send(new ReportReady(
            reportName: 'Exportação de Dados Pessoais (LGPD)',
            downloadUrl: $url,
        ));

        AuditLog::record(AuditEvent::DataExportDelivered, AuditEventType::System, eventData: [
            'user_id' => $user->id,
            'data_export_request_id' => $request->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $request = DataExportRequest::query()->find($this->dataExportRequestId);
        $request?->markFailed($exception->getMessage());
        AuditLog::record(
            event: AuditEvent::DataExportFailed,
            auditable: $request,
        );
    }
}
