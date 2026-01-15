<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateRegistrationsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:registrations';
    protected $description = 'Migrate students_seminaries to registrations';

    public function handle(): int
    {
        $this->info('Migrating registrations (students_seminaries)...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->newTable('registrations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = 0;

        $this->legacyTable('students_seminaries')
            ->orderBy('student_id')
            ->orderBy('seminary_id')
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
                        'seminar_id' => $record->seminary_id,
                        'user_id' => $mapping->new_user_id,
                        'present' => $record->present ?? false,
                        'reminder_sent' => $record->sentReminder ?? false,
                        'certificate_code' => $record->certificate_code ?? null,
                        'certificate_sent' => $record->sentCertificate ?? false,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }

                if (!empty($inserts)) {
                    $this->newTable('registrations')->insert($inserts);
                    $count += count($inserts);
                }
            });

        $this->info("Migrated {$count} registrations.");
        return Command::SUCCESS;
    }
}
