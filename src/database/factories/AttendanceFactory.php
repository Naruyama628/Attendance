<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        
        return [
            'user_id' => 2,
            'work_date' => $this->faker->date(),
            'clock_in' => now()->setTime(9, 0),
            'clock_out' => now()->setTime(18, 0),
        ];
    }
}
