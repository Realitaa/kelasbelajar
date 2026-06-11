<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderClassroomModulesRequest;
use App\Http\Requests\ReorderModuleObjectsRequest;
use App\Http\Requests\StoreClassroomModuleRequest;
use App\Http\Requests\UpdateClassroomModuleRequest;
use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Services\ClassroomModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ClassroomModuleController extends Controller
{
    public function __construct(
        protected ClassroomModuleService $moduleService
    ) {}

    public function store(StoreClassroomModuleRequest $request, Classroom $classroom): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $this->moduleService->createModule($classroom, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Modul berhasil ditambahkan.',
        ]);

        return redirect()->back();
    }

    public function update(UpdateClassroomModuleRequest $request, Classroom $classroom, ClassroomModule $module): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        if ($module->classroom_id !== $classroom->id) {
            abort(404);
        }

        $this->moduleService->updateModule($module, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Modul berhasil diperbarui.',
        ]);

        return redirect()->back();
    }

    public function destroy(Classroom $classroom, ClassroomModule $module): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        if ($module->classroom_id !== $classroom->id) {
            abort(404);
        }

        $this->moduleService->deleteModule($module);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Modul berhasil dihapus.',
        ]);

        return redirect()->back();
    }

    public function reorder(ReorderClassroomModulesRequest $request, Classroom $classroom): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $validated = $request->validated();
        $this->moduleService->reorderModules($classroom, $validated['modules']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Urutan modul berhasil disimpan.',
        ]);

        return redirect()->back();
    }

    public function reorderObjects(ReorderModuleObjectsRequest $request, Classroom $classroom): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $validated = $request->validated();
        $this->moduleService->reorderObjects($classroom, $validated['objects']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Urutan isi modul berhasil disimpan.',
        ]);

        return redirect()->back();
    }
}
