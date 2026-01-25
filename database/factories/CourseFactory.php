<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Sistemas de Informação',
                'Ciência da Computação',
                'Engenharia de Computação',
                'Análise e Desenvolvimento de Sistemas',
            ]),
        ];
    }
}
