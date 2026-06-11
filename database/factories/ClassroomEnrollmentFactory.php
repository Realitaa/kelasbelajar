<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassroomEnrollment>
 */
class ClassroomEnrollmentFactory extends Factory
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
            'student_id' => User::factory()->student(),
            'enrolled_at' => now(),
        ];
    }
}
