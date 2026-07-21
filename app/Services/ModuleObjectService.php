<?php

namespace App\Services;

use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\ModuleObject;
use App\Models\Quiz;

class ModuleObjectService
{
    public function createObject(ClassroomModule $module, array $validated, int $userId): void
    {
        $position = $module->objects()->max('position') + 1;

        if ($validated['type'] === 'learning_content') {
            $object = LearningContent::create([
                'title' => $validated['title'],
                'content' => [],
                'created_by' => $userId,
            ]);
        } else {
            $object = Quiz::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'passing_grade' => $validated['passing_grade'] ?? 70,
                'time_limit' => $validated['time_limit'] ?? 30,
                'max_attempts' => $validated['max_attempts'] ?? null,
                'min_attempts_for_solution' => $validated['min_attempts_for_solution'] ?? 1,
                'created_by' => $userId,
            ]);
        }

        $module->objects()->create([
            'object_id' => $object->id,
            'object_type' => get_class($object),
            'position' => $position,
        ]);
    }

    public function updateObject(ModuleObject $moduleObject, array $validated): void
    {
        $actualObject = $moduleObject->object;

        if ($validated['type'] === 'learning_content' && $actualObject instanceof LearningContent) {
            $actualObject->update([
                'title' => $validated['title'],
            ]);
        } elseif ($validated['type'] === 'quiz' && $actualObject instanceof Quiz) {
            $actualObject->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'passing_grade' => $validated['passing_grade'] ?? 70,
                'time_limit' => $validated['time_limit'] ?? 30,
                'max_attempts' => $validated['max_attempts'] ?? null,
                'min_attempts_for_solution' => $validated['min_attempts_for_solution'] ?? 1,
            ]);
        }
    }

    public function deleteObject(ModuleObject $moduleObject): void
    {
        $moduleObject->object->delete();
        $moduleObject->delete();
    }
}
