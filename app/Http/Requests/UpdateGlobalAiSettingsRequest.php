<?php

namespace App\Http\Requests;

use App\Services\AiSettingsResolver;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'superadmin';
    }

    public function rules(): array
    {
        return [
            'feature' => ['sometimes', 'array'],
            'feature.quiz_generation_enabled' => ['sometimes', 'boolean'],
            'feature.quiz_insights_enabled' => ['sometimes', 'boolean'],
            'feature.class_summary_enabled' => ['sometimes', 'boolean'],
            'feature.assessment_analysis_enabled' => ['sometimes', 'boolean'],
            'prompt' => ['sometimes', 'array'],
            'prompt.quiz_generation' => ['sometimes', 'array'],
            'prompt.quiz_generation.system' => ['sometimes', 'string', 'max:5000'],
            'prompt.quiz_generation.user_template' => ['sometimes', 'string', 'max:12000'],
            'prompt.quiz_insights' => ['sometimes', 'array'],
            'prompt.quiz_insights.system' => ['sometimes', 'string', 'max:5000'],
            'prompt.quiz_insights.user_template' => ['sometimes', 'string', 'max:12000'],
            'prompt.class_summary' => ['sometimes', 'array'],
            'prompt.class_summary.system' => ['sometimes', 'string', 'max:5000'],
            'prompt.class_summary.user_template' => ['sometimes', 'string', 'max:12000'],
            'model' => ['sometimes', 'array'],
            'model.default' => ['sometimes', 'string', 'in:'.implode(',', array_keys(AiSettingsResolver::AVAILABLE_MODELS))],
            'model.max_tokens' => ['sometimes', 'integer', 'min:50', 'max:8192'],
        ];
    }
}
