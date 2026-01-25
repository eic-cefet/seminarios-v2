<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| These commands run automatically via the Laravel scheduler.
| Ensure `php artisan schedule:run` is called every minute via cron.
|
*/

// Send seminar reminders daily at 10:00 AM (for seminars happening the next day)
Schedule::command('reminders:seminars')
    ->dailyAt('10:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer();

// Send evaluation reminders daily at 14:00 (for seminars that ended 2+ days ago)
Schedule::command('reminders:evaluations')
    ->dailyAt('14:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer();

// Process pending certificates every hour (generate and send for confirmed presences)
Schedule::command('certificates:process-pending')
    ->hourly()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Maintenance Commands
|--------------------------------------------------------------------------
*/

// Flush expired password reset tokens daily at 3:00 AM
Schedule::command('auth:clear-resets')
    ->dailyAt('03:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer();

// Prune failed queue jobs older than 7 days, daily at 3:15 AM
Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('03:30')
    ->timezone('America/Sao_Paulo')
    ->onOneServer();

// Prune stale queue batch entries older than 48 hours, daily at 3:45 AM
Schedule::command('queue:prune-batches --hours=48')
    ->dailyAt('03:45')
    ->timezone('America/Sao_Paulo')
    ->onOneServer();

// Clear scheduler mutex cache files weekly on Sunday at 4:00 AM
Schedule::command('schedule:clear-cache')
    ->weeklyOn(0, '04:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer();
