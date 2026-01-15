<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSubjectsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:subjects';
    protected $description = 'Migrate subjects from legacy database';

    public function handle(): int
    {
        $this->info('Migrating subjects...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('subjects')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = $this->migrateSimpleTable(
            'subjects',
            'subjects',
            [],
            fn ($data) => [
                'id' => $data['id'],
                'name' => $data['name'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]
        );

        $this->info("Migrated {$count} subjects.");
        return Command::SUCCESS;
    }
}
