<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Exports\SemestralReportExport;
use App\Mail\ReportReady;
use App\Models\AuditLog;
use App\Models\User;
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

        if ($sem == 1) {
            $startDate = "{$year}-01-01 00:00:00";
            $endDate = "{$year}-06-30 23:59:59";
        } else {
            $startDate = "{$year}-07-01 00:00:00";
            $endDate = "{$year}-12-31 23:59:59";
        }

        $query = User::query()
            ->whereHas('registrations', function ($q) use ($startDate, $endDate) {
                $q->whereHas('seminar', function ($sq) use ($startDate, $endDate) {
                    $sq->whereBetween('scheduled_at', [$startDate, $endDate])
                        ->where('active', true);

                    if (! empty($this->types)) {
                        $sq->whereIn('seminar_type_id', $this->types);
                    }
                });
            })
            ->with([
                'studentData.course',
                'registrations' => function ($q) use ($startDate, $endDate) {
                    $q->whereHas('seminar', function ($sq) use ($startDate, $endDate) {
                        $sq->whereBetween('scheduled_at', [$startDate, $endDate])
                            ->where('active', true);

                        if (! empty($this->types)) {
                            $sq->whereIn('seminar_type_id', $this->types);
                        }
                    })
                        ->with([
                            'seminar:id,name,scheduled_at,seminar_type_id,duration_minutes',
                            'seminar.seminarType:id,name',
                        ]);
                },
            ]);

        if (! empty($this->courses)) {
            $query->whereHas('studentData', fn ($q) => $q->whereIn('course_id', $this->courses));
        }

        if (! empty($this->situations)) {
            $query->whereHas('studentData', fn ($q) => $q->whereIn('course_situation', $this->situations));
        }

        $reportData = $query->get()->map(function ($user) {
            $registrations = $user->registrations;
            $totalMinutes = $registrations->sum(
                fn ($registration) => (int) ($registration->seminar->duration_minutes ?? 60)
            );

            $presentations = $registrations->map(fn ($registration) => [
                'name' => $registration->seminar->name,
                'date' => $registration->seminar->scheduled_at,
                'type' => $registration->seminar->seminarType?->name,
                'duration_minutes' => (int) ($registration->seminar->duration_minutes ?? 60),
            ])->sortBy('date')->values();

            return [
                'name' => $user->name,
                'email' => $user->email,
                'course' => $user->studentData?->course?->name ?? 'N/A',
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'presentations' => $presentations,
            ];
        })->sortBy('name')->values();

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

        Mail::to($this->user->email)->send(new ReportReady(
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
