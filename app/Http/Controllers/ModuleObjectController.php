<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModuleObjectRequest;
use App\Http\Requests\UpdateModuleObjectRequest;
use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\ModuleObject;
use App\Services\ModuleObjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ModuleObjectController extends Controller
{
    public function store(StoreModuleObjectRequest $request, Classroom $classroom, ClassroomModule $module, ModuleObjectService $moduleObjectService): RedirectResponse
    {
        if ($module->classroom_id !== $classroom->id) {
            abort(404);
        }

        $moduleObjectService->createObject($module, $request->validated(), $request->user()->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Objek berhasil ditambahkan.',
        ]);

        return redirect()->back();
    }

    public function update(UpdateModuleObjectRequest $request, Classroom $classroom, ModuleObject $object, ModuleObjectService $moduleObjectService): RedirectResponse
    {
        $moduleObjectService->updateObject($object, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Objek berhasil diperbarui.',
        ]);

        return redirect()->back();
    }

    public function destroy(Classroom $classroom, ModuleObject $object, ModuleObjectService $moduleObjectService): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $moduleObjectService->deleteObject($object);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Objek berhasil dihapus.',
        ]);

        return redirect()->back();
    }
}
