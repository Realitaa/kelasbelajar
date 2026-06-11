<?php

namespace Database\Factories;

use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\ModuleObject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleObject>
 */
class ModuleObjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => ClassroomModule::factory(),
            'object_id' => LearningContent::factory(),
            'object_type' => LearningContent::class,
            'position' => fake()->numberBetween(1, 10),
        ];
    }
}
