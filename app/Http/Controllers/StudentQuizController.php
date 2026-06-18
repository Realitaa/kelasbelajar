<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveQuizAnswerRequest;
use App\Http\Requests\StudentQuizSessionRequest;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Services\StudentQuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StudentQuizController extends Controller
{
    public function start(Request $request, Classroom $classroom, Quiz $quiz, StudentQuizService $studentQuizService): RedirectResponse
    {
        Gate::authorize('view', $classroom);

        $session = $studentQuizService->startSession($classroom, $quiz, $request->user()->id);

        if (! $session) {
            return redirect()->back()->with('toast', ['type' => 'error', 'message' => 'Kuis belum memiliki soal.']);
        }

        // Check if expired
        if (now()->greaterThan($session->expires_at)) {
            $moduleObject = $session->quiz->moduleObjects()->first();
            $studentQuizService->calculateAndSubmit($session);

            return redirect()->route('classrooms.show', [
                'classroom' => $classroom->slug,
                'object_id' => $moduleObject->id,
            ])->with('toast', ['type' => 'error', 'message' => 'Waktu kuis telah habis dan disubmit otomatis.']);
        }

        return redirect()->route('quizzes.take', $session->id);
    }

    public function take(StudentQuizSessionRequest $request, QuizSession $session, StudentQuizService $studentQuizService)
    {
        $moduleObject = $session->quiz->moduleObjects()->first();
        $classroomSlug = $moduleObject->module->classroom->slug;

        if (now()->greaterThan($session->expires_at)) {
            $studentQuizService->calculateAndSubmit($session);

            return redirect()->route('classrooms.show', [
                'classroom' => $classroomSlug,
                'object_id' => $moduleObject->id,
            ])->with('toast', ['type' => 'error', 'message' => 'Waktu kuis telah habis.']);
        }

        $session->load('quiz');

        return Inertia::render('quizzes/Take', [
            'session' => $session,
            'quiz' => $session->quiz,
            'totalQuestions' => count($session->questions_order),
            'classroomSlug' => $classroomSlug,
        ]);
    }

    public function getQuestion(StudentQuizSessionRequest $request, QuizSession $session, int $index, StudentQuizService $studentQuizService): JsonResponse
    {
        if (now()->greaterThan($session->expires_at)) {
            return response()->json(['error' => 'Waktu habis'], 400);
        }

        $questionData = $studentQuizService->getQuestionData($session, $index);

        if (! $questionData) {
            return response()->json(['error' => 'Soal tidak ditemukan'], 404);
        }

        return response()->json($questionData);
    }

    public function saveAnswer(SaveQuizAnswerRequest $request, QuizSession $session, StudentQuizService $studentQuizService): JsonResponse
    {
        if (now()->greaterThan($session->expires_at)) {
            return response()->json(['error' => 'Waktu habis'], 400);
        }

        $studentQuizService->saveAnswer($session, $request->validated());

        return response()->json(['success' => true]);
    }

    public function submit(StudentQuizSessionRequest $request, QuizSession $session, StudentQuizService $studentQuizService): RedirectResponse
    {
        $moduleObject = $session->quiz->moduleObjects()->first();
        $classroomSlug = $moduleObject->module->classroom->slug;

        $studentQuizService->calculateAndSubmit($session);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kuis berhasil disubmit.',
        ]);

        return redirect()->route('classrooms.show', [
            'classroom' => $classroomSlug,
            'object_id' => $moduleObject->id,
        ]);
    }
}
