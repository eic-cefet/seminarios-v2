<?php

namespace Database\Factories;

use App\Enums\BadgeKey;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBadge>
 */
class UserBadgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'badge_key' => fake()->randomElement(BadgeKey::cases()),
            'earned_at' => now(),
        ];
    }
}
