<?php

use App\Models\AuditLog;
use Illuminate\Support\Facades\Notification;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Notification::fake();
});

it('records an audit log when a backup succeeds', function () {
    event(new BackupWasSuccessful('s3', 'Seminarios'));

    assertDatabaseHas('audit_logs', [
        'event_name' => 'command.database_backup_completed',
        'event_type' => 'system',
    ]);
    expect(AuditLog::where('event_name', 'command.database_backup_completed')->count())->toBe(1);
});

it('records an audit log when a backup fails', function () {
    event(new BackupHasFailed(new Exception('disk unreachable')));

    assertDatabaseHas('audit_logs', [
        'event_name' => 'command.database_backup_failed',
        'event_type' => 'system',
    ]);

    $log = AuditLog::where('event_name', 'command.database_backup_failed')->firstOrFail();
    expect($log->event_data['error'])->toBe('disk unreachable');
});

it('records the disk name when a failed backup carries one', function () {
    event(new BackupHasFailed(new Exception('s3 upload timed out'), 's3', 'Seminarios'));

    $log = AuditLog::where('event_name', 'command.database_backup_failed')->firstOrFail();
    expect($log->event_data['disk'])->toBe('s3');
    expect($log->event_data['error'])->toBe('s3 upload timed out');
});
