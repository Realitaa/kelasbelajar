<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassroomStoreRequest;
use App\Http\Requests\ClassroomUpdateRequest;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Services\ClassroomService;
use Illuminate\Http\JsonResponse;
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
     * Display the classroom content page for student.
     */
    public function show(Request $request, Classroom $classroom): Response
    {
        Gate::authorize('view', $classroom);

        $user = $request->user();

        // Load modules with objects and their nested object details
        $classroom->load([
            'modules' => function ($q) {
                $q->orderBy('position');
            },
            'modules.objects' => function ($q) {
                $q->orderBy('position');
            },
            'modules.objects.object',
        ]);

        // Get all quiz IDs in this classroom to retrieve submissions in one query
        $quizIds = [];
        foreach ($classroom->modules as $module) {
            foreach ($module->objects as $obj) {
                if ($obj->object_type === Quiz::class) {
                    $quizIds[] = $obj->object_id;
                }
            }
        }

        // Fetch submissions for this student
        $submissions = QuizSubmission::where('student_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->get();

        // Flatten objects to determine access sequentially
        $flatObjects = [];
        foreach ($classroom->modules as $module) {
            foreach ($module->objects as $obj) {
                $flatObjects[] = $obj;
            }
        }

        // Determine completes & access sequentially
        $canAccess = true;
        foreach ($flatObjects as $obj) {
            // Student is allowed to access if $canAccess is true
            $obj->can_access = $canAccess;

            if ($obj->object_type === Quiz::class) {
                $quiz = $obj->object;
                $quizSubmissions = $submissions->where('quiz_id', $obj->object_id);

                // Highest score matters
                $maxScore = $quizSubmissions->max('score');
                $passingGrade = $quiz?->passing_grade ?? 70;

                $obj->is_completed = ! is_null($maxScore) && $maxScore >= $passingGrade;

                if ($quiz) {
                    $quiz->highest_score = $maxScore;
                }
            } else {
                // LearningContent is always completed automatically
                $obj->is_completed = true;
            }

            // Next object is accessible only if this one was accessible AND is completed
            $canAccess = $canAccess && $obj->is_completed;
        }

        // Determine active object
        $activeObjectId = $request->query('object_id');
        $activeObject = null;

        if ($activeObjectId) {
            foreach ($flatObjects as $obj) {
                if ((int) $obj->id === (int) $activeObjectId && $obj->can_access) {
                    $activeObject = $obj;
                    break;
                }
            }
        }

        // If no active object specified or not accessible, default to first accessible one
        if (! $activeObject) {
            foreach ($flatObjects as $obj) {
                if ($obj->can_access) {
                    $activeObject = $obj;
                    break;
                }
            }
        }

        // If we still don't have an active object (e.g. classroom has no objects), pick first if any
        if (! $activeObject && count($flatObjects) > 0) {
            $activeObject = $flatObjects[0];
        }

        // If active object is a quiz, load historical submissions for this specific quiz
        $activeQuizSubmissions = collect();
        if ($activeObject && $activeObject->object_type === Quiz::class) {
            $activeQuizSubmissions = QuizSubmission::where('quiz_id', $activeObject->object_id)
                ->where('student_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($sub) use ($activeObject) {
                    return [
                        'id' => $sub->id,
                        'score' => $sub->score,
                        'submitted_at' => $sub->submitted_at ? $sub->submitted_at->toIso8601String() : $sub->created_at->toIso8601String(),
                        'is_passing' => $sub->score >= ($activeObject->object->passing_grade ?? 70),
                    ];
                });
        }

        return Inertia::render('classrooms/Show', [
            'classroom' => $classroom,
            'activeObject' => $activeObject,
            'activeQuizSubmissions' => $activeQuizSubmissions,
        ]);
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

    /**
     * Get the enrolled students of the classroom.
     */
    public function students(Classroom $classroom): JsonResponse
    {
        Gate::authorize('viewStudents', $classroom);

        $students = $classroom->enrollments()
            ->with('student')
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->student->id,
                'name' => $enrollment->student->name,
                'email' => $enrollment->student->email,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
            ]);

        return response()->json([
            'data' => $students,
        ]);
    }
}
