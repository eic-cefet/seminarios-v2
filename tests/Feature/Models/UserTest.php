<?php

use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SocialIdentity;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Models\UserStudentData;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

describe('User Model', function () {
    describe('relationships', function () {
        it('has one student data', function () {
            $user = User::factory()->create();
            $studentData = UserStudentData::factory()->create(['user_id' => $user->id]);

            expect($user->studentData)->toBeInstanceOf(UserStudentData::class);
            expect($user->studentData->id)->toBe($studentData->id);
        });

        it('has one speaker data', function () {
            $user = User::factory()->create();
            $speakerData = UserSpeakerData::factory()->create(['user_id' => $user->id]);

            expect($user->speakerData)->toBeInstanceOf(UserSpeakerData::class);
            expect($user->speakerData->id)->toBe($speakerData->id);
        });

        it('has many registrations', function () {
            $user = User::factory()->create();
            Registration::factory()->count(3)->create(['user_id' => $user->id]);

            expect($user->registrations)->toHaveCount(3);
            expect($user->registrations->first())->toBeInstanceOf(Registration::class);
        });

        it('belongs to many seminars as speaker', function () {
            $user = User::factory()->create();
            $seminars = Seminar::factory()->count(2)->create();

            $user->seminarsAsSpeaker()->attach($seminars->pluck('id'));

            expect($user->seminarsAsSpeaker)->toHaveCount(2);
            expect($user->seminarsAsSpeaker->first())->toBeInstanceOf(Seminar::class);
        });

        it('has many ratings', function () {
            $user = User::factory()->create();
            $seminars = Seminar::factory()->count(2)->create();

            foreach ($seminars as $seminar) {
                Rating::factory()->create([
                    'user_id' => $user->id,
                    'seminar_id' => $seminar->id,
                ]);
            }

            expect($user->ratings)->toHaveCount(2);
            expect($user->ratings->first())->toBeInstanceOf(Rating::class);
        });

        it('has many social identities', function () {
            $user = User::factory()->create();

            SocialIdentity::create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_id' => 'google-123',
            ]);
            SocialIdentity::create([
                'user_id' => $user->id,
                'provider' => 'github',
                'provider_id' => 'github-456',
            ]);

            expect($user->socialIdentities)->toHaveCount(2);
            expect($user->socialIdentities->first())->toBeInstanceOf(SocialIdentity::class);
        });
    });

    describe('role methods', function () {
        beforeEach(function () {
            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        });

        it('returns true for isAdmin when user has admin role', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');

            expect($user->isAdmin())->toBeTrue();
        });

        it('returns false for isAdmin when user does not have admin role', function () {
            $user = User::factory()->create();

            expect($user->isAdmin())->toBeFalse();
        });

        it('returns true for isTeacher when user has teacher role', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');

            expect($user->isTeacher())->toBeTrue();
        });

        it('returns false for isTeacher when user does not have teacher role', function () {
            $user = User::factory()->create();

            expect($user->isTeacher())->toBeFalse();
        });

        it('returns true for isUser when user has user role', function () {
            $user = User::factory()->create();
            $user->assignRole('user');

            expect($user->isUser())->toBeTrue();
        });

        it('returns false for isUser when user does not have user role', function () {
            $user = User::factory()->create();

            expect($user->isUser())->toBeFalse();
        });
    });

    describe('password reset', function () {
        it('sends password reset notification', function () {
            Notification::fake();

            $user = User::factory()->create();
            $user->sendPasswordResetNotification('test-token');

            Notification::assertSentTo($user, ResetPassword::class);
        });
    });

    describe('attributes', function () {
        it('has fillable attributes', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'username' => 'johndoe',
            ]);

            expect($user->name)->toBe('John Doe');
            expect($user->email)->toBe('john@example.com');
            expect($user->username)->toBe('johndoe');
        });

        it('hashes password automatically', function () {
            $user = User::factory()->create([
                'password' => 'plain-password',
            ]);

            expect($user->password)->not->toBe('plain-password');
        });

        it('casts email_verified_at to datetime', function () {
            $user = User::factory()->create([
                'email_verified_at' => '2024-01-15 10:30:00',
            ]);

            expect($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('soft deletes', function () {
        it('soft deletes user', function () {
            $user = User::factory()->create();
            $user->delete();

            expect($user->trashed())->toBeTrue();
            expect(User::withTrashed()->find($user->id))->not->toBeNull();
        });

        it('can restore soft deleted user', function () {
            $user = User::factory()->create();
            $user->delete();
            $user->restore();

            expect($user->trashed())->toBeFalse();
        });
    });
});
