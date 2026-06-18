<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSession;
use App\Models\QuizSubmission;

class StudentQuizService
{
    public function startSession(Classroom $classroom, Quiz $quiz, int $studentId): ?QuizSession
    {
        $session = QuizSession::where('student_id', $studentId)
            ->where('quiz_id', $quiz->id)
            ->first();

        if ($session) {
            return $session;
        }

        $questions = $quiz->questions()->with('options')->get();
        if ($questions->isEmpty()) {
            return null;
        }

        $questionsOrder = $questions->pluck('id')->shuffle()->toArray();
        $optionsOrder = [];

        foreach ($questions as $question) {
            $optionsOrder[$question->id] = $question->options->pluck('id')->shuffle()->toArray();
        }

        return QuizSession::create([
            'student_id' => $studentId,
            'quiz_id' => $quiz->id,
            'questions_order' => $questionsOrder,
            'options_order' => $optionsOrder,
            'answers' => [],
            'started_at' => now(),
            'expires_at' => now()->addMinutes($quiz->time_limit),
        ]);
    }

    public function getQuestionData(QuizSession $session, int $index): ?array
    {
        $questionsOrder = $session->questions_order;
        if (! isset($questionsOrder[$index])) {
            return null;
        }

        $questionId = $questionsOrder[$index];
        $question = QuizQuestion::with('options')->find($questionId);

        if (! $question) {
            return null;
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

        return [
            'id' => $question->id,
            'question' => $question->question,
            'options' => $shuffledOptions,
            'answer' => $session->answers[$question->id] ?? null,
        ];
    }

    public function saveAnswer(QuizSession $session, array $validated): void
    {
        $answers = $session->answers ?? [];
        $answers[$validated['question_id']] = $validated['answer'];

        $session->update(['answers' => $answers]);
    }

    public function calculateAndSubmit(QuizSession $session): void
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
