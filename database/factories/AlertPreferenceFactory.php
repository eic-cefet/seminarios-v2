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
            'new_seminar_alert' => true,
            'seminar_reminder_7d' => true,
            'seminar_reminder_24h' => true,
            'evaluation_prompt' => true,
            'announcements' => true,
            'certificate_ready' => true,
            'seminar_rescheduled' => true,
            'workshop_announcements' => true,
        ];
    }

    public function optedOut(): self
    {
        return $this->state(fn () => ['new_seminar_alert' => false]);
    }

    public function newSeminarAlertOff(): self
    {
        return $this->state(fn () => ['new_seminar_alert' => false]);
    }

    public function transactionalAllOff(): self
    {
        return $this->state(fn () => [
            'seminar_reminder_7d' => false,
            'seminar_reminder_24h' => false,
            'evaluation_prompt' => false,
            'announcements' => false,
            'certificate_ready' => false,
            'seminar_rescheduled' => false,
            'workshop_announcements' => false,
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
