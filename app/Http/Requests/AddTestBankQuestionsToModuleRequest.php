<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTestBankQuestionsToModuleRequest extends FormRequest
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
            'test_bank_question_ids' => ['required', 'array', 'min:1', 'max:100'],
            'test_bank_question_ids.*' => ['required', 'integer', 'exists:test_bank_questions,id'],
        ];
    }
}
