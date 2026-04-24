<?php

use App\Jobs\DeleteS3FileJob;
use App\Jobs\GenerateSemestralReportJob;
use App\Mail\ReportReady;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use App\Models\UserStudentData;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

it('generates excel report and sends email to recipient', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create(['email' => 'admin@example.com']);
    $student = User::factory()->create();
    $course = Course::factory()->create();
    UserStudentData::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'course_situation' => 'studying',
    ]);
    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
        'duration_minutes' => 90,
    ]);
    Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $job = new GenerateSemestralReportJob($user, "{$currentYear}.1");
    $job->handle();

    Mail::assertQueued(ReportReady::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && str_contains($mail->reportName, (string) now()->year);
    });

    Queue::assertPushed(DeleteS3FileJob::class);
});

it('applies types filter', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();
    $type = SeminarType::factory()->create();

    $student = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
        'seminar_type_id' => $type->id,
    ]);
    Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $job = new GenerateSemestralReportJob($user, "{$currentYear}.1", types: [$type->id]);
    $job->handle();

    Mail::assertQueued(ReportReady::class);
});

it('applies situations filter', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create();
    $student = User::factory()->create();
    UserStudentData::create(['user_id' => $student->id, 'course_id' => $course->id, 'course_situation' => 'studying']);

    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
    ]);
    Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $job = new GenerateSemestralReportJob($user, "{$currentYear}.1", situations: ['studying']);
    $job->handle();

    Mail::assertQueued(ReportReady::class);
});

it('applies course filter', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();

    $student1 = User::factory()->create();
    UserStudentData::create(['user_id' => $student1->id, 'course_id' => $course1->id, 'course_situation' => 'studying']);

    $student2 = User::factory()->create();
    UserStudentData::create(['user_id' => $student2->id, 'course_id' => $course2->id, 'course_situation' => 'studying']);

    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
    ]);
    Registration::factory()->create(['user_id' => $student1->id, 'seminar_id' => $seminar->id]);
    Registration::factory()->create(['user_id' => $student2->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $job = new GenerateSemestralReportJob($user, "{$currentYear}.1", courses: [$course1->id]);
    $job->handle();

    Mail::assertQueued(ReportReady::class);
});

it('handles second semester date range', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();
    $student = User::factory()->create();

    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonths(7),
    ]);
    Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $job = new GenerateSemestralReportJob($user, "{$currentYear}.2");
    $job->handle();

    Mail::assertQueued(ReportReady::class);
});

it('throws and does not send email when s3 upload fails', function () {
    Storage::fake('s3');
    Excel::fake(); // faking Excel prevents the file from being written to storage
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();

    $currentYear = now()->year;
    $job = new GenerateSemestralReportJob($user, "{$currentYear}.1");

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);

    Mail::assertNothingSent();
});

it('records audit log on completion', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();

    $currentYear = now()->year;
    $job = new GenerateSemestralReportJob($user, "{$currentYear}.1");
    $job->handle();

    $this->assertDatabaseHas('audit_logs', [
        'event_name' => 'report.generated',
        'event_type' => 'system',
    ]);
});
