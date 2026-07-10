<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        return $this->user() && $this->user()->can('update', $comment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (! $this->hasActualContent($value)) {
                        $fail('Konten diskusi tidak boleh kosong.');
                    }
                },
            ],
        ];
    }

    /**
     * Recursively check if the Tiptap content has actual text/media elements.
     */
    protected function hasActualContent(?array $node): bool
    {
        if (! $node) {
            return false;
        }

        if (isset($node['type'])) {
            if (in_array($node['type'], ['text', 'image', 'youtube', 'inlineMath', 'blockMath', 'table', 'slideshow'])) {
                if ($node['type'] === 'text' && isset($node['text'])) {
                    return trim($node['text']) !== '';
                }

                return true;
            }
        }

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                if (is_array($child) && $this->hasActualContent($child)) {
                    return true;
                }
            }
        }

        return false;
    }
}
