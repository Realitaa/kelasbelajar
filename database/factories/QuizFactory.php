<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'passing_grade' => fake()->randomElement([60, 70, 80]),
            'time_limit' => fake()->randomElement([15, 30, 45, 60]),
            'created_by' => User::factory(),
        ];
    }
}
