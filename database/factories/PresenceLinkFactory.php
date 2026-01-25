<?php

namespace Database\Factories;

use App\Models\Seminar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PresenceLink>
 */
class PresenceLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seminar_id' => Seminar::factory(),
            'uuid' => fake()->uuid(),
            'active' => true,
            'expires_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 week', '-1 hour'),
        ]);
    }

    public function expiresIn(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }
}
