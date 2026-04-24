<?php

namespace Database\Factories;

use App\Models\MfaTrustedDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MfaTrustedDevice>
 */
class MfaTrustedDeviceFactory extends Factory
{
    protected $model = MfaTrustedDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', Str::random(40)),
            'label' => $this->faker->userAgent(),
            'ip' => $this->faker->ipv4(),
            'expires_at' => now()->addDays(30),
        ];
    }
}
