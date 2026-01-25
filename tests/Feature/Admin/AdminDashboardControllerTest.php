<?php

use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\Subject;
use App\Models\User;

describe('GET /admin/dashboard/stats', function () {
    it('returns dashboard stats for admin user', function () {
        actingAsAdmin();

        // Create test data
        $location = SeminarLocation::factory()->create(['max_vacancies' => 10]);
        $upcomingSeminar = Seminar::factory()->upcoming()->create([
            'active' => true,
            'seminar_location_id' => $location->id,
        ]);
        $pastSeminar = Seminar::factory()->past()->create(['active' => true]);

        $user = User::factory()->create();
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $upcomingSeminar->id,
        ]);

        Rating::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $pastSeminar->id,
        ]);

        Subject::factory()->count(2)->create();

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'upcomingSeminars',
                    'latestRatings',
                    'nearCapacity',
                    'latestRegistrations',
                    'counts' => ['users', 'seminars', 'registrations', 'subjects'],
                ],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertForbidden();
    });

    it('returns near capacity seminars correctly', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create(['max_vacancies' => 10]);
        $seminar = Seminar::factory()->upcoming()->create([
            'active' => true,
            'seminar_location_id' => $location->id,
        ]);

        // Create 8 registrations (80% capacity)
        Registration::factory()->count(8)->create([
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertSuccessful();

        $nearCapacity = $response->json('data.nearCapacity');
        expect($nearCapacity)->toHaveCount(1);
    });
});
