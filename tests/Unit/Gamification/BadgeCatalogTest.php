<?php

use App\Enums\BadgeKey;
use App\Gamification\BadgeCatalog;
use App\Gamification\GamificationSnapshot;
use Tests\TestCase;

uses(TestCase::class);

$badgeDefinitions = [
    'first presence' => [BadgeKey::FirstPresence, 'Primeiro Passo', 'Participe de uma apresentação.', 'Participação', 'bronze', 'attendance_count', 1, 'Footprints'],
    'attendance 5' => [BadgeKey::Attendance5, 'Pegando Ritmo', 'Participe de 5 apresentações.', 'Participação', 'bronze', 'attendance_count', 5, 'Flame'],
    'attendance 10' => [BadgeKey::Attendance10, 'Presença Marcante', 'Participe de 10 apresentações.', 'Participação', 'silver', 'attendance_count', 10, 'Medal'],
    'attendance 25' => [BadgeKey::Attendance25, 'Habitante da EIC', 'Participe de 25 apresentações.', 'Participação', 'gold', 'attendance_count', 25, 'Landmark'],
    'attendance 50' => [BadgeKey::Attendance50, 'Veterano de Auditório', 'Participe de 50 apresentações.', 'Participação', 'platinum', 'attendance_count', 50, 'Crown'],
    'attendance 100' => [BadgeKey::Attendance100, 'Lenda das Apresentações', 'Participe de 100 apresentações.', 'Participação', 'platinum', 'attendance_count', 100, 'Trophy'],
    'subjects 3' => [BadgeKey::Subjects3, 'Mente Curiosa', 'Participe de apresentações sobre 3 tópicos distintos.', 'Exploração', 'bronze', 'distinct_subjects', 3, 'Search'],
    'subjects 5' => [BadgeKey::Subjects5, 'Explorador de Tópicos', 'Participe de apresentações sobre 5 tópicos distintos.', 'Exploração', 'bronze', 'distinct_subjects', 5, 'Compass'],
    'subjects 10' => [BadgeKey::Subjects10, 'Conexões do Conhecimento', 'Participe de apresentações sobre 10 tópicos distintos.', 'Exploração', 'silver', 'distinct_subjects', 10, 'Network'],
    'subjects 20' => [BadgeKey::Subjects20, 'Polímata da EIC', 'Participe de apresentações sobre 20 tópicos distintos.', 'Exploração', 'platinum', 'distinct_subjects', 20, 'Brain'],
    'types 3' => [BadgeKey::Types3, 'Formatos sem Fronteiras', 'Participe de apresentações de 3 tipos distintos.', 'Exploração', 'silver', 'distinct_types', 3, 'Shapes'],
    'speakers 5' => [BadgeKey::Speakers5, 'Cinco Perspectivas', 'Participe de apresentações com 5 palestrantes distintos.', 'Exploração', 'bronze', 'distinct_speakers', 5, 'Users'],
    'speakers 15' => [BadgeKey::Speakers15, 'Vozes da Comunidade', 'Participe de apresentações com 15 palestrantes distintos.', 'Exploração', 'gold', 'distinct_speakers', 15, 'AudioLines'],
    'double day' => [BadgeKey::DoubleDay, 'Dose Dupla', 'Participe de 2 apresentações no mesmo dia.', 'Ritmo', 'bronze', 'max_attendances_day', 2, 'CopyPlus'],
    'triple day' => [BadgeKey::TripleDay, 'Maratona de Conhecimento', 'Participe de 3 apresentações no mesmo dia.', 'Ritmo', 'special', 'max_attendances_day', 3, 'Gauge'],
    'week 5' => [BadgeKey::Week5, 'Semana Intensiva', 'Participe de 5 apresentações na mesma semana.', 'Ritmo', 'silver', 'max_attendances_week', 5, 'CalendarDays'],
    'month 8' => [BadgeKey::Month8, 'Mês em Foco', 'Participe de 8 apresentações no mesmo mês.', 'Ritmo', 'gold', 'max_attendances_month', 8, 'CalendarRange'],
    'semester 10' => [BadgeKey::Semester10, 'Semestre em Movimento', 'Participe de 10 apresentações no mesmo semestre.', 'Ritmo', 'gold', 'max_attendances_semester', 10, 'GraduationCap'],
    'year 20' => [BadgeKey::Year20, 'Ano de Ouro', 'Participe de 20 apresentações no mesmo ano.', 'Ritmo', 'platinum', 'max_attendances_year', 20, 'Sparkles'],
    'semesters 2' => [BadgeKey::Semesters2, 'Sempre Presente', 'Participe de apresentações em 2 semestres distintos.', 'Constância', 'bronze', 'active_semesters', 2, 'Repeat2'],
    'semesters 4' => [BadgeKey::Semesters4, 'Jornada Contínua', 'Participe de apresentações em 4 semestres distintos.', 'Constância', 'silver', 'active_semesters', 4, 'Route'],
    'semesters 6' => [BadgeKey::Semesters6, 'Legado EIC', 'Participe de apresentações em 6 semestres distintos.', 'Constância', 'platinum', 'active_semesters', 6, 'Milestone'],
    'first workshop' => [BadgeKey::FirstWorkshop, 'Workshop Concluído', 'Conclua um workshop.', 'Workshops', 'bronze', 'completed_workshops', 1, 'Wrench'],
    'workshops 3' => [BadgeKey::Workshops3, 'Mão na Massa', 'Conclua 3 workshops.', 'Workshops', 'silver', 'completed_workshops', 3, 'Hammer'],
    'workshops 5' => [BadgeKey::Workshops5, 'Mestre de Workshops', 'Conclua 5 workshops.', 'Workshops', 'platinum', 'completed_workshops', 5, 'BadgeCheck'],
    'first evaluation' => [BadgeKey::FirstEvaluation, 'Primeira Impressão', 'Avalie uma apresentação.', 'Contribuição', 'bronze', 'evaluation_count', 1, 'MessageSquare'],
    'evaluations 5' => [BadgeKey::Evaluations5, 'Voz Ativa', 'Avalie 5 apresentações.', 'Contribuição', 'bronze', 'evaluation_count', 5, 'MessagesSquare'],
    'evaluations 10' => [BadgeKey::Evaluations10, 'Feedback que Transforma', 'Avalie 10 apresentações.', 'Contribuição', 'silver', 'evaluation_count', 10, 'MessageCircleMore'],
    'evaluations 25' => [BadgeKey::Evaluations25, 'Conselheiro da Comunidade', 'Avalie 25 apresentações.', 'Contribuição', 'gold', 'evaluation_count', 25, 'HeartHandshake'],
    'feedback champion' => [BadgeKey::FeedbackChampion, 'Compromisso com a Melhoria', 'Participe de pelo menos 10 apresentações e avalie pelo menos 90% delas.', 'Contribuição', 'special', 'feedback_champion', 1, 'Award'],
];

