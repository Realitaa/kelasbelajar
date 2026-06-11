<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassroomStoreRequest;
use App\Http\Requests\ClassroomUpdateRequest;
use App\Models\Classroom;
use App\Services\ClassroomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ClassroomService $classroomService
    ) {}

    /**
     * Display a listing of classrooms.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        return Inertia::render('classrooms/Index', [
            'classrooms' => $this->classroomService->getClassroomsForUser($request->user()),
        ]);
    }

    /**
     * Store a newly created classroom in storage.
     */
    public function store(ClassroomStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Classroom::class);

        $this->classroomService->createClassroom($request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil dibuat.',
        ]);

        return to_route('classrooms.index');
    }

    /**
     * Update the specified classroom in storage.
     */
    public function update(ClassroomUpdateRequest $request, Classroom $classroom): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $this->classroomService->updateClassroom($classroom, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil diperbarui.',
        ]);

        return to_route('classrooms.index');
    }

    /**
     * Remove the specified classroom from storage.
     */
    public function destroy(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $this->classroomService->deleteClassroom($classroom);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil dihapus.',
        ]);

        return redirect()->back();
    }

    /**
     * Publish the specified classroom.
     */
    public function publish(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('publish', $classroom);

        $this->classroomService->publishClassroom($classroom);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil dipublikasikan.',
        ]);

        return redirect()->back();
    }

    /**
     * Unpublish the specified classroom.
     */
    public function unpublish(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('unpublish', $classroom);

        $this->classroomService->unpublishClassroom($classroom);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Publikasi kelas dibatalkan.',
        ]);

        return redirect()->back();
    }

    /**
     * Display the classroom manage page with loaded modules.
     */
    public function manage(Classroom $classroom): Response
    {
        Gate::authorize('update', $classroom);

        $classroom->load(['modules.objects.object']);

        return Inertia::render('classrooms/Manage', [
            'classroom' => $classroom,
        ]);
    }
}
