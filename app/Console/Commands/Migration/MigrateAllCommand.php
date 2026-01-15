<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;

class MigrateAllCommand extends Command
{
    protected $signature = 'migrate:legacy
                            {--fresh : Run fresh migrations first}
                            {--seed : Seed roles}';

    protected $description = 'Run all legacy data migrations in order';

    public function handle(): int
    {
        $this->info('Starting full legacy migration...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->call('migrate:fresh');
        }

        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => 'RolesSeeder']);
        }

        $commands = [
            'migrate:seminar-locations',
            'migrate:seminar-types',
            'migrate:subjects',
            'migrate:workshops',
            'migrate:courses',
            'migrate:users',
            'migrate:seminars',
            'migrate:seminar-speakers',
            'migrate:seminar-subjects',
            'migrate:registrations',
            'migrate:presence-links',
            'migrate:ratings',
        ];

        foreach ($commands as $command) {
            $this->info("Running: {$command}");
            $this->call($command);
            $this->newLine();
        }

        $this->info('Legacy migration completed!');

        return Command::SUCCESS;
    }
}
