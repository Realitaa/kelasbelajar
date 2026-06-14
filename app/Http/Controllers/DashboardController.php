<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomComment;
use App\Models\ClassroomEnrollment;
use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the admin dashboard with system statistics.
     */
    public function index(Request $request): Response
    {
        abort_if($request->user()->role !== 'administrator', 403, 'Akses ditolak. Halaman ini hanya untuk Administrator.');

        $stats = [
            'users' => [
                'total' => User::count(),
                'student' => User::where('role', 'student')->count(),
                'educator' => User::where('role', 'educator')->count(),
                'administrator' => User::where('role', 'administrator')->count(),
            ],
            'classrooms' => [
                'total' => Classroom::count(),
                'published' => Classroom::where('is_published', true)->count(),
                'draft' => Classroom::where('is_published', false)->count(),
            ],
            'enrollments' => [
                'total' => ClassroomEnrollment::count(),
            ],
            'quizzes' => [
                'total' => Quiz::count(),
                'submissions' => QuizSubmission::count(),
            ],
            'comments' => [
                'total' => ClassroomComment::count(),
            ],
            'content' => [
                'modules' => ClassroomModule::count(),
                'learning_contents' => LearningContent::count(),
            ],
        ];

        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        $recentClassrooms = Classroom::with('educator')
            ->withCount('enrollments')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentClassrooms' => $recentClassrooms,
        ]);
    }
}
