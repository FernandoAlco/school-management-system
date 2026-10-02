<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2000, 2099);

        return [
            'name' => $startYear.'-'.($startYear + 1),
            'starts_on' => "{$startYear}-08-20",
            'ends_on' => ($startYear + 1).'-07-15',
            'is_current' => false,
        ];
    }

    /**
     * Indicate that the academic year is the current one.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_current' => true,
        ]);
    }
}
