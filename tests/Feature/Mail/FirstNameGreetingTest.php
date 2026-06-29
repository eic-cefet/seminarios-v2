<?php

use App\Mail\AccountAnonymized;
use App\Mail\AccountDeletionCancelled;
use App\Mail\AccountDeletionConfirmation;
use App\Mail\AccountDeletionScheduled;
use App\Mail\CertificateGenerated;
use App\Mail\EvaluationReminder;
use App\Mail\NewSeminarAlert;
use App\Mail\SeminarRegistrationConfirmation;
use App\Mail\SeminarReminder;
use App\Mail\SeminarReminder7d;
use App\Mail\SeminarRescheduled;
use App\Mail\SpeakerSeminarRecap;
use App\Mail\SpeakerSeminarReminder;
use App\Mail\SpeakerSeminarRescheduled;
use App\Mail\WelcomeUser;
use App\Mail\WorkshopAvailable;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('greets the recipient by first name only', function (Closure $build) {
    $user = User::factory()->create(['name' => 'Mariana Costa Lima']);

    $rendered = $build($user)->render();

    expect($rendered)
        ->toContain('Mariana')
        ->not->toContain('Mariana Costa Lima');
})->with([
    'welcome' => [fn (User $u) => new WelcomeUser($u)],
    'seminar-reminder' => [fn (User $u) => new SeminarReminder($u, collect([Seminar::factory()->create()]))],
    'seminar-reminder-7d' => [fn (User $u) => new SeminarReminder7d($u, collect([Seminar::factory()->create()]))],
    'registration-confirmation' => [fn (User $u) => new SeminarRegistrationConfirmation($u, Seminar::factory()->create())],
    'seminar-rescheduled' => [fn (User $u) => new SeminarRescheduled($u, Seminar::factory()->create(), Carbon::now()->subDay())],
    'evaluation-reminder' => [fn (User $u) => new EvaluationReminder($u, collect([Seminar::factory()->create()]))],
    'new-seminar-alert' => [fn (User $u) => new NewSeminarAlert($u, Seminar::factory()->create())],
    'workshop-available' => [fn (User $u) => new WorkshopAvailable($u, Workshop::factory()->create())],
    'account-deletion-scheduled' => [fn (User $u) => new AccountDeletionScheduled($u, Carbon::now()->addDays(30))],
    'account-deletion-cancelled' => [fn (User $u) => new AccountDeletionCancelled($u)],
    'account-deletion-confirmation' => [fn (User $u) => new AccountDeletionConfirmation($u, 'https://example.test/confirm', Carbon::now()->addHour())],
    'account-anonymized' => [fn (User $u) => new AccountAnonymized($u->name)],
    'certificate-generated' => [function (User $u) {
        Storage::fake('s3');
        Storage::disk('s3')->put('certificates/x.pdf', '%PDF-1.4 fake');

        return new CertificateGenerated(Registration::factory()->create(['user_id' => $u->id]), 'certificates/x.pdf');
    }],
    'speaker-seminar-reminder' => [fn (User $u) => new SpeakerSeminarReminder($u, Seminar::factory()->create())],
    'speaker-seminar-rescheduled' => [fn (User $u) => new SpeakerSeminarRescheduled($u, Seminar::factory()->create(), Carbon::now()->subDay())],
    'speaker-seminar-recap' => [fn (User $u) => new SpeakerSeminarRecap($u, Seminar::factory()->create(), 12)],
]);
