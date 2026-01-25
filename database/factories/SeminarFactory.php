<?php

namespace Database\Factories;

use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seminar>
 */
class SeminarFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->sentence(5);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'description' => fake()->optional()->paragraphs(2, true),
            'seminar_location_id' => SeminarLocation::factory(),
            'seminar_type_id' => SeminarType::factory(),
            'workshop_id' => null,
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'room_link' => fake()->optional()->url(),
            'active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }
}
