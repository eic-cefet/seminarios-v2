<?php

namespace Database\Factories;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registration>
 */
class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seminar_id' => Seminar::factory(),
            'user_id' => User::factory(),
            'present' => false,
            'reminder_sent' => false,
            'certificate_code' => null,
            'certificate_sent' => false,
            'evaluation_sent_at' => null,
        ];
    }

    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'present' => true,
        ]);
    }

    public function withCertificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'present' => true,
            'certificate_code' => fake()->uuid(),
        ]);
    }

    public function evaluated(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_sent_at' => now(),
        ]);
    }
}
