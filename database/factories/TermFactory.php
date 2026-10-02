<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 year', '+1 year');

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Term 1',
            'sort_order' => 1,
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+2 months'),
        ];
    }
}
