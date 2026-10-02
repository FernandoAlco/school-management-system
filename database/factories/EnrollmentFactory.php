<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'section_id' => Section::factory(),
            'academic_year_id' => fn (array $attributes): int => Section::findOrFail($attributes['section_id'])->academic_year_id,
            'enrolled_on' => fake()->dateTimeBetween('-1 year'),
            'status' => EnrollmentStatus::Active,
        ];
    }
}
