<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Subject;

trait ResolvesSubjects
{
    /**
     * @param  array<string>  $names
     * @return array<int>
     */
    private function resolveSubjectNames(array $names): array
    {
        return array_map(
            function (string $name) {
                $trimmed = trim($name);

                return Subject::firstOrCreate(
                    ['name' => $trimmed],
                    ['slug' => $this->slugService->generateUnique($trimmed, Subject::class)],
                )->id;
            },
            $names
        );
    }
}
