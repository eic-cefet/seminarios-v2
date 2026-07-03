<?php

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
});
