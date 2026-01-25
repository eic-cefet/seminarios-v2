<?php

namespace Database\Factories;

use App\Enums\CourseRole;
use App\Enums\CourseSituation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserStudentData>
 */
class UserStudentDataFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'course_situation' => fake()->randomElement(CourseSituation::cases()),
            'course_role' => fake()->randomElement(CourseRole::cases()),
        ];
    }

    public function studying(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_situation' => CourseSituation::Studying,
        ]);
    }

    public function graduated(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_situation' => CourseSituation::Graduated,
        ]);
    }

    public function asStudent(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_role' => CourseRole::Aluno,
        ]);
    }

    public function asTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_role' => CourseRole::Professor,
        ]);
    }
}
