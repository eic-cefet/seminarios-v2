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
