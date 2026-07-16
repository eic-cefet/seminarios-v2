<?php

namespace Database\Factories;

use App\Enums\ExperienceReason;
use App\Models\User;
use App\Models\UserExperienceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserExperienceEvent>
 */
class UserExperienceEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => fake()->randomElement(ExperienceReason::cases()),
            'source_key' => fake()->unique()->uuid(),
            'points' => fake()->numberBetween(1, 100),
        ];
    }
}
