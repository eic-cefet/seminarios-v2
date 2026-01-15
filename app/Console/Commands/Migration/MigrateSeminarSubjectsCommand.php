<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSeminarSubjectsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:seminar-subjects';
    protected $description = 'Migrate seminar_subject pivot table';

    public function handle(): int
    {
        $this->info('Migrating seminar subjects...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('seminar_subject')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = 0;

        $this->legacyTable('seminaries_subjects')
            ->orderBy('seminary_id')
            ->chunk(500, function ($records) use (&$count) {
                $inserts = [];

                foreach ($records as $record) {
                    $inserts[] = [
                        'seminar_id' => $record->seminary_id,
                        'subject_id' => $record->subject_id,
                    ];
                }

                if (!empty($inserts)) {
                    $this->newTable('seminar_subject')->insert($inserts);
                    $count += count($inserts);
                }
            });

        $this->info("Migrated {$count} seminar-subject relationships.");
        return Command::SUCCESS;
    }
}
