<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePresenceLinksCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:presence-links';

    protected $description = 'Migrate presence links from legacy database';

    public function handle(): int
    {
        $this->info('Migrating presence links...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('seminar_presence_links')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = $this->migrateSimpleTable(
            'seminary_presence_links',
            'seminar_presence_links',
            [],
            function ($data) {
                return [
                    'id' => $data['seminary_id'],
                    'seminar_id' => $data['seminary_id'],
                    'uuid' => $data['uuid'],
                    'active' => $data['active'] ?? false,
                    'expires_at' => $data['expires_at'] ?? null,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                ];
            },
            'seminary_id'
        );

        $this->info("Migrated {$count} presence links.");

        return Command::SUCCESS;
    }
}
