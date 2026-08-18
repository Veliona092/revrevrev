<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'superadmin'], true);
    }

    public function rules(): array
    {
        return [
            'features' => ['sometimes', 'array'],
            'features.quiz_generation_enabled' => ['sometimes', 'boolean'],
            'features.quiz_insights_enabled' => ['sometimes', 'boolean'],
            'features.class_summary_enabled' => ['sometimes', 'boolean'],
            'features.assessment_analysis_enabled' => ['sometimes', 'boolean'],
            'quiz_defaults' => ['sometimes', 'array'],
            'quiz_defaults.question_count' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'quiz_defaults.difficulty' => ['sometimes', 'in:Easy,Normal,Hard'],
        ];
    }
}
