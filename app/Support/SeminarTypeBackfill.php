<?php

namespace App\Support;

use App\Enums\Gender;
use App\Models\SeminarType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeminarTypeBackfill
{
    /** @var array<string, array{gender: Gender, name_plural: string}> */
    private const KNOWN = [
        'seminario' => ['gender' => Gender::Masculine, 'name_plural' => 'Seminários'],
        'qualificacao' => ['gender' => Gender::Feminine, 'name_plural' => 'Qualificações'],
        'dissertacao' => ['gender' => Gender::Feminine, 'name_plural' => 'Dissertações'],
        'tcc' => ['gender' => Gender::Masculine, 'name_plural' => 'TCCs'],
        'aula-inaugural' => ['gender' => Gender::Feminine, 'name_plural' => 'Aulas inaugurais'],
        'painel' => ['gender' => Gender::Masculine, 'name_plural' => 'Painéis'],
        'doutorado' => ['gender' => Gender::Masculine, 'name_plural' => 'Doutorados'],
    ];

    public static function apply(SeminarType $type): void
    {
        $slug = Str::slug($type->name);
        $known = self::KNOWN[$slug] ?? null;

        if ($known === null) {
            Log::warning('Unknown seminar type during gender backfill, defaulting to masculine', [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $slug,
            ]);

            return;
        }

        $type->forceFill([
            'gender' => $known['gender'],
            'name_plural' => $known['name_plural'],
        ])->save();
    }
}
