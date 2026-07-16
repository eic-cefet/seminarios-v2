<?php

use App\Gamification\ExperienceLevel;
use Tests\TestCase;

uses(TestCase::class);

it('calculates exact lifetime level boundaries', function (int $xp, array $expected) {
    expect(app(ExperienceLevel::class)->fromXp($xp))->toBe($expected);
})->with([
    'zero xp' => [0, ['level' => 1, 'rank' => 'Iniciante', 'current_level_xp' => 0, 'next_level_xp' => 100, 'progress_percent' => 0]],
    'last xp in level one' => [99, ['level' => 1, 'rank' => 'Iniciante', 'current_level_xp' => 0, 'next_level_xp' => 100, 'progress_percent' => 99]],
    'first xp in level two' => [100, ['level' => 2, 'rank' => 'Curioso', 'current_level_xp' => 100, 'next_level_xp' => 300, 'progress_percent' => 0]],
    'last xp in level two' => [299, ['level' => 2, 'rank' => 'Curioso', 'current_level_xp' => 100, 'next_level_xp' => 300, 'progress_percent' => 99]],
    'first xp in level three' => [300, ['level' => 3, 'rank' => 'Curioso', 'current_level_xp' => 300, 'next_level_xp' => 600, 'progress_percent' => 0]],
    'last xp before explorer' => [999, ['level' => 4, 'rank' => 'Curioso', 'current_level_xp' => 600, 'next_level_xp' => 1000, 'progress_percent' => 99]],
    'first xp as explorer' => [1000, ['level' => 5, 'rank' => 'Explorador', 'current_level_xp' => 1000, 'next_level_xp' => 1500, 'progress_percent' => 0]],
    'first xp in level twenty' => [19000, ['level' => 20, 'rank' => 'Lenda EIC', 'current_level_xp' => 19000, 'next_level_xp' => 21000, 'progress_percent' => 0]],
]);

it('selects the greatest configured rank boundary not exceeding the level', function (int $xp, string $rank) {
    expect(app(ExperienceLevel::class)->fromXp($xp)['rank'])->toBe($rank);
})->with([
    'level 1' => [0, 'Iniciante'],
    'level 2' => [100, 'Curioso'],
    'level 5' => [1000, 'Explorador'],
    'level 8' => [2800, 'Investigador'],
    'level 12' => [6600, 'Especialista'],
    'level 16' => [12000, 'Mestre'],
    'level 20' => [19000, 'Lenda EIC'],
    'after level 20' => [21000, 'Lenda EIC'],
]);

it('clamps negative lifetime xp to zero', function () {
    expect(app(ExperienceLevel::class)->fromXp(-50))->toBe([
        'level' => 1,
        'rank' => 'Iniciante',
        'current_level_xp' => 0,
        'next_level_xp' => 100,
        'progress_percent' => 0,
    ]);
});

it('returns an integer progress percentage clamped between zero and one hundred', function (int $xp) {
    $progress = app(ExperienceLevel::class)->fromXp($xp)['progress_percent'];

    expect($progress)->toBeInt()
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
})->with([-1, 0, 99, 100, 200, 299, 300, 999, 1000, 18999, 19000]);
