<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Generate a unique slug for the given model.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function generateUnique(
        string $name,
        string $modelClass,
        string $column = 'slug',
        ?int $excludeId = null
    ): string {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($modelClass, $column, $slug, $excludeId)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists in the given model.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function slugExists(
        string $modelClass,
        string $column,
        string $slug,
        ?int $excludeId = null
    ): bool {
        $query = $modelClass::where($column, $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
