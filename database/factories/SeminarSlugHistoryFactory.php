<?php

namespace Database\Factories;

use App\Models\Seminar;
use App\Models\SeminarSlugHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeminarSlugHistory>
 */
class SeminarSlugHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seminar_id' => Seminar::factory(),
            'slug' => fake()->unique()->slug(3),
        ];
    }
}
