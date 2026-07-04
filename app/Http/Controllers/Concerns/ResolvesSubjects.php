<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Subject;
use App\Services\SlugService;

trait ResolvesSubjects
{
    /**
     * @param  array<string>  $names
     * @return array<int>
     */
    private function resolveSubjectNames(array $names, SlugService $slugService): array
    {
        return array_map(
            function (string $name) use ($slugService) {
                $trimmed = trim($name);

                return Subject::firstOrCreate(
                    ['name' => $trimmed],
                    ['slug' => $slugService->generateUnique($trimmed, Subject::class)],
                )->id;
            },
            $names
        );
    }
}
