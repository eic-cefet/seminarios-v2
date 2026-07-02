<?php

namespace App\Services;

use App\Models\Seminar;
use App\Models\SeminarSlugHistory;
use App\Support\Locking\LockKey;
use App\Support\Locking\Mutex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Models whose retired slugs must stay reserved so historical links
     * keep resolving to their original owner. Shape per entry:
     * [history model, slug column, owner FK column].
     *
     * @var array<class-string<Model>, array{class-string<Model>, string, string}>
     */
    private const RESERVED_SLUG_SOURCES = [
        Seminar::class => [SeminarSlugHistory::class, 'slug', 'seminar_id'],
    ];

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function generateUnique(
        string $name,
        string $modelClass,
        string $column = 'slug',
        ?int $excludeId = null
    ): string {
        return Mutex::for(LockKey::slugGeneration($modelClass, $name), ttlSeconds: 5, waitSeconds: 5)
            ->protect(function () use ($name, $modelClass, $column, $excludeId): string {
                $baseSlug = Str::slug($name);
                $slug = $baseSlug;
                $counter = 1;

                while ($this->slugExists($modelClass, $column, $slug, $excludeId)) {
                    $slug = $baseSlug.'-'.$counter++;
                }

                return $slug;
            });
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function slugExists(
        string $modelClass,
        string $column,
        string $slug,
        ?int $excludeId = null
    ): bool {
        $query = $modelClass::where($column, $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            return true;
        }

        if (! isset(self::RESERVED_SLUG_SOURCES[$modelClass])) {
            return false;
        }

        [$historyClass, $historyColumn, $ownerColumn] = self::RESERVED_SLUG_SOURCES[$modelClass];

        $historyQuery = $historyClass::query()->where($historyColumn, $slug);

        if ($excludeId !== null) {
            $historyQuery->where($ownerColumn, '!=', $excludeId);
        }

        return $historyQuery->exists();
    }
}
