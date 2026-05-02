<?php

use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\PresenceLink;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\SocialIdentity;
use App\Models\Subject;
use App\Models\User;

describe('Auditable Trait', function () {
    describe('model creation', function () {
        it('logs a created event when a model is created', function () {
            $subject = Subject::factory()->create(['name' => 'Laravel']);

            $log = AuditLog::where('event_name', 'subject.created')
                ->where('auditable_id', $subject->id)
                ->first();

            expect($log)->not->toBeNull()
                ->and($log->event_type)->toBe(AuditEventType::System)
                ->and($log->event_data['name'])->toBe('Laravel');
        });
    });

    describe('model update', function () {
        it('logs an updated event with old and new values', function () {
            $subject = Subject::factory()->create(['name' => 'Old Name']);

            AuditLog::query()->delete(); // clear creation log

            $subject->update(['name' => 'New Name']);

            $log = AuditLog::where('event_name', 'subject.updated')->first();

            expect($log)->not->toBeNull()
                ->and($log->event_data['old_values']['name'])->toBe('Old Name')
                ->and($log->event_data['new_values']['name'])->toBe('New Name');
        });

        it('skips logging when only updated_at changes', function () {
            $subject = Subject::factory()->create();

            AuditLog::query()->delete();

            $subject->touch();

            $log = AuditLog::where('event_name', 'subject.updated')->first();

            expect($log)->toBeNull();
        });
    });

    describe('model deletion', function () {
        it('logs a deleted event for hard deletes', function () {
            $seminarType = SeminarType::factory()->create();

            AuditLog::query()->delete();

            $seminarType->delete();

            $log = AuditLog::where('event_name', 'seminar_type.deleted')->first();

            expect($log)->not->toBeNull()
                ->and($log->event_data['name'])->toBe($seminarType->name);
        });

        it('logs a soft_deleted event for soft deletes', function () {
            $seminar = Seminar::factory()->create();

            AuditLog::query()->delete();

            $seminar->delete();

            $log = AuditLog::where('event_name', 'seminar.soft_deleted')->first();

            expect($log)->not->toBeNull()
                ->and($log->auditable_id)->toBe($seminar->id);
        });

        it('logs a force_deleted event for force deletes', function () {
            $seminar = Seminar::factory()->create();

            AuditLog::query()->delete();

            $seminar->forceDelete();

            $log = AuditLog::where('event_name', 'seminar.force_deleted')->first();

            expect($log)->not->toBeNull();
        });
    });

    describe('model restoration', function () {
        it('logs a restored event for soft-deleted models', function () {
            $seminar = Seminar::factory()->create();
            $seminar->delete();

            AuditLog::query()->delete();

            $seminar->restore();

            $log = AuditLog::where('event_name', 'seminar.restored')->first();

            expect($log)->not->toBeNull()
                ->and($log->auditable_id)->toBe($seminar->id);
        });
    });

    describe('sensitive field exclusion', function () {
        it('excludes globally excluded fields from event data', function () {
            $user = User::factory()->create([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'secret123',
            ]);

            $log = AuditLog::where('event_name', 'user.created')
                ->where('auditable_id', $user->id)
                ->first();

            expect($log)->not->toBeNull()
                ->and($log->event_data)->not->toHaveKey('password')
                ->and($log->event_data)->not->toHaveKey('remember_token')
                ->and($log->event_data)->toHaveKey('name')
                ->and($log->event_data)->toHaveKey('email');
        });

        it('excludes model-specific auditExclude fields', function () {
            $user = User::factory()->create();
            $identity = SocialIdentity::create([
                'user_id' => $user->id,
                'provider' => 'github',
                'provider_id' => '12345',
                'token' => 'secret-token',
                'refresh_token' => 'secret-refresh',
            ]);

            $log = AuditLog::where('event_name', 'social_identity.created')
                ->where('auditable_id', $identity->id)
                ->first();

            expect($log)->not->toBeNull()
                ->and($log->event_data)->not->toHaveKey('token')
                ->and($log->event_data)->not->toHaveKey('refresh_token')
                ->and($log->event_data)->toHaveKey('provider')
                ->and($log->event_data)->toHaveKey('provider_id');
        });
    });

    describe('compatibility', function () {
        it('works alongside PresenceLink boot method', function () {
            $seminar = Seminar::factory()->create();
            $link = PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

            // UUID should still be generated by PresenceLink::boot()
            expect($link->uuid)->not->toBeNull()->not->toBeEmpty();

            // Audit log should be created by Auditable trait
            $log = AuditLog::where('event_name', 'presence_link.created')
                ->where('auditable_id', $link->id)
                ->first();

            expect($log)->not->toBeNull();
        });

        it('does not audit the AuditLog model itself', function () {
            $initialCount = AuditLog::count();

            AuditLog::factory()->create();

            // Only the one we created + any from factory user creation, no self-audit
            $selfAuditLogs = AuditLog::where('event_name', 'audit_log.created')->count();
            expect($selfAuditLogs)->toBe(0);
        });
    });

    describe('event name generation', function () {
        it('converts CamelCase model names to snake_case', function () {
            $seminar = Seminar::factory()->create();
            $link = PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

            $log = AuditLog::where('auditable_id', $link->id)
                ->where('auditable_type', $link->getMorphClass())
                ->first();

            expect($log->event_name)->toBe('presence_link.created');
        });

        it('generates correct event names for soft delete models', function () {
            $course = Course::factory()->create();

            AuditLog::query()->delete();

            $course->delete();

            $log = AuditLog::where('auditable_id', $course->id)->first();

            expect($log->event_name)->toBe('course.soft_deleted');
        });
    });
});
