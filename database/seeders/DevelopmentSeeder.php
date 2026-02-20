<?php

namespace Database\Seeders;

use App\Enums\CourseSituation;
use App\Models\Course;
use App\Models\PresenceLink;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding development data...');

        // --- Lookup tables ---
        $seminarTypes = collect([
            'Seminário', 'Qualificação', 'Dissertação', 'TCC',
            'Aula inaugural', 'Painel', 'Doutorado',
        ])->map(fn (string $name) => SeminarType::create(['name' => $name]));

        $locations = SeminarLocation::factory(5)->create();
        $courses = Course::factory(6)->create();
        $subjects = Subject::factory(30)->create();
        $workshops = Workshop::factory(3)->create();

        $this->command->line('  Created lookup tables');

        // --- Users ---
        $admins = User::factory(2)->admin()->create();
        $teachers = User::factory(1)->teacher()->create();
        $speakers = User::factory(15)->speaker()->create();

        $students = User::factory(50)->create();
        $students->each(function (User $user) use ($courses) {
            $situation = fake()->randomFloat(2, 0, 1) < 0.95
                ? CourseSituation::Studying
                : CourseSituation::Graduated;

            \App\Models\UserStudentData::factory()
                ->state([
                    'user_id' => $user->id,
                    'course_id' => $courses->random()->id,
                    'course_situation' => $situation,
                ])
                ->create();
        });

        $this->command->line('  Created users: 2 admins, 1 teacher, 15 speakers, 50 students');

        // --- Weighted seminar type distribution ---
        // TCC ~50%, Seminário ~25%, Dissertação ~10%, Qualificação ~10%, others ~5%
        $typeWeights = [
            'TCC' => 15,
            'Seminário' => 8,
            'Dissertação' => 3,
            'Qualificação' => 3,
            'Doutorado' => 1,
        ];

        $weightedTypes = [];
        foreach ($typeWeights as $name => $weight) {
            $type = $seminarTypes->firstWhere('name', $name);
            for ($i = 0; $i < $weight; $i++) {
                $weightedTypes[] = $type->id;
            }
        }

        // --- Seminars ---
        $allSeminars = collect();

        // 20 past seminars
        $pastSeminars = collect();
        for ($i = 0; $i < 20; $i++) {
            $seminar = Seminar::factory()
                ->past()
                ->state([
                    'seminar_location_id' => $locations->random()->id,
                    'seminar_type_id' => fake()->randomElement($weightedTypes),
                    'workshop_id' => $i < 2 ? $workshops->random()->id : null,
                    'created_by' => $teachers->merge($admins)->random()->id,
                ])
                ->create();

            $this->attachSpeakersAndSubjects($seminar, $speakers, $subjects);
            PresenceLink::factory()->inactive()->create(['seminar_id' => $seminar->id]);

            $pastSeminars->push($seminar);
        }

        // 8 upcoming seminars
        $upcomingSeminars = collect();
        for ($i = 0; $i < 8; $i++) {
            $seminar = Seminar::factory()
                ->upcoming()
                ->state([
                    'seminar_location_id' => $locations->random()->id,
                    'seminar_type_id' => fake()->randomElement($weightedTypes),
                    'created_by' => $teachers->merge($admins)->random()->id,
                ])
                ->create();

            $this->attachSpeakersAndSubjects($seminar, $speakers, $subjects);
            PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

            $upcomingSeminars->push($seminar);
        }

        // 2 inactive seminars
        for ($i = 0; $i < 2; $i++) {
            $seminar = Seminar::factory()
                ->upcoming()
                ->inactive()
                ->state([
                    'seminar_location_id' => $locations->random()->id,
                    'seminar_type_id' => fake()->randomElement($weightedTypes),
                    'created_by' => $teachers->merge($admins)->random()->id,
                ])
                ->create();

            $this->attachSpeakersAndSubjects($seminar, $speakers, $subjects);
            PresenceLink::factory()->inactive()->create(['seminar_id' => $seminar->id]);
        }

        $allSeminars = $pastSeminars->merge($upcomingSeminars);
        $this->command->line('  Created 30 seminars (20 past, 8 upcoming, 2 inactive)');

        // --- Registrations & Ratings ---
        $registrationCount = 0;
        $ratingCount = 0;

        foreach ($pastSeminars as $seminar) {
            $attendees = $students->random(fake()->numberBetween(8, 20));

            foreach ($attendees as $student) {
                $isPresent = fake()->randomFloat(2, 0, 1) < 0.57;

                $registration = Registration::factory()->state([
                    'seminar_id' => $seminar->id,
                    'user_id' => $student->id,
                    'present' => $isPresent,
                    'reminder_sent' => true,
                    'certificate_code' => $isPresent ? fake()->uuid() : null,
                    'certificate_sent' => $isPresent && fake()->boolean(70),
                ]);

                $registration->create();
                $registrationCount++;

                if ($isPresent && fake()->randomFloat(2, 0, 1) < 0.30) {
                    $score = fake()->randomElement([5, 5, 5, 5, 5, 5, 5, 5, 4, 3]);

                    Rating::factory()->create([
                        'seminar_id' => $seminar->id,
                        'user_id' => $student->id,
                        'score' => $score,
                    ]);
                    $ratingCount++;
                }
            }
        }

        foreach ($upcomingSeminars as $seminar) {
            $attendees = $students->random(fake()->numberBetween(10, 25));

            foreach ($attendees as $student) {
                Registration::factory()->create([
                    'seminar_id' => $seminar->id,
                    'user_id' => $student->id,
                ]);
                $registrationCount++;
            }
        }

        $this->command->line("  Created {$registrationCount} registrations, {$ratingCount} ratings");
        $this->command->info('Development data seeded successfully.');
    }

    private function attachSpeakersAndSubjects(Seminar $seminar, $speakers, $subjects): void
    {
        // 75% chance of 1 speaker, 20% chance of 2, 5% chance of 3
        $speakerCount = fake()->randomElement([1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 3]);
        $seminar->speakers()->attach(
            $speakers->random(min($speakerCount, $speakers->count()))->pluck('id')
        );

        $subjectCount = fake()->numberBetween(1, 4);
        $seminar->subjects()->attach(
            $subjects->random(min($subjectCount, $subjects->count()))->pluck('id')
        );
    }
}
