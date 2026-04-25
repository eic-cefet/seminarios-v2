<?php

// tests/Feature/Console/TestAffectedCommandTest.php

use Illuminate\Support\Facades\Process;

it('skips when no files changed', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: ''),
    ]);

    $this->artisan('test:affected', ['--base' => 'origin/main'])
        ->expectsOutputToContain('No affected backend tests')
        ->assertSuccessful();

    Process::assertNotRan(fn ($process): bool => is_string($process->command)
        && str_contains($process->command, 'artisan') && str_contains($process->command, 'test'));
});

it('falls back to the full suite when a trigger path changes', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: "composer.lock\n"),
        "*'php'*'artisan'*'test'*" => Process::result(exitCode: 0, output: 'OK'),
    ]);

    $this->artisan('test:affected', ['--base' => 'origin/main'])
        ->expectsOutputToContain('full suite')
        ->assertSuccessful();

    Process::assertRan(fn ($p): bool => is_string($p->command)
        && str_contains($p->command, 'artisan')
        && str_contains($p->command, 'test')
        && str_contains($p->command, '--coverage')
        && str_contains($p->command, '--min=95'));
});

it('runs targeted tests with scoped coverage and 100% min', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: "app/Models/User.php\n"),
        "*'grep'*" => Process::result(output: "tests/Feature/UserTest.php\n"),
        "*'php'*'artisan'*'test'*" => Process::result(exitCode: 0, output: 'OK'),
    ]);

    $this->artisan('test:affected', ['--base' => 'origin/main'])
        ->assertSuccessful();

    Process::assertRan(fn ($p): bool => is_string($p->command)
        && str_contains($p->command, 'tests/Feature/UserTest.php')
        && str_contains($p->command, '--coverage-filter=app/Models/User.php')
        && str_contains($p->command, '--min=100'));
});

it('returns a non-zero exit code when the underlying test run fails', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: "tests/Feature/Foo.php\n"),
        "*'php'*'artisan'*'test'*" => Process::result(exitCode: 1, output: 'FAIL'),
    ]);

    $this->artisan('test:affected', ['--base' => 'origin/main'])
        ->assertFailed();
});
