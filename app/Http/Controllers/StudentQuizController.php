<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSession;
use App\Models\QuizSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StudentQuizController extends Controller
{
    public function start(Request $request, Classroom $classroom, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('view', $classroom);

        // Check if session already exists
        $session = QuizSession::where('student_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        if ($session) {
            // Check if expired
            if (now()->greaterThan($session->expires_at)) {
                $moduleObject = $session->quiz->moduleObjects()->first();
                // Auto submit if expired
                $this->calculateAndSubmit($session);

                return redirect()->route('classrooms.show', [
                    'classroom' => $classroom->slug, 
                    'object_id' => $moduleObject->id
                ])->with('toast', ['type' => 'error', 'message' => 'Waktu kuis telah habis dan disubmit otomatis.']);
            }

            return redirect()->route('quizzes.take', $session->id);
        }



        // Create new session
        $questions = $quiz->questions()->with('options')->get();
        if ($questions->isEmpty()) {
            return redirect()->back()->with('toast', ['type' => 'error', 'message' => 'Kuis belum memiliki soal.']);
        }

        $questionsOrder = $questions->pluck('id')->shuffle()->toArray();
        $optionsOrder = [];

        foreach ($questions as $question) {
            $optionsOrder[$question->id] = $question->options->pluck('id')->shuffle()->toArray();
        }

        $session = QuizSession::create([
            'student_id' => $request->user()->id,
            'quiz_id' => $quiz->id,
            'questions_order' => $questionsOrder,
            'options_order' => $optionsOrder,
            'answers' => [],
            'started_at' => now(),
            'expires_at' => now()->addMinutes($quiz->time_limit),
        ]);

        return redirect()->route('quizzes.take', $session->id);
    }

    public function take(Request $request, QuizSession $session)
    {
        if ($session->student_id !== $request->user()->id) {
            abort(403);
        }

        $moduleObject = $session->quiz->moduleObjects()->first();
        $classroomSlug = $moduleObject->module->classroom->slug;

        if (now()->greaterThan($session->expires_at)) {
            $this->calculateAndSubmit($session);

            return redirect()->route('classrooms.show', [
                'classroom' => $classroomSlug,
                'object_id' => $moduleObject->id
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

    public function getQuestion(Request $request, QuizSession $session, int $index): JsonResponse
    {
        if ($session->student_id !== $request->user()->id) {
            abort(403);
        }

        if (now()->greaterThan($session->expires_at)) {
            return response()->json(['error' => 'Waktu habis'], 400);
        }

        $questionsOrder = $session->questions_order;
        if (! isset($questionsOrder[$index])) {
            return response()->json(['error' => 'Soal tidak ditemukan'], 404);
        }

        $questionId = $questionsOrder[$index];
        $question = QuizQuestion::with('options')->find($questionId);

        if (! $question) {
            return response()->json(['error' => 'Soal tidak ditemukan'], 404);
        }

        // Map the options to the shuffled order
        $shuffledOptionIds = $session->options_order[$questionId];
        $options = $question->options->keyBy('id');

        $shuffledOptions = [];
        foreach ($shuffledOptionIds as $optionId) {
            if (isset($options[$optionId])) {
                $option = $options[$optionId];
                // Hide is_correct
                $shuffledOptions[] = [
                    'id' => $option->id,
                    'option' => $option->option,
                ];
            }
        }

        return response()->json([
            'id' => $question->id,
            'question' => $question->question,
            'options' => $shuffledOptions,
            'answer' => $session->answers[$question->id] ?? null,
        ]);
    }

    public function saveAnswer(Request $request, QuizSession $session): JsonResponse
    {
        if ($session->student_id !== $request->user()->id) {
            abort(403);
        }

        if (now()->greaterThan($session->expires_at)) {
            return response()->json(['error' => 'Waktu habis'], 400);
        }

        $validated = $request->validate([
            'question_id' => 'required|integer',
            'answer' => 'nullable', // integer for single choice, array for multiple choice, string for text
        ]);

        $answers = $session->answers ?? [];
        $answers[$validated['question_id']] = $validated['answer'];

        $session->update(['answers' => $answers]);

        return response()->json(['success' => true]);
    }

    public function submit(Request $request, QuizSession $session): RedirectResponse
    {
        if ($session->student_id !== $request->user()->id) {
            abort(403);
        }

        $moduleObject = $session->quiz->moduleObjects()->first();
        $classroomSlug = $moduleObject->module->classroom->slug;
        
        $this->calculateAndSubmit($session);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kuis berhasil disubmit.',
        ]);

        return redirect()->route('classrooms.show', [
            'classroom' => $classroomSlug,
            'object_id' => $moduleObject->id
        ]);
    }

    private function calculateAndSubmit(QuizSession $session): void
    {
        $quiz = $session->quiz;
        $questions = $quiz->questions()->with('options')->get()->keyBy('id');

        $score = 0;
        $totalQuestions = $questions->count();
        $answers = $session->answers ?? [];

        if ($totalQuestions > 0) {
            $correctCount = 0;
            foreach ($questions as $question) {
                $studentAnswer = $answers[$question->id] ?? null;

                if ($studentAnswer !== null) {
                    $correctOption = $question->options->where('is_correct', true)->first();
                    if ($correctOption && (int) $studentAnswer === $correctOption->id) {
                        $correctCount++;
                    }
                }
            }

            $score = round(($correctCount / $totalQuestions) * 100);
        }

        QuizSubmission::create([
            'student_id' => $session->student_id,
            'quiz_id' => $quiz->id,
            'score' => $score,
            'submitted_at' => now(),
        ]);

        $session->delete();
    }
}
