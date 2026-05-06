<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\SeminarType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeminarType>
 */
class SeminarTypeFactory extends Factory
{
    protected $model = SeminarType::class;

    public function definition(): array
    {
        $known = [
            'Palestra' => [Gender::Feminine, 'Palestras'],
            'Workshop' => [Gender::Masculine, 'Workshops'],
            'Mesa Redonda' => [Gender::Feminine, 'Mesas Redondas'],
            'Minicurso' => [Gender::Masculine, 'Minicursos'],
            'Seminário' => [Gender::Masculine, 'Seminários'],
        ];

        $name = fake()->randomElement(array_keys($known));
        [$gender, $plural] = $known[$name];

        return [
            'name' => $name,
            'gender' => $gender,
            'name_plural' => $plural,
        ];
    }

    public function masculine(): static
    {
        return $this->state(['gender' => Gender::Masculine]);
    }

    public function feminine(): static
    {
        return $this->state(['gender' => Gender::Feminine]);
    }
}
