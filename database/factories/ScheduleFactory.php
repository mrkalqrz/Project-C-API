<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_name' => $this->faker->company() . ' Arena',
            'rake_percentage' => 5,
            'min_payout' => 100,
            'max_payout' => 1000000000,
            'max_draw_bet' => 5000,
            'enable_draw_bet' => 1,
            'draw_rake' => 8,
            'status' => 1
        ];
    }
}
