<?php

use Illuminate\Console\Scheduling\Schedule;

function backupScheduledEvent(string $command)
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, $command));
}

describe('Scheduled database backup', function () {
    it('schedules the database backup', function () {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:run --only-db')
            ->assertExitCode(0);
    });

    it('schedules the backup cleanup', function () {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:clean')
            ->assertExitCode(0);
    });

    it('runs the backup tasks when the feature is enabled', function () {
        config(['features.database_backup.enabled' => true]);

        expect(backupScheduledEvent('backup:run')->filtersPass(app()))->toBeTrue()
            ->and(backupScheduledEvent('backup:clean')->filtersPass(app()))->toBeTrue();
    });

    it('skips the backup tasks when the feature is disabled', function () {
        config(['features.database_backup.enabled' => false]);

        expect(backupScheduledEvent('backup:run')->filtersPass(app()))->toBeFalse()
            ->and(backupScheduledEvent('backup:clean')->filtersPass(app()))->toBeFalse();
    });
});
