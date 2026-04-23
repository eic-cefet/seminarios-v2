<?php

namespace Database\Factories;

use App\Models\AlertPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertPreference>
 */
class AlertPreferenceFactory extends Factory
{
    protected $model = AlertPreference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'opted_in' => true,
            'seminar_reminder_7d' => true,
            'seminar_reminder_24h' => true,
            'evaluation_prompt' => true,
            'announcements' => true,
        ];
    }

    public function optedOut(): self
    {
        return $this->state(fn () => ['opted_in' => false]);
    }

    public function transactionalAllOff(): self
    {
        return $this->state(fn () => [
            'seminar_reminder_7d' => false,
            'seminar_reminder_24h' => false,
            'evaluation_prompt' => false,
            'announcements' => false,
        ]);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function forTypes(array $ids): self
    {
        return $this->afterCreating(function (AlertPreference $pref) use ($ids) {
            $pref->seminarTypes()->sync($ids);
        });
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function forSubjects(array $ids): self
    {
        return $this->afterCreating(function (AlertPreference $pref) use ($ids) {
            $pref->subjects()->sync($ids);
        });
    }
}
