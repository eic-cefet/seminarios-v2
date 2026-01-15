<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCoursesCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:courses {--dry-run : Show what would be migrated}';

    protected $description = 'Migrate courses from legacy system';

    public function handle(): int
    {
        $this->info('Migrating courses...');

        if (! $this->option('dry-run')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $this->newTable('courses')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $count = $this->migrateSimpleTable(
            legacyTable: 'courses',
            newTable: 'courses',
            fieldMap: [],
            transformer: function ($data) {
                if ($this->option('dry-run')) {
                    $this->line("Would migrate course: {$data['name']}");

                    return null;
                }

                return [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                    'deleted_at' => $data['deleted_at'] ?? null,
                ];
            }
        );

        $this->info("Migrated {$count} courses.");

        return Command::SUCCESS;
    }
}
