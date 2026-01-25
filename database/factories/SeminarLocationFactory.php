<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeminarLocation>
 */
class SeminarLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Auditório', 'Sala', 'Laboratório']).' '.fake()->numberBetween(100, 500),
            'max_vacancies' => fake()->randomElement([30, 50, 80, 100, 150]),
        ];
    }
}