dataset('badge definitions', $badgeDefinitions);
dataset('regular badge thresholds', array_filter(
    $badgeDefinitions,
    fn (array $definition): bool => $definition[0] !== BadgeKey::FeedbackChampion,
));

it('contains every persisted badge exactly once', function () {
    $definitions = app(BadgeCatalog::class)->all();

    expect(array_keys($definitions))->toEqualCanonicalizing(
        array_map(fn (BadgeKey $key): string => $key->value, BadgeKey::cases()),
    )->and($definitions)->toHaveCount(30);
});

it('locks every badge definition to the product catalog', function (
    BadgeKey $key,
    string $name,
    string $description,
    string $category,
    string $tier,
    string $metric,
    int $threshold,
    string $icon,
) {
    $definition = app(BadgeCatalog::class)->all()[$key->value];

    expect($definition->key)->toBe($key)
        ->and($definition->name)->toBe($name)
        ->and($definition->description)->toBe($description)->not->toBeEmpty()
        ->and($definition->category)->toBe($category)
        ->and($definition->tier)->toBe($tier)
        ->and($definition->metric)->toBe($metric)
        ->and($definition->threshold)->toBe($threshold)
        ->and($definition->icon)->toBe($icon);
})->with('badge definitions');

it('uses unique badge names and the configured tier bonuses', function () {
    $definitions = app(BadgeCatalog::class)->all();
    $names = array_map(fn ($definition): string => $definition->name, $definitions);

    expect(array_unique($names))->toHaveCount(30)
        ->and(config('gamification.badge_tier_bonus'))->toBe([
            'bronze' => 25,
            'silver' => 50,
            'gold' => 100,
            'platinum' => 250,
            'special' => 75,
        ]);

    foreach ($definitions as $definition) {
        expect(config("gamification.badge_tier_bonus.{$definition->tier}"))->toBeInt()->toBeGreaterThan(0);
    }
});

it('awards regular badges only at their exact metric threshold', function (
    BadgeKey $key,
    string $name,
    string $description,
    string $category,
    string $tier,
    string $metric,
    int $threshold,
    string $icon,
) {
    $catalog = app(BadgeCatalog::class);

    expect($catalog->earnedBy(GamificationSnapshot::fromMetrics([$metric => $threshold - 1])))
        ->not->toContain($key)
        ->and($catalog->earnedBy(GamificationSnapshot::fromMetrics([$metric => $threshold])))
        ->toContain($key);
})->with('regular badge thresholds');

it('returns zero for absent metrics and supports every catalog metric', function () {
    $snapshot = GamificationSnapshot::fromMetrics([]);
    $metrics = array_unique(array_map(
        fn ($definition): string => $definition->metric,
        app(BadgeCatalog::class)->all(),
    ));

    foreach ($metrics as $metric) {
        expect($snapshot->metric($metric))->toBe(0);
    }
});

it('requires ten attendances and ninety percent evaluation coverage for feedback champion', function (
    int $attendances,
    int $evaluations,
    bool $earned,
) {
    $earnedBadges = app(BadgeCatalog::class)->earnedBy(GamificationSnapshot::fromMetrics([
        'attendance_count' => $attendances,
        'evaluation_count' => $evaluations,
        'feedback_champion' => 1,
    ]));

    expect(in_array(BadgeKey::FeedbackChampion, $earnedBadges, true))->toBe($earned);
})->with([
    'nine attendances at full coverage remain locked' => [9, 9, false],
    'ten attendances below coverage remain locked' => [10, 8, false],
    'ten attendances at exact coverage unlock' => [10, 9, true],
    'twenty attendances below exact coverage remain locked' => [20, 17, false],
    'twenty attendances at exact coverage unlock' => [20, 18, true],
]);
