<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateSeminarsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:seminars';
    protected $description = 'Migrate seminars from legacy database';

    public function handle(): int
    {
        $this->info('Migrating seminars...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('seminars')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = $this->migrateSimpleTable(
            'seminaries',
            'seminars',
            [],
            function ($data) {
                $slug = $data['url'] ?? null;
                if (empty($slug)) {
                    $slug = Str::slug($data['name']) . '-' . $data['id'];
                }

                return [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'seminar_location_id' => $data['local_id'],
                    'workshop_id' => $data['workshop_id'] ?? null,
                    'seminar_type_id' => $data['type'] ?? null,
                    'scheduled_at' => $data['combined_date'] ?? null,
                    'link' => $data['link'] ?? null,
                    'active' => $data['active'] ?? false,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                    'deleted_at' => $data['deleted_at'] ?? null,
                ];
            }
        );

        $this->info("Migrated {$count} seminars.");
        return Command::SUCCESS;
    }
}
