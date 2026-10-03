<?php

namespace Database\Factories;

use App\Enums\TeacherStatus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'employee_number' => fake()->unique()->numerify('EMP-#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(###) ###-####'),
            'hire_date' => fake()->dateTimeBetween('-15 years', '-1 month'),
            'status' => TeacherStatus::Active,
        ];
    }

    /**
     * Indicate that the teacher has a user account.
     */
    public function withUser(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => User::factory()->state([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'email' => $attributes['email'],
            ]),
        ]);
    }

    /**
     * Indicate that the teacher is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TeacherStatus::Inactive,
        ]);
    }
}
