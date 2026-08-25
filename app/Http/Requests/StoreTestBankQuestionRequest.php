<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestBankQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'admin', 'superadmin'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'max:1000'],
            'correct_option' => ['required', 'string', 'max:10'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'difficulty' => ['required', Rule::in(['Average', 'Normal', 'Hard'])],
            'topic' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(['draft', 'approved'])],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if (! array_key_exists($this->input('correct_option'), $this->input('options', []))) {
                $validator->errors()->add('correct_option', 'The correct answer must be one of the supplied choices.');
            }
        }];
    }
}
