<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateQuizQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('classroom'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'questions' => 'present|array',
            'questions.*.id' => 'nullable|integer',
            'questions.*.type' => 'required|string|in:PG,PG MCMA,PG K',
            'questions.*.question' => 'required', // Tiptap JSON array
            'questions.*.solution' => 'nullable', // Tiptap JSON array
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.id' => 'nullable|integer',
            'questions.*.options.*.option' => 'required', // Tiptap JSON array
            'questions.*.options.*.is_correct' => 'required|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $questions = $this->input('questions', []);
            foreach ($questions as $qIndex => $question) {
                $correctCount = 0;
                foreach ($question['options'] ?? [] as $option) {
                    if (! empty($option['is_correct'])) {
                        $correctCount++;
                    }
                }

                if (($question['type'] ?? '') === 'PG' && $correctCount !== 1) {
                    $validator->errors()->add("questions.{$qIndex}.options", 'Exactly one option must be marked as the correct answer.');
                }
            }
        });
    }
}
