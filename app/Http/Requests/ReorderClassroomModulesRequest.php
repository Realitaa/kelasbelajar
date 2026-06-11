<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderClassroomModulesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $classroom = $this->route('classroom');

        return $this->user() && $this->user()->can('update', $classroom);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'modules' => ['required', 'array'],
            'modules.*.id' => ['required', 'exists:classroom_modules,id'],
            'modules.*.position' => ['required', 'integer'],
        ];
    }
}
