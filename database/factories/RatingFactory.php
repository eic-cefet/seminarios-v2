<?php

namespace Database\Factories;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rating>
 */
class RatingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seminar_id' => Seminar::factory(),
            'user_id' => User::factory(),
            'score' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }

    public function withScore(int $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
        ]);
    }

    public function withComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => fake()->paragraph(),
        ]);
    }
}
