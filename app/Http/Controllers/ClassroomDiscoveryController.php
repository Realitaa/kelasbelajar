<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomDiscoveryController extends Controller
{
    /**
     * Display a listing of published classrooms for discovery.
     */
    public function index(Request $request): Response
    {
        if ($request->user()->role !== 'student') {
            abort(403);
        }

        $query = $request->input('query');
        $user = $request->user();

        $classrooms = Classroom::where('is_published', true)
            ->when($query, function ($builder, $search) {
                $builder->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('unique_code', 'like', "%{$search}%")
                        ->orWhereHas('educator', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->with(['educator', 'enrollments' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('classrooms/Discovery', [
            'classrooms' => $classrooms,
            'filters' => [
                'query' => $query,
            ],
        ]);
    }

    /**
     * Enroll the student in the specified classroom.
     */
    public function enroll(Request $request, Classroom $classroom): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'student') {
            abort(403);
        }

        if (! $classroom->is_published) {
            abort(404);
        }

        $alreadyEnrolled = ClassroomEnrollment::where('classroom_id', $classroom->id)
            ->where('student_id', $user->id)
            ->exists();

        if (! $alreadyEnrolled) {
            ClassroomEnrollment::create([
                'classroom_id' => $classroom->id,
                'student_id' => $user->id,
                'enrolled_at' => now(),
            ]);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Berhasil bergabung ke kelas {$classroom->title}.",
            ]);
        }

        return redirect()->back();
    }
}
