<?php

use App\Enums\BadgeKey;
use App\Enums\ConsentType;
use App\Enums\ExperienceReason;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\UserDataExportService;

beforeEach(function () {
    $this->service = app(UserDataExportService::class);
});

it('collects profile, registrations, ratings, consents for a user', function () {
    $user = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@example.com']);
    $seminar = Seminar::factory()->create();
    Registration::factory()->for($user)->for($seminar)->create(['present' => true]);
    Rating::factory()->for($user)->for($seminar)->create(['score' => 5, 'comment' => 'Excelente']);
    UserConsent::factory()->for($user)->granted()->create(['type' => ConsentType::PrivacyPolicy]);

    $payload = $this->service->collect($user);

    expect($payload['profile']['name'])->toBe('Ana Silva')
        ->and($payload['profile']['email'])->toBe('ana@example.com')
        ->and($payload['registrations'])->toHaveCount(1)
        ->and($payload['ratings'])->toHaveCount(1)
        ->and($payload['ratings'][0]['comment'])->toBe('Excelente')
        ->and($payload['consents'])->toHaveCount(1);
});

it('exports only the users gamification rows in deterministic order', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->badges()->create([
        'badge_key' => BadgeKey::FirstPresence,
        'earned_at' => '2026-07-15 09:00:00',
    ]);
    $user->badges()->create([
        'badge_key' => BadgeKey::Attendance5,
        'earned_at' => '2026-07-15 08:00:00',
    ]);
    $otherUser->badges()->create([
        'badge_key' => BadgeKey::Attendance10,
        'earned_at' => '2026-07-15 07:00:00',
    ]);

    $user->experienceEvents()->create([
        'reason' => ExperienceReason::Evaluation,
        'source_key' => 'evaluation:456',
        'points' => 20,
    ]);
    $otherUser->experienceEvents()->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => 'attendance:other',
        'points' => 999,
    ]);
    $user->experienceEvents()->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => 'attendance:123',
        'points' => 100,
    ]);
    $user->experienceEvents()->create([
        'reason' => ExperienceReason::BadgeBonus,
        'source_key' => 'badge:first_presence',
        'points' => 155,
    ]);

    $payload = $this->service->collect($user);

    expect($payload['gamification'])->toBe([
        'total_xp' => 275,
        'level' => 2,
        'rank' => 'Curioso',
        'badges' => [
            ['key' => 'attendance_5', 'earned_at' => '2026-07-15T11:00:00+00:00'],
            ['key' => 'first_presence', 'earned_at' => '2026-07-15T12:00:00+00:00'],
        ],
        'experience_events' => [
            ['reason' => 'evaluation', 'source_key' => 'evaluation:456', 'points' => 20],
            ['reason' => 'attendance', 'source_key' => 'attendance:123', 'points' => 100],
            ['reason' => 'badge_bonus', 'source_key' => 'badge:first_presence', 'points' => 155],
        ],
    ])->and($user->relationLoaded('badges'))->toBeTrue()
        ->and($user->relationLoaded('experienceEvents'))->toBeTrue();
});

it('builds a ZIP containing JSON files and a README', function () {
    $user = User::factory()->create();
    Registration::factory()->for($user)->create();

    $zipPath = $this->service->writeZip($user);

    expect(file_exists($zipPath))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();

    expect($names)->toContain('profile.json')
        ->and($names)->toContain('registrations.json')
        ->and($names)->toContain('ratings.json')
        ->and($names)->toContain('consents.json')
        ->and($names)->toContain('README.txt');

    @unlink($zipPath);
});

it('writes a README inventory note that uses feminine "apresentações" copy', function () {
    $user = User::factory()->create();

    $zipPath = $this->service->writeZip($user);

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $readme = $zip->getFromName('README.txt');
    $zip->close();
    @unlink($zipPath);

    expect($readme)
        ->toContain('inscrições e presenças nas apresentações.')
        ->not->toContain('inscrições e presenças nos seminários.');
});

it('creates the tmp directory when it does not exist', function () {
    $user = User::factory()->create();

    $tmpDir = storage_path('app/tmp');
    $existed = is_dir($tmpDir);

    // Temporarily remove the directory if it exists to force the mkdir branch
    if ($existed) {
        $files = glob($tmpDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        rmdir($tmpDir);
    }

    $zipPath = $this->service->writeZip($user);
    expect(file_exists($zipPath))->toBeTrue();
    @unlink($zipPath);
});
