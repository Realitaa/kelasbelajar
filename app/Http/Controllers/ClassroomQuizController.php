<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateQuizQuestionsRequest;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Services\ClassroomQuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
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

    public function updateQuestions(UpdateQuizQuestionsRequest $request, Classroom $classroom, Quiz $quiz, ClassroomQuizService $quizService): RedirectResponse
    {
        if ($quiz->moduleObjects()->first()?->module->classroom_id !== $classroom->id) {
            abort(404);
        }

        $validated = $request->validated();

        $quizService->updateQuestions($quiz, $validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Soal kuis berhasil disimpan.',
        ]);

        return redirect()->back();
    }
}
