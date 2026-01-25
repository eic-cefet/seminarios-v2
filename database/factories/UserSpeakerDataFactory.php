<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSpeakerData>
 */
class UserSpeakerDataFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'institution' => fake()->optional()->company(),
            'description' => fake()->optional()->paragraph(),
        ];
    }

    public function withInstitution(): static
    {
        return $this->state(fn (array $attributes) => [
            'institution' => fake()->company(),
        ]);
    }

    public function withDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->paragraphs(2, true),
        ]);
    }
}
