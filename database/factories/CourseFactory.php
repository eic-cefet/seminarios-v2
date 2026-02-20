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
