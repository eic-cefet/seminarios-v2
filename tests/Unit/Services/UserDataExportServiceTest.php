<?php

use App\Enums\ConsentType;
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
