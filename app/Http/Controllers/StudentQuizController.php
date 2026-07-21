<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveQuizAnswerRequest;
use App\Http\Requests\StudentQuizSessionRequest;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\QuizSubmission;
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

        $hasActiveSession = QuizSession::where('student_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->exists();

        if (! $hasActiveSession && $quiz->max_attempts !== null) {
            $attemptsCount = $quiz->getAttemptsCount($request->user()->id);

            if ($attemptsCount >= $quiz->max_attempts) {
                return redirect()->back()->with('toast', [
                    'type' => 'error',
                    'message' => 'Batas maksimum percobaan kuis telah tercapai.',
                ]);
            }
        }

        $session = $studentQuizService->startSession($classroom, $quiz, $request->user()->id);

        if (! $session) {
            return redirect()->back()->with('toast', ['type' => 'error', 'message' => 'Kuis belum memiliki soal.']);
        }

        // Check if expired
        if (now()->greaterThan($session->expires_at)) {
            $submission = $studentQuizService->calculateAndSubmit($session);

            return redirect()->route('quizzes.submissions.show', $submission->id)
                ->with('toast', ['type' => 'error', 'message' => 'Waktu kuis telah habis dan disubmit otomatis.']);
        }

        return redirect()->route('quizzes.take', $session->id);
    }

    public function take(StudentQuizSessionRequest $request, QuizSession $session, StudentQuizService $studentQuizService)
    {
        $moduleObject = $session->quiz->moduleObjects()->first();
        $classroomSlug = $moduleObject->module->classroom->slug;

        if (now()->greaterThan($session->expires_at)) {
            $submission = $studentQuizService->calculateAndSubmit($session);

            return redirect()->route('quizzes.submissions.show', $submission->id)
                ->with('toast', ['type' => 'error', 'message' => 'Waktu kuis telah habis.']);
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
        $submission = $studentQuizService->calculateAndSubmit($session);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kuis berhasil disubmit.',
        ]);

        return redirect()->route('quizzes.submissions.show', $submission->id);
    }

    public function showSubmission(Request $request, QuizSubmission $submission)
    {
        if ($submission->student_id !== $request->user()->id) {
            abort(403);
        }

        $submission->load([
            'quiz',
            'quiz.questions' => fn ($q) => $q->withTrashed(),
            'quiz.questions.options' => fn ($q) => $q->withTrashed(),
        ]);

        $attemptsCount = $submission->quiz->getAttemptsCount($request->user()->id);
        $minAttempts = $submission->quiz->min_attempts_for_solution ?? 1;
        $canShowSolution = $attemptsCount >= $minAttempts;

        if (! $canShowSolution) {
            $submission->quiz->questions->each(function ($question) {
                $question->solution = null;
                $question->makeHidden('solution');
            });
        }

        $moduleObject = $submission->quiz->moduleObjects()->first();

        return Inertia::render('quizzes/SubmissionDetail', [
            'submission' => $submission,
            'quiz' => $submission->quiz,
            'classroomSlug' => $moduleObject->module->classroom->slug,
            'objectId' => $moduleObject->id,
            'canShowSolution' => $canShowSolution,
        ]);
    }
}
