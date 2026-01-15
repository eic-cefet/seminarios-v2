<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateWorkshopsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:workshops';
    protected $description = 'Migrate workshops from legacy database';

    public function handle(): int
    {
        $this->info('Migrating workshops...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('workshops')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = $this->migrateSimpleTable(
            'workshops',
            'workshops',
            [],
            fn ($data) => [
                'id' => $data['id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]
        );

        $this->info("Migrated {$count} workshops.");
        return Command::SUCCESS;
    }
}
