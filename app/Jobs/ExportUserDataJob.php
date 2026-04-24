<?php

namespace App\Jobs;

use App\Enums\AuditEvent;
use App\Mail\DataExportFailed;
use App\Mail\DataExportReady;
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
use Throwable;

class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $dataExportRequestId) {}

    public function handle(UserDataExportService $service): void
    {
        $request = DataExportRequest::query()->findOrFail($this->dataExportRequestId);
        $request->markRunning();
        $user = $request->user;

        try {
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

            $hours = (int) config('lgpd.retention.data_export_link_hours', 48);
            $expiresAt = now()->addHours($hours);
            $request->markCompleted($filename, $expiresAt, $size);

            $signedUrl = Storage::disk('s3')->temporaryUrl($filename, $expiresAt);

            Mail::to($user->email)->queue(new DataExportReady($user, $signedUrl, $expiresAt));

            AuditLog::record(
                event: AuditEvent::DataExportDelivered,
                auditable: $request,
            );
        } catch (Throwable $e) {
            $request->markFailed($e->getMessage());
            AuditLog::record(
                event: AuditEvent::DataExportFailed,
                auditable: $request,
            );
            Mail::to($user->email)->queue(new DataExportFailed($user));
            throw $e;
        }
    }
}
