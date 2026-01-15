<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSeminarSpeakersCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:seminar-speakers';
    protected $description = 'Migrate seminar_speaker pivot table';

    public function handle(): int
    {
        $this->info('Migrating seminar speakers...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('seminar_speaker')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = 0;

        $this->legacyTable('seminaries_speakers')
            ->orderBy('seminary_id')
            ->chunk(500, function ($records) use (&$count) {
                $inserts = [];

                foreach ($records as $record) {
                    $mapping = $this->newTable('user_id_mappings')
                        ->where('legacy_table', 'speakers')
                        ->where('legacy_id', $record->speaker_id)
                        ->first();

                    if (!$mapping) {
                        $this->warn("No mapping for speaker_id: {$record->speaker_id}");
                        continue;
                    }

                    $inserts[] = [
                        'seminar_id' => $record->seminary_id,
                        'user_id' => $mapping->new_user_id,
                    ];
                }

                if (!empty($inserts)) {
                    $this->newTable('seminar_speaker')->insert($inserts);
                    $count += count($inserts);
                }
            });

        $this->info("Migrated {$count} seminar-speaker relationships.");
        return Command::SUCCESS;
    }
}
