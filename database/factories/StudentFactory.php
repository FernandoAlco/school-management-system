<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Male, Gender::Female]);

        return [
            'user_id' => null,
            'student_number' => fake()->unique()->numerify('A########'),
            'first_name' => fake()->firstName($gender === Gender::Male ? 'male' : 'female'),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-6 years'),
            'gender' => $gender,
            'national_id' => null,
            'address' => fake()->address(),
            'photo_path' => null,
            'status' => StudentStatus::Active,
        ];
    }

    /**
     * Indicate that the student has a user account.
     */
    public function withUser(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => User::factory()->state([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
            ]),
        ]);
    }

    /**
     * Indicate that the student has graduated.
     */
    public function graduated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StudentStatus::Graduated,
        ]);
    }

    /**
     * Indicate that the student has withdrawn.
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StudentStatus::Withdrawn,
        ]);
    }
}
