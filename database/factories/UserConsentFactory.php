<?php

namespace Database\Factories;

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConsent>
 */
class UserConsentFactory extends Factory
{
    protected $model = UserConsent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'anonymous_id' => null,
            'type' => fake()->randomElement(ConsentType::cases()),
            'granted' => true,
            'version' => '1.0',
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent_hash' => hash('sha256', fake()->userAgent()),
            'source' => 'test',
        ];
    }

    public function granted(): static
    {
        return $this->state(fn () => ['granted' => true]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['granted' => false]);
    }
}
