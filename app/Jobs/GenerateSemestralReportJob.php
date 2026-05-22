<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Exports\SemestralReportExport;
use App\Mail\ReportReady;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SemestralReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateSemestralReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public User $user,
        public string $semester,
        public array $courses = [],
        public array $types = [],
        public array $situations = [],
    ) {}

    public function handle(): void
    {
        $this->setAuditContext();

        [$year, $sem] = explode('.', $this->semester);

        $reportData = app(SemestralReportService::class)->collect($this->semester, [
            'courses' => $this->courses,
            'types' => $this->types,
            'situations' => $this->situations,
        ]);

        $timestamp = now()->format('YmdHis');
        $filename = "reports/{$timestamp}-relatorio-semestral-{$year}-{$sem}.xlsx";

        Excel::store(
            new SemestralReportExport($reportData, $year, $sem),
            $filename,
            's3',
            \Maatwebsite\Excel\Excel::XLSX
        );

        if (! Storage::disk('s3')->exists($filename)) {
            throw new \RuntimeException("Failed to store report on S3: {$filename}");
        }

        $url = Storage::disk('s3')->temporaryUrl($filename, now()->addHours(2));

        DeleteS3FileJob::dispatch($filename)->delay(now()->addHours(2));

        Mail::to($this->user->email)->queue(new ReportReady(
            reportName: "Relatório Semestral {$this->semester}",
            downloadUrl: $url,
        ));

        AuditLog::record(AuditEvent::ReportGenerated, AuditEventType::System, eventData: [
            'type' => 'semestral',
            'semester' => $this->semester,
            'format' => 'excel',
            'recipient' => $this->user->email,
        ]);
    }
}
