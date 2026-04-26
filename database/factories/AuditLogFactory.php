<?php

namespace Database\Factories;

use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_name' => 'test.event',
            'event_type' => AuditEventType::System,
            'event_data' => null,
            'origin' => 'TestFactory',
            'ip_hash' => hash('sha256', $this->faker->ipv4()),
        ];
    }

    public function manual(): static
    {
        return $this->state(fn () => [
            'event_type' => AuditEventType::Manual,
        ]);
    }

    public function forModel(Model $model): static
    {
        return $this->state(fn () => [
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }
}
