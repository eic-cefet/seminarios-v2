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
        $user->name = 'Maria Silva';
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

    it('returns profile with student data but no course after update', function () {
        $user = actingAsUser();

        UserStudentData::factory()->create([
            'user_id' => $user->id,
            'course_id' => null, // No course associated
            'course_situation' => CourseSituation::Studying,
            'course_role' => CourseRole::Aluno,
        ]);

        $response = $this->putJson('/api/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.student_data.course_situation', 'studying')
            ->assertJsonPath('user.student_data.course', null);
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

        $response->assertForbidden()
            ->assertJsonFragment(['message' => 'Você não participou desta apresentação.']);
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

        $response->assertForbidden()
            ->assertJsonFragment(['message' => 'O prazo para avaliar esta apresentação expirou.']);
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

        $response->assertConflict()
            ->assertJsonFragment(['message' => 'Você já avaliou esta apresentação.']);
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

    it('returns custom Portuguese validation message for missing score', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $response = $this->postJson("/api/profile/ratings/{$seminar->id}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['score' => 'A nota é obrigatória.']);
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

describe('GET /api/profile/calendar-feed', function () {
    it('lazily generates and persists the token', function () {
        $user = actingAsUser();

        expect($user->calendar_feed_token)->toBeNull();

        $response = $this->getJson('/api/profile/calendar-feed');

        $response->assertSuccessful();
        $user->refresh();
        expect($user->calendar_feed_token)->not->toBeNull();
        expect($response->json('data.personal_url'))->toContain('/calendar/personal/'.$user->calendar_feed_token);
        expect($response->json('data.public_url'))->toContain('/calendar/seminars.ics');
    });

    it('returns the same url on subsequent calls', function () {
        actingAsUser();

        $first = $this->getJson('/api/profile/calendar-feed')->json('data.personal_url');
        $second = $this->getJson('/api/profile/calendar-feed')->json('data.personal_url');

        expect($second)->toBe($first);
    });

    it('returns 401 for unauthenticated user', function () {
        $this->getJson('/api/profile/calendar-feed')->assertUnauthorized();
    });
});

describe('POST /api/profile/calendar-feed/rotate', function () {
    it('replaces the token and invalidates the old feed url', function () {
        $user = actingAsUser(User::factory()->create(['calendar_feed_token' => str_repeat('a', 48)]));

        $response = $this->postJson('/api/profile/calendar-feed/rotate');

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Novo link gerado com sucesso.');

        $user->refresh();
        expect($user->calendar_feed_token)->not->toBe(str_repeat('a', 48));
        expect($response->json('data.personal_url'))->toContain($user->calendar_feed_token);

        $this->get('/calendar/personal/'.str_repeat('a', 48).'.ics')->assertNotFound();
        $this->get('/calendar/personal/'.$user->calendar_feed_token.'.ics')->assertOk();
    });

    it('records an audit event', function () {
        actingAsUser();

        $this->postJson('/api/profile/calendar-feed/rotate')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'event_name' => 'calendar_feed.token_rotated',
        ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $this->postJson('/api/profile/calendar-feed/rotate')->assertUnauthorized();
    });
});

describe('GET /api/profile/schedule', function () {
    it('returns only upcoming active registrations ordered by date ascending', function () {
        $user = actingAsUser();

        $later = Seminar::factory()->create(['active' => true, 'scheduled_at' => now()->addDays(10)]);
        $sooner = Seminar::factory()->create(['active' => true, 'scheduled_at' => now()->addDays(2)]);
        $past = Seminar::factory()->past()->create(['active' => true]);
        $inactive = Seminar::factory()->create(['active' => false, 'scheduled_at' => now()->addDays(5)]);

        foreach ([$later, $sooner, $past, $inactive] as $seminar) {
            Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar->id]);
        }

        $response = $this->getJson('/api/profile/schedule');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.seminar.id', $sooner->id)
            ->assertJsonPath('data.1.seminar.id', $later->id);
    });

    it('excludes other users registrations', function () {
        actingAsUser();
        $other = User::factory()->create();
        Registration::factory()->create(['user_id' => $other->id]);

        $this->getJson('/api/profile/schedule')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns paginated results', function () {
        $user = actingAsUser();
        Registration::factory()->count(15)->create(['user_id' => $user->id]);

        $past = Seminar::factory()->past()->create(['active' => true]);
        $inactive = Seminar::factory()->create(['active' => false, 'scheduled_at' => now()->addDays(5)]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $past->id]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $inactive->id]);

        $response = $this->getJson('/api/profile/schedule?per_page=10');

        $response->assertSuccessful()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15);
    });

    it('returns 401 for unauthenticated user', function () {
        $this->getJson('/api/profile/schedule')->assertUnauthorized();
    });
});
