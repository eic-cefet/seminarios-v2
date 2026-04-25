<?php

// tests/Unit/Console/Support/AffectedTestPlannerTest.php

use App\Console\Support\AffectedTestPlanner;
use Illuminate\Support\Facades\Process;

it('returns skip when there are no changed files', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: ''),
    ]);

    $plan = app(AffectedTestPlanner::class)->resolve('origin/main');

    expect($plan)->toMatchArray([
        'skip' => true,
        'full_suite' => false,
        'source_files' => [],
        'test_files' => [],
        'coverage_filter' => [],
    ]);
});

it('falls back to full suite when a trigger path changes', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: "composer.lock\napp/Models/User.php\n"),
    ]);

    $plan = app(AffectedTestPlanner::class)->resolve('origin/main');

    expect($plan['full_suite'])->toBeTrue()
        ->and($plan['skip'])->toBeFalse()
        ->and($plan['reason'])->toContain('composer.lock')
        ->and($plan['coverage_filter'])->toBe(['app/']);
});

it('maps changed source files to test files via class-name grep', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: "app/Models/User.php\n"),
        "*'grep'*'User'*'tests/'*" => Process::result(
            output: "tests/Feature/UserTest.php\ntests/Unit/Models/UserScopeTest.php\n"
        ),
    ]);

    $plan = app(AffectedTestPlanner::class)->resolve('origin/main');

    expect($plan['skip'])->toBeFalse()
        ->and($plan['full_suite'])->toBeFalse()
        ->and($plan['source_files'])->toBe(['app/Models/User.php'])
        ->and($plan['test_files'])->toBe([
            'tests/Feature/UserTest.php',
            'tests/Unit/Models/UserScopeTest.php',
        ])
        ->and($plan['coverage_filter'])->toBe(['app/Models/User.php']);
});

it('always includes directly changed test files', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: "tests/Feature/SomeTest.php\n"),
    ]);

    $plan = app(AffectedTestPlanner::class)->resolve('origin/main');

    expect($plan['skip'])->toBeFalse()
        ->and($plan['source_files'])->toBe([])
        ->and($plan['test_files'])->toBe(['tests/Feature/SomeTest.php']);
});

it('skips when changes are unrelated to PHP code', function (): void {
    Process::fake([
        "*'git' 'diff'*" => Process::result(output: "README.md\nresources/js/foo.ts\n"),
    ]);

    $plan = app(AffectedTestPlanner::class)->resolve('origin/main');

    expect($plan['skip'])->toBeTrue();
});
