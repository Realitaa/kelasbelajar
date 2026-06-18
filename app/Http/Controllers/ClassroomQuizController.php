<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Quiz;
use App\Services\ClassroomQuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ClassroomQuizController extends Controller
{
    public function show(Classroom $classroom, Quiz $quiz): JsonResponse
    {
        Gate::authorize('update', $classroom);

        if ($quiz->moduleObjects()->first()?->module->classroom_id !== $classroom->id) {
            abort(404);
        }

        $questions = $quiz->questions()->with('options')->get();

        return response()->json([
            'data' => $questions,
        ]);
    }

    public function updateQuestions(Request $request, Classroom $classroom, Quiz $quiz, ClassroomQuizService $quizService): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        if ($quiz->moduleObjects()->first()?->module->classroom_id !== $classroom->id) {
            abort(404);
        }

        $validated = $request->validate([
            'questions' => 'present|array',
            'questions.*.id' => 'nullable|integer',
            'questions.*.type' => 'required|string|in:PG,PG MCMA,PG K',
            'questions.*.question' => 'required', // Tiptap JSON array
            'questions.*.solution' => 'nullable', // Tiptap JSON array
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.id' => 'nullable|integer',
            'questions.*.options.*.option' => 'required', // Tiptap JSON array
            'questions.*.options.*.is_correct' => 'required|boolean',
        ]);

        // Additional validation for options correct answers
        foreach ($validated['questions'] as $qIndex => $question) {
            $correctCount = 0;
            foreach ($question['options'] as $option) {
                if ($option['is_correct']) {
                    $correctCount++;
                }
            }

            if ($question['type'] === 'PG' && $correctCount !== 1) {
                throw ValidationException::withMessages([
                    "questions.{$qIndex}.options" => 'Exactly one option must be marked as the correct answer.',
                ]);
            }
        }

        $quizService->updateQuestions($quiz, $validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Soal kuis berhasil disimpan.',
        ]);

        return redirect()->back();
    }
}
