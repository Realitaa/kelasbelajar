<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\ModuleObject;
use Illuminate\Support\Facades\DB;

class ClassroomModuleService
{
    /**
     * Create a new classroom module.
     *
     * @param  array{title: string}  $data
     */
    public function createModule(Classroom $classroom, array $data): ClassroomModule
    {
        $maxPosition = $classroom->modules()->max('position') ?? 0;

        return $classroom->modules()->create([
            'title' => $data['title'],
            'color' => $data['color'] ?? null,
            'position' => $maxPosition + 1,
        ]);
    }

    /**
     * Update an existing classroom module.
     *
     * @param  array{title: string, color?: string|null}  $data
     */
    public function updateModule(ClassroomModule $module, array $data): ClassroomModule
    {
        $module->update([
            'title' => $data['title'],
            'color' => $data['color'] ?? null,
        ]);

        return $module;
    }

    /**
     * Delete a classroom module and reorder the remaining ones.
     */
    public function deleteModule(ClassroomModule $module): void
    {
        $classroom = $module->classroom;

        $module->delete();

        // Reorder remaining modules
        $remainingModules = $classroom->modules()->orderBy('position')->get();
        foreach ($remainingModules as $index => $remModule) {
            $remModule->update(['position' => $index + 1]);
        }
    }

    public function reorderModules(Classroom $classroom, array $modulesData): void
    {
        DB::transaction(function () use ($classroom, $modulesData) {
            foreach ($modulesData as $moduleData) {
                $classroom->modules()
                    ->where('id', $moduleData['id'])
                    ->update(['position' => 100000 + $moduleData['position']]);
            }

            foreach ($modulesData as $moduleData) {
                $classroom->modules()
                    ->where('id', $moduleData['id'])
                    ->update(['position' => $moduleData['position']]);
            }
        });
    }

    /**
     * Reorder module objects (contents/quizzes) across or within modules.
     *
     * @param  array<int, array{id: int, module_id: int, position: int}>  $objectsData
     */
    public function reorderObjects(Classroom $classroom, array $objectsData): void
    {
        foreach ($objectsData as $objectData) {
            $targetModule = $classroom->modules()->where('id', $objectData['module_id'])->first();

            if ($targetModule) {
                $moduleObject = ModuleObject::find($objectData['id']);
                if ($moduleObject && $moduleObject->module->classroom_id === $classroom->id) {
                    $moduleObject->update([
                        'module_id' => $objectData['module_id'],
                        'position' => $objectData['position'],
                    ]);
                }
            }
        }
    }
}
