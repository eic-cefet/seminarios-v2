<?php

namespace App\Console\Commands\Migration\Concerns;

use Illuminate\Support\Facades\DB;

trait MigratesData
{
    protected function legacyTable(string $table)
    {
        return DB::connection('legacy')->table($table);
    }

    protected function newTable(string $table)
    {
        return DB::connection('mysql')->table($table);
    }

    protected function migrateSimpleTable(
        string $legacyTable,
        string $newTable,
        array $fieldMap = [],
        ?callable $transformer = null,
        string $orderBy = 'id'
    ): int {
        $count = 0;

        $this->legacyTable($legacyTable)
            ->orderBy($orderBy)
            ->chunk(500, function ($records) use ($newTable, $fieldMap, $transformer, &$count) {
                $inserts = [];

                foreach ($records as $record) {
                    $data = (array) $record;

                    foreach ($fieldMap as $old => $new) {
                        if (array_key_exists($old, $data)) {
                            $data[$new] = $data[$old];
                            unset($data[$old]);
                        }
                    }

                    if ($transformer) {
                        $data = $transformer($data);
                    }

                    if ($data !== null) {
                        $inserts[] = $data;
                    }
                }

                if (!empty($inserts)) {
                    $this->newTable($newTable)->insert($inserts);
                    $count += count($inserts);
                }
            });

        return $count;
    }
}
