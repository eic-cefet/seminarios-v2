<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Bacharelado em Ciência da Computação',
                'Bacharelado em Sistemas de Informação',
                'Tecnologia em Sistemas para Internet',
                'Bacharelado em Engenharia de Computação',
                'Bacharelado em Engenharia de Produção',
                'Bacharelado em Engenharia Elétrica',
                'Bacharelado em Engenharia Ambiental',
                'Bacharelado em Engenharia Civil',
                'Bacharelado em Administração',
                'Mestrado em Ciência da Computação',
                'Doutorado em Ciência da Computação',
                'Mestrado em Engenharia Elétrica',
            ]),
        ];
    }
}
