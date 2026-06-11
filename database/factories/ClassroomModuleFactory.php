<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\ClassroomModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassroomModule>
 */
class ClassroomModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'title' => fake()->sentence(3),
            'position' => fake()->numberBetween(1, 10),
        ];
    }
}
