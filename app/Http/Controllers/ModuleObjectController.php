<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\ModuleObject;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ModuleObjectController extends Controller
{
    public function store(Request $request, Classroom $classroom, ClassroomModule $module): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        if ($module->classroom_id !== $classroom->id) {
            abort(404);
        }

        $validated = $request->validate([
            'type' => 'required|in:learning_content,quiz',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
        ]);

        $position = $module->objects()->max('position') + 1;

        if ($validated['type'] === 'learning_content') {
            $object = LearningContent::create([
                'title' => $validated['title'],
                'content' => [],
                'created_by' => $request->user()->id,
            ]);
        } else {
            $object = Quiz::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'passing_grade' => $validated['passing_grade'] ?? 70,
                'time_limit' => $validated['time_limit'] ?? 30,
                'created_by' => $request->user()->id,
            ]);
        }

        $module->objects()->create([
            'object_id' => $object->id,
            'object_type' => get_class($object),
            'position' => $position,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Objek berhasil ditambahkan.',
        ]);

        return redirect()->back();
    }

    public function update(Request $request, Classroom $classroom, ModuleObject $object): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $validated = $request->validate([
            'type' => 'required|in:learning_content,quiz',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
        ]);

        $actualObject = $object->object;

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
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Objek berhasil diperbarui.',
        ]);

        return redirect()->back();
    }

    public function destroy(Classroom $classroom, ModuleObject $object): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $object->object->delete();
        $object->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Objek berhasil dihapus.',
        ]);

        return redirect()->back();
    }
}
