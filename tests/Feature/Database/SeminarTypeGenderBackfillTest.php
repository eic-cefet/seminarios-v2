<?php

use App\Enums\Gender;
use App\Models\SeminarType;
use App\Support\SeminarTypeBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('has gender and name_plural columns on seminar_types', function () {
    $type = SeminarType::create(['name' => 'Test']);

    expect($type->getAttributes())->toHaveKey('gender');
    expect($type->getAttributes())->toHaveKey('name_plural');
});

it('defaults gender to masculine when not specified', function () {
    $type = SeminarType::create(['name' => 'Custom Type']);

    expect($type->fresh()->gender)->toBe(Gender::Masculine);
});

it('allows name_plural to be null', function () {
    $type = SeminarType::create(['name' => 'Custom']);

    expect($type->fresh()->name_plural)->toBeNull();
});

it('backfilled known types via seeder match (Str::slug)', function () {
    // The migration runs once during the test database setup. We verify the same
    // mapping by simulating: create a type with each known slug and run the
    // backfill closure exposed by the migration.
    $known = [
        'Seminário' => [Gender::Masculine, 'Seminários'],
        'Qualificação' => [Gender::Feminine, 'Qualificações'],
        'Dissertação' => [Gender::Feminine, 'Dissertações'],
        'TCC' => [Gender::Masculine, 'TCCs'],
        'Aula inaugural' => [Gender::Feminine, 'Aulas inaugurais'],
        'Painel' => [Gender::Masculine, 'Painéis'],
        'Doutorado' => [Gender::Masculine, 'Doutorados'],
    ];

    foreach ($known as $name => [$expectedGender, $expectedPlural]) {
        $type = SeminarType::create(['name' => $name]);
        SeminarTypeBackfill::apply($type);
        $type->refresh();

        expect($type->gender)->toBe($expectedGender, "gender for '{$name}'");
        expect($type->name_plural)->toBe($expectedPlural, "plural for '{$name}'");
    }
});

it('logs a warning and defaults to masculine for unknown type names during backfill', function () {
    Log::spy();

    $type = SeminarType::create(['name' => 'Tipo Inventado']);
    SeminarTypeBackfill::apply($type);
    $type->refresh();

    expect($type->gender)->toBe(Gender::Masculine);
    expect($type->name_plural)->toBeNull();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $ctx) => str_contains($message, 'Unknown seminar type')
            && $ctx['name'] === 'Tipo Inventado'
            && $ctx['slug'] === 'tipo-inventado');
});
