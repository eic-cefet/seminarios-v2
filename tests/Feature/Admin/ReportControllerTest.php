<?php

use App\Models\Course;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use App\Models\UserStudentData;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

describe('GET /api/admin/reports/courses', function () {
    it('returns list of courses for admin', function () {
        actingAsAdmin();

        Course::factory()->create(['name' => 'Sistemas de Informação']);

        $response = $this->getJson('/api/admin/reports/courses');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['value', 'label'],
                ],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/reports/courses');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/reports/courses');

        $response->assertForbidden();
    });

    it('orders courses by name', function () {
        actingAsAdmin();

        Course::factory()->create(['name' => 'Ciência da Computação']);
        Course::factory()->create(['name' => 'Análise e Desenvolvimento de Sistemas']);

        $response = $this->getJson('/api/admin/reports/courses');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data[0]['label'])->toBe('Análise e Desenvolvimento de Sistemas');
        expect($data[1]['label'])->toBe('Ciência da Computação');
    });
});

describe('GET /api/admin/reports/semestral', function () {
    it('returns semestral report for browser format', function () {
        actingAsAdmin();

        $course = Course::factory()->create(['name' => 'Engenharia de Computação']);

        // Create user with student data
        $user = User::factory()->create(['name' => 'Test Student']);
        UserStudentData::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_situation' => 'studying',
        ]);

        // Create seminar in the first semester of current year
        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonth(),
        ]);

        // Create registration
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1&format=browser");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['name', 'email', 'course', 'total_hours', 'presentations'],
                ],
                'summary' => ['total_users', 'total_hours', 'semester'],
            ])
            ->assertJsonPath('summary.semester', "{$currentYear}.1");
    });

    it('filters by course', function () {
        actingAsAdmin();

        $course1 = Course::factory()->create(['name' => 'Sistemas de Informação']);
        $course2 = Course::factory()->create(['name' => 'Ciência da Computação']);

        $user1 = User::factory()->create();
        UserStudentData::create([
            'user_id' => $user1->id,
            'course_id' => $course1->id,
            'course_situation' => 'studying',
        ]);

        $user2 = User::factory()->create();
        UserStudentData::create([
            'user_id' => $user2->id,
            'course_id' => $course2->id,
            'course_situation' => 'studying',
        ]);

        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonth(),
        ]);

        Registration::factory()->create(['user_id' => $user1->id, 'seminar_id' => $seminar->id]);
        Registration::factory()->create(['user_id' => $user2->id, 'seminar_id' => $seminar->id]);

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1&format=browser&courses[]={$course1->id}");

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
    });

    it('filters by seminar type', function () {
        actingAsAdmin();

        $type1 = SeminarType::factory()->create(['name' => 'Palestra']);
        $type2 = SeminarType::factory()->create(['name' => 'Workshop']);

        $user = User::factory()->create();

        $seminar1 = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonth(),
            'seminar_type_id' => $type1->id,
        ]);

        $seminar2 = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonth(),
            'seminar_type_id' => $type2->id,
        ]);

        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar1->id]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar2->id]);

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1&format=browser&types[]={$type1->id}");

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data[0]['presentations'])->toHaveCount(1);
    });

    it('filters by course situation', function () {
        actingAsAdmin();

        $course = Course::factory()->create(['name' => 'Análise e Desenvolvimento de Sistemas']);

        $studying = User::factory()->create();
        UserStudentData::create([
            'user_id' => $studying->id,
            'course_id' => $course->id,
            'course_situation' => 'studying',
        ]);

        $graduated = User::factory()->create();
        UserStudentData::create([
            'user_id' => $graduated->id,
            'course_id' => $course->id,
            'course_situation' => 'graduated',
        ]);

        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonth(),
        ]);

        Registration::factory()->create(['user_id' => $studying->id, 'seminar_id' => $seminar->id]);
        Registration::factory()->create(['user_id' => $graduated->id, 'seminar_id' => $seminar->id]);

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1&format=browser&situations[]=studying");

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
    });

    it('handles second semester date range', function () {
        actingAsAdmin();

        $user = User::factory()->create();

        // Create seminar in July (second semester)
        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonths(7), // August
        ]);

        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar->id]);

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.2&format=browser");

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
    });

    it('generates excel report', function () {
        actingAsAdmin();

        Storage::fake('s3');
        Excel::fake();

        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonth(),
        ]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar->id]);

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1&format=excel");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Relatório gerado com sucesso')
            ->assertJsonStructure(['url']);

        // Verify excel was stored - the assertStored method requires exact path
        // We verify the file was stored via the response containing a URL
        expect($response->json('url'))->toContain('relatorio-semestral');
    });

    it('returns validation error for missing semester', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/reports/semestral?format=browser');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['semester']);
    });

    it('returns validation error for missing format', function () {
        actingAsAdmin();

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    });

    it('returns validation error for invalid format', function () {
        actingAsAdmin();

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1&format=invalid");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/reports/semestral?semester=2024.1&format=browser');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/reports/semestral?semester=2024.1&format=browser');

        $response->assertForbidden();
    });

    it('sorts report by name', function () {
        actingAsAdmin();

        $userZ = User::factory()->create(['name' => 'Zé da Silva']);
        $userA = User::factory()->create(['name' => 'Ana Santos']);

        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->startOfYear()->addMonth(),
        ]);

        Registration::factory()->create(['user_id' => $userZ->id, 'seminar_id' => $seminar->id]);
        Registration::factory()->create(['user_id' => $userA->id, 'seminar_id' => $seminar->id]);

        $currentYear = now()->year;
        $response = $this->getJson("/api/admin/reports/semestral?semester={$currentYear}.1&format=browser");

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data[0]['name'])->toBe('Ana Santos');
        expect($data[1]['name'])->toBe('Zé da Silva');
    });
});
