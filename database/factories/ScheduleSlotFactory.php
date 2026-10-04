<?php

namespace Database\Factories;

use App\Enums\Weekday;
use App\Models\Course;
use App\Models\Period;
use App\Models\ScheduleSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleSlot>
 */
class ScheduleSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'period_id' => Period::factory(),
            'day_of_week' => fake()->randomElement(Weekday::schoolDays()),
            'classroom_id' => null,
        ];
    }
}
