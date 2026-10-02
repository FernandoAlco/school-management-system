<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeLevel>
 */
class GradeLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sortOrder = fake()->unique()->numberBetween(1, 255);

        return [
            'name' => "Grade {$sortOrder}",
            'sort_order' => $sortOrder,
        ];
    }
}
