<?php

namespace Database\Factories;

use App\Models\SeminarType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeminarType>
 */
class SeminarTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Palestra', 'Workshop', 'Mesa Redonda', 'Minicurso', 'Seminário']),
        ];
    }
}
