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
        ];
    }

    public function optedOut(): self
    {
        return $this->state(fn () => ['opted_in' => false]);
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
