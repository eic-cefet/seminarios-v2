<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSeminarLocationsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:seminar-locations';
    protected $description = 'Migrate seminar locations from legacy database';

    public function handle(): int
    {
        $this->info('Migrating seminar locations...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('seminar_locations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = $this->migrateSimpleTable(
            'locals',
            'seminar_locations',
            [],
            fn ($data) => [
                'id' => $data['id'],
                'name' => $data['name'],
                'max_vacancies' => $data['sits'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]
        );

        $this->info("Migrated {$count} seminar locations.");
        return Command::SUCCESS;
    }
}
