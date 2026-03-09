<?php

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;

describe('AuditLog Model', function () {
    describe('record', function () {
        it('creates an audit log entry with correct fields', function () {
            $user = actingAsUser();
            $seminar = Seminar::factory()->create();

            $log = AuditLog::record(
                AuditEvent::PresenceRegistered,
                auditable: $seminar,
                eventData: ['seminar_id' => $seminar->id],
            );

            expect($log)->toBeInstanceOf(AuditLog::class)
                ->and($log->user_id)->toBe($user->id)
                ->and($log->event_name)->toBe('presence.registered')
                ->and($log->event_type)->toBe(AuditEventType::Manual)
                ->and($log->auditable_type)->toBe($seminar->getMorphClass())
                ->and($log->auditable_id)->toBe($seminar->id)
                ->and($log->event_data)->toBe(['seminar_id' => $seminar->id]);
        });

        it('captures authenticated user automatically', function () {
            $user = actingAsUser();

            $log = AuditLog::record(AuditEvent::UserLogin, auditable: $user);

            expect($log->user_id)->toBe($user->id);
        });

        it('allows explicit user_id override', function () {
            $user = User::factory()->create();

            $log = AuditLog::record(AuditEvent::UserLogout, auditable: $user, userId: $user->id);

            expect($log->user_id)->toBe($user->id);
        });

        it('works with nullable user for unauthenticated events', function () {
            $log = AuditLog::record(AuditEvent::UserForgotPassword, eventData: [
                'email' => 'test@example.com',
            ]);

            expect($log->user_id)->toBeNull();
        });

        it('captures origin from context', function () {
            Context::add('audit.origin', 'TestController@index');

            $log = AuditLog::record(AuditEvent::ReportGenerated);

            expect($log->origin)->toBe('TestController@index');
        });

        it('uses system event type when specified', function () {
            $log = AuditLog::record(
                AuditEvent::CertificateGenerated,
                AuditEventType::System,
            );

            expect($log->event_type)->toBe(AuditEventType::System);
        });
    });

    describe('recordModelEvent', function () {
        it('creates an audit log with system event type', function () {
            $seminar = Seminar::factory()->create();

            $log = AuditLog::recordModelEvent('seminar.updated', $seminar, [
                'old_values' => ['name' => 'Old'],
                'new_values' => ['name' => 'New'],
            ]);

            expect($log->event_name)->toBe('seminar.updated')
                ->and($log->event_type)->toBe(AuditEventType::System)
                ->and($log->auditable_type)->toBe($seminar->getMorphClass())
                ->and($log->auditable_id)->toBe($seminar->id);
        });
    });

    describe('relationships', function () {
        it('belongs to a user', function () {
            $user = User::factory()->create();
            $log = AuditLog::factory()->byUser($user)->create();

            expect($log->user)->toBeInstanceOf(User::class)
                ->and($log->user->id)->toBe($user->id);
        });

        it('morphs to an auditable model', function () {
            $seminar = Seminar::factory()->create();
            $log = AuditLog::factory()->forModel($seminar)->create();

            expect($log->auditable)->toBeInstanceOf(Seminar::class)
                ->and($log->auditable->id)->toBe($seminar->id);
        });
    });

    describe('scopes', function () {
        it('filters by model with forModel scope', function () {
            $seminar = Seminar::factory()->create();

            AuditLog::query()->delete(); // clear logs from factory setup

            AuditLog::factory()->forModel($seminar)->create();
            AuditLog::factory()->create();

            $results = AuditLog::forModel($seminar)->get();

            expect($results)->toHaveCount(1);
        });

        it('filters by event name with forEvent scope', function () {
            AuditLog::query()->delete();

            AuditLog::factory()->create(['event_name' => 'user.login']);
            AuditLog::factory()->create(['event_name' => 'user.logout']);

            // Filter out logs from factory user creation
            $results = AuditLog::forEvent('user.login')->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->event_name)->toBe('user.login');
        });

        it('filters by user with forUser scope', function () {
            $user = User::factory()->create();

            AuditLog::query()->delete();

            AuditLog::factory()->byUser($user)->create();
            AuditLog::factory()->create(['user_id' => null]);

            $results = AuditLog::forUser($user->id)->get();

            expect($results)->toHaveCount(1);
        });

        it('filters by date range with between scope', function () {
            AuditLog::query()->delete();

            $user = User::factory()->create();
            AuditLog::query()->delete(); // clear user creation logs

            AuditLog::factory()->byUser($user)->create(['created_at' => Carbon::parse('2026-01-01'), 'event_name' => 'test.a']);
            AuditLog::factory()->byUser($user)->create(['created_at' => Carbon::parse('2026-06-15'), 'event_name' => 'test.b']);
            AuditLog::factory()->byUser($user)->create(['created_at' => Carbon::parse('2026-12-31'), 'event_name' => 'test.c']);

            $results = AuditLog::between(
                Carbon::parse('2026-01-01'),
                Carbon::parse('2026-06-30'),
            )->get();

            expect($results)->toHaveCount(2);
        });
    });

    describe('casts', function () {
        it('casts event_data as array', function () {
            $log = AuditLog::factory()->create(['event_data' => ['key' => 'value']]);

            expect($log->fresh()->event_data)->toBe(['key' => 'value']);
        });

        it('casts event_type as AuditEventType enum', function () {
            $log = AuditLog::factory()->create(['event_type' => AuditEventType::Manual]);

            expect($log->fresh()->event_type)->toBe(AuditEventType::Manual);
        });
    });
});
