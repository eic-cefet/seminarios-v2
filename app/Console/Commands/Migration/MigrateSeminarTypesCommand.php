<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSeminarTypesCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:seminar-types';
    protected $description = 'Migrate seminar types from legacy database';

    public function handle(): int
    {
        $this->info('Migrating seminar types...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('seminar_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = $this->migrateSimpleTable(
            'type_seminaries',
            'seminar_types',
            [],
            fn ($data) => [
                'id' => $data['id'],
                'name' => $data['name'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]
        );

        $this->info("Migrated {$count} seminar types.");
        return Command::SUCCESS;
    }
}
