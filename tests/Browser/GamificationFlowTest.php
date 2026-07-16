<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Services\GamificationService;

beforeEach(function () {
    if (file_exists(public_path('build/manifest.json'))) {
        return;
    }

    if (! file_exists(public_path('hot'))) {
        $this->markTestSkipped('Frontend assets are unavailable and no Vite development server is configured.');
    }

    $hotUrl = trim((string) file_get_contents(public_path('hot')));
    $host = parse_url($hotUrl, PHP_URL_HOST);
    $port = parse_url($hotUrl, PHP_URL_PORT) ?: 80;
    $socket = is_string($host) ? @fsockopen($host, $port, $errorCode, $errorMessage, 1) : false;

    if (! is_resource($socket)) {
        $this->markTestSkipped("The configured Vite development server is unavailable at {$hotUrl}.");
    }

    fclose($socket);
});

it('shows only the authenticated students gamification progress', function () {
    $student = User::factory()->student()->create();
    $seminar = Seminar::factory()->create([
        'name' => 'Privacidade e Segurança',
        'scheduled_at' => now()->subDay(),
    ]);
    Registration::factory()->for($student)->for($seminar)->create(['present' => true]);

    app(GamificationService::class)->sync($student, notify: false);

    $this->actingAs($student);

    visit('/conquistas')
        ->assertSee('Minhas conquistas')
        ->assertSee('Primeiro Passo')
        ->assertSee('Nível 2')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    $otherStudent = User::factory()->student()->create();
    $this->actingAs($otherStudent);

    visit('/conquistas')
        ->assertSee('Minhas conquistas')
        ->assertSee('0 de 30 conquistas')
        ->assertDontSee('Conquistada em')
        ->assertSourceMissing("/conquistas/{$student->id}")
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    $this->getJson("/api/admin/students/{$student->id}/gamification")
        ->assertForbidden();

    visit("/conquistas/{$student->id}")
        ->assertSee('Página não encontrada')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});
