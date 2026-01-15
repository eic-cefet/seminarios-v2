<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateRatingsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:ratings';
    protected $description = 'Migrate ratings from legacy database';

    public function handle(): int
    {
        $this->info('Migrating ratings...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('ratings')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = 0;

        $this->legacyTable('seminary_evaluations')
            ->orderBy('id')
            ->chunk(500, function ($records) use (&$count) {
                $inserts = [];

                foreach ($records as $record) {
                    $mapping = $this->newTable('user_id_mappings')
                        ->where('legacy_table', 'students')
                        ->where('legacy_id', $record->student_id)
                        ->first();

                    if (!$mapping) {
                        $this->warn("No mapping for student_id: {$record->student_id}");
                        continue;
                    }

                    $inserts[] = [
                        'id' => $record->id,
                        'seminar_id' => $record->seminary_id,
                        'user_id' => $mapping->new_user_id,
                        'score' => $record->rating ?? $record->stars ?? 5,
                        'comment' => $record->comment ?? null,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }

                if (!empty($inserts)) {
                    $this->newTable('ratings')->insert($inserts);
                    $count += count($inserts);
                }
            });

        $this->info("Migrated {$count} ratings.");
        return Command::SUCCESS;
    }
}
