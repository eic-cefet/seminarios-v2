<?php

use App\Enums\CourseRole;
use App\Enums\CourseSituation;
use App\Models\Course;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Models\UserStudentData;

describe('GET /api/profile', function () {
    it('returns authenticated user profile', function () {
        $user = actingAsUser();

        $response = $this->getJson('/api/profile');

        $response->assertSuccessful()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', $user->name)
            ->assertJsonPath('user.email', $user->email);
    });

    it('returns profile with student data when present', function () {
        $user = actingAsUser();
        $course = Course::factory()->create();

        UserStudentData::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_situation' => CourseSituation::Studying,
            'course_role' => CourseRole::Aluno,
        ]);

        $response = $this->getJson('/api/profile');

        $response->assertSuccessful()
            ->assertJsonPath('user.student_data.course_situation', 'studying')
            ->assertJsonPath('user.student_data.course_role', 'Aluno')
            ->assertJsonPath('user.student_data.course.id', $course->id);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/profile');

        $response->assertUnauthorized();
    });
});

describe('PUT /api/profile', function () {
    it('updates user profile', function () {
        $user = actingAsUser();

        $response = $this->putJson('/api/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Perfil atualizado com sucesso.')
            ->assertJsonPath('user.name', 'Updated Name');

        expect($user->fresh()->name)->toBe('Updated Name');
    });

    it('clears email verification when email changes', function () {
        $user = actingAsUser();
        $user->email_verified_at = now();
        $user->save();

        $response = $this->putJson('/api/profile', [
            'name' => $user->name,
            'email' => 'newemail@example.com',
        ]);

        $response->assertSuccessful();
        expect($user->fresh()->email_verified_at)->toBeNull();
    });

    it('validates required fields', function () {
        actingAsUser();

        $response = $this->putJson('/api/profile', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    });

    it('validates email uniqueness', function () {
        $existingUser = User::factory()->create();
        actingAsUser();

        $response = $this->putJson('/api/profile', [
            'name' => 'Test',
            'email' => $existingUser->email,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->putJson('/api/profile', [
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $response->assertUnauthorized();
    });
});

describe('PUT /api/profile/student-data', function () {
    it('creates student data when none exists', function () {
        $user = actingAsUser();
        $course = Course::factory()->create();

        $response = $this->putJson('/api/profile/student-data', [
            'course_id' => $course->id,
            'course_situation' => CourseSituation::Studying,
            'course_role' => CourseRole::Aluno,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Dados atualizados com sucesso.')
            ->assertJsonPath('user.student_data.course_situation', 'studying');

        expect($user->fresh()->studentData)->not->toBeNull();
    });

    it('updates existing student data', function () {
        $user = actingAsUser();
        $course = Course::factory()->create();

        UserStudentData::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_situation' => CourseSituation::Studying,
        ]);

        $response = $this->putJson('/api/profile/student-data', [
            'course_id' => $course->id,
            'course_situation' => CourseSituation::Graduated,
            'course_role' => CourseRole::Outro,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('user.student_data.course_situation', 'graduated');
    });

    it('returns 401 for unauthenticated user', function () {
        $course = Course::factory()->create();

        $response = $this->putJson('/api/profile/student-data', [
            'course_id' => $course->id,
            'course_situation' => CourseSituation::Studying,
            'course_role' => CourseRole::Aluno,
        ]);

        $response->assertUnauthorized();
    });
});

describe('PUT /api/profile/password', function () {
    it('updates password with correct current password', function () {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $this->actingAs($user);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Senha atualizada com sucesso.');
    });

    it('rejects incorrect current password', function () {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $this->actingAs($user);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    });

    it('validates password confirmation', function () {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $this->actingAs($user);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnauthorized();
    });
});

describe('GET /api/profile/registrations', function () {
    it('returns user registrations', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->getJson('/api/profile/registrations');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.seminar.id', $seminar->id);
    });

    it('returns paginated results', function () {
        $user = actingAsUser();

        Registration::factory()->count(15)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/profile/registrations?per_page=10');

        $response->assertSuccessful()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/profile/registrations');

        $response->assertUnauthorized();
    });
});

describe('GET /api/profile/pending-evaluations', function () {
    it('returns seminars attended but not rated', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $response = $this->getJson('/api/profile/pending-evaluations');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.seminar.id', $seminar->id);
    });

    it('excludes seminars already rated', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        Rating::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->getJson('/api/profile/pending-evaluations');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('excludes seminars older than 30 days', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(40),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $response = $this->getJson('/api/profile/pending-evaluations');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('excludes seminars where user was not present', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => false,
        ]);

        $response = $this->getJson('/api/profile/pending-evaluations');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/profile/pending-evaluations');

        $response->assertUnauthorized();
    });
});

describe('POST /api/profile/ratings/{seminar}', function () {
    it('submits a rating for attended seminar', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $response = $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 5,
            'comment' => 'Excellent seminar!',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Avaliação enviada com sucesso!')
            ->assertJsonPath('rating.score', 5);

        expect(Rating::where('user_id', $user->id)->where('seminar_id', $seminar->id)->exists())->toBeTrue();
    });

    it('rejects rating for seminar not attended', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        $response = $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 5,
        ]);

        $response->assertForbidden();
    });

    it('rejects rating for seminar older than 30 days', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(40),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $response = $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 5,
        ]);

        $response->assertForbidden();
    });

    it('rejects duplicate rating', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        Rating::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 5,
        ]);

        $response->assertConflict();
    });

    it('validates score range', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $response = $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['score']);
    });

    it('returns 401 for unauthenticated user', function () {
        $seminar = Seminar::factory()->create();

        $response = $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 5,
        ]);

        $response->assertUnauthorized();
    });
});

describe('GET /api/profile/certificates', function () {
    it('returns user certificates', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'certificate_code' => 'ABC123',
        ]);

        $response = $this->getJson('/api/profile/certificates');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.certificate_code', 'ABC123');
    });

    it('excludes registrations without certificate', function () {
        $user = actingAsUser();

        Registration::factory()->create([
            'user_id' => $user->id,
            'present' => true,
            'certificate_code' => null,
        ]);

        $response = $this->getJson('/api/profile/certificates');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('excludes registrations where user was not present', function () {
        $user = actingAsUser();

        Registration::factory()->create([
            'user_id' => $user->id,
            'present' => false,
            'certificate_code' => 'ABC123',
        ]);

        $response = $this->getJson('/api/profile/certificates');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/profile/certificates');

        $response->assertUnauthorized();
    });
});
