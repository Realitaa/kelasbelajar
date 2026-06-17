<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function updateQuestions(Request $request, Classroom $classroom, Quiz $quiz): RedirectResponse
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
            'questions.*.solution' => 'nullable|array', // Tiptap JSON array
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

        DB::transaction(function () use ($quiz, $validated) {
            $incomingQuestionIds = collect($validated['questions'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete questions not present in payload
            $quiz->questions()->whereNotIn('id', $incomingQuestionIds)->delete();

            // Temporarily update existing questions to a high position to avoid unique constraint collisions
            foreach ($validated['questions'] as $index => $qData) {
                if (! empty($qData['id'])) {
                    $quiz->questions()->where('id', $qData['id'])->update([
                        'position' => 100000 + $index + 1,
                    ]);
                }
            }

            foreach ($validated['questions'] as $index => $qData) {
                $question = null;
                if (! empty($qData['id'])) {
                    $question = $quiz->questions()->find($qData['id']);
                }

                if (! $question) {
                    $question = $quiz->questions()->create([
                        'type' => $qData['type'],
                        'question' => $qData['question'],
                        'solution' => $qData['solution'] ?? null,
                        'position' => $index + 1,
                    ]);
                } else {
                    $question->update([
                        'type' => $qData['type'],
                        'question' => $qData['question'],
                        'solution' => $qData['solution'] ?? null,
                        'position' => $index + 1,
                    ]);
                }

                $incomingOptionIds = collect($qData['options'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Delete options not present in payload
                $question->options()->whereNotIn('id', $incomingOptionIds)->delete();

                foreach ($qData['options'] as $oData) {
                    if (! empty($oData['id'])) {
                        $option = $question->options()->find($oData['id']);
                        if ($option) {
                            $option->update([
                                'option' => $oData['option'],
                                'is_correct' => $oData['is_correct'],
                            ]);

                            continue;
                        }
                    }

                    $question->options()->create([
                        'option' => $oData['option'],
                        'is_correct' => $oData['is_correct'],
                    ]);
                }
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Soal kuis berhasil disimpan.',
        ]);

        return redirect()->back();
    }
}
