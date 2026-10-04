<?php

namespace Database\Factories;

use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Period>
 */
class PeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hour = fake()->unique()->numberBetween(6, 20);

        return [
            'name' => "Period {$hour}",
            'starts_at' => sprintf('%02d:00:00', $hour),
            'ends_at' => sprintf('%02d:45:00', $hour),
        ];
    }
}
