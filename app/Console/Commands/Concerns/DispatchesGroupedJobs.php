<?php

namespace App\Console\Commands\Concerns;

use Closure;
use Illuminate\Support\Collection;

trait DispatchesGroupedJobs
{
    /**
     * Group registrations by user and dispatch a job for each user.
     *
     * @param  Collection  $registrations  Registrations with 'user' relationship loaded
     * @param  class-string  $jobClass  Job class that accepts (User, Collection<int>) constructor
     * @param  Closure|null  $jobFactory  Optional factory invoked as fn(User, Collection<int>) => job instance
     * @return int Number of jobs dispatched
     */
    protected function dispatchGroupedByUser(Collection $registrations, string $jobClass, string $userLabel = 'users', ?Closure $jobFactory = null): int
    {
        $grouped = $registrations->groupBy('user_id');

        $this->info("Found {$grouped->count()} {$userLabel}.");

        $dispatched = 0;

        foreach ($grouped as $userRegistrations) {
            $user = $userRegistrations->first()->user;

            if (! $user) {
                continue;
            }

            $registrationIds = $userRegistrations->pluck('id');

            $job = $jobFactory !== null
                ? $jobFactory($user, $registrationIds)
                : new $jobClass($user, $registrationIds);

            if ($this->option('sync')) {
                $job->handle();
            } else {
                dispatch($job);
            }

            $dispatched++;

            $seminarCount = $userRegistrations->count();
            $this->line("  - {$user->email}: {$seminarCount} seminar(s)");
        }

        return $dispatched;
    }
}
