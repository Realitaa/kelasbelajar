<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomComment;
use App\Models\ClassroomEnrollment;
use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Models\User;

class DashboardService
{
    public function getAdminStats(): array
    {
        return [
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
    }

    public function getRecentUsers(int $limit = 5)
    {
        return User::orderBy('created_at', 'desc')
            ->take($limit)
            ->get(['id', 'name', 'email', 'role', 'created_at']);
    }

    public function getRecentClassrooms(int $limit = 5)
    {
        return Classroom::with('educator')
            ->withCount('enrollments')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
