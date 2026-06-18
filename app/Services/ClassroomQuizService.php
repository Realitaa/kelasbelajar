<?php

namespace App\Services;

use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

class ClassroomQuizService
{
    public function __construct(
        protected TiptapSanitizer $sanitizer
    ) {}

    public function updateQuestions(Quiz $quiz, array $validatedData): void
    {
        DB::transaction(function () use ($quiz, $validatedData) {
            $incomingQuestionIds = collect($validatedData['questions'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete questions not present in payload
            $quiz->questions()->whereNotIn('id', $incomingQuestionIds)->delete();

            // Temporarily update existing questions to a high position to avoid unique constraint collisions
            foreach ($validatedData['questions'] as $index => $qData) {
                if (! empty($qData['id'])) {
                    $quiz->questions()->where('id', $qData['id'])->update([
                        'position' => 100000 + $index + 1,
                    ]);
                }
            }

            foreach ($validatedData['questions'] as $index => $qData) {
                $questionData = [
                    'type' => $qData['type'],
                    'question' => $this->sanitizer->sanitize($qData['question']),
                    'solution' => isset($qData['solution']) ? $this->sanitizer->sanitize($qData['solution']) : null,
                    'position' => $index + 1,
                ];

                $questionModel = null;
                if (! empty($qData['id'])) {
                    $questionModel = $quiz->questions()->find($qData['id']);
                }

                if (! $questionModel) {
                    $questionModel = $quiz->questions()->create($questionData);
                } else {
                    $questionModel->update($questionData);
                }

                $incomingOptionIds = collect($qData['options'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Delete options not present in payload
                $questionModel->options()->whereNotIn('id', $incomingOptionIds)->delete();

                foreach ($qData['options'] as $oData) {
                    $optionData = [
                        'option' => $this->sanitizer->sanitize($oData['option']),
                        'is_correct' => $oData['is_correct'],
                    ];

                    if (! empty($oData['id'])) {
                        $optionModel = $questionModel->options()->find($oData['id']);
                        if ($optionModel) {
                            $optionModel->update($optionData);

                            continue;
                        }
                    }

                    $questionModel->options()->create($optionData);
                }
            }
        });
    }
}
