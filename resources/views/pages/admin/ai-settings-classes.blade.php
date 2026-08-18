@extends('layouts.appAdmin')

@section('title', 'Class AI Settings')
@section('page-heading', 'Class AI Settings')

@section('header-actions')
    @if(Auth::user()?->role === 'superadmin')
        <a class="rv-btn rv-btn-secondary" href="{{ route('superadmin.ai-settings') }}">
            <i class="fas fa-brain"></i> Global Settings
        </a>
    @endif
@endsection

@section('content')
<style>
    .aic-wrap { display: flex; flex-direction: column; gap: 16px; }
    .aic-sub { font-size: 15px; color: #64748b; margin-top: -8px; }

    .aic-flash {
        border-radius: 12px;
        padding: 11px 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        border: 1px solid transparent;
    }

    .aic-flash.success {
        background: #e8f7ee;
        border-color: #c8ead6;
        color: #17653f;
    }

    .aic-flash.error {
        background: #fff2f2;
        border-color: #f3c9c9;
        color: #8e1f1f;
    }

    .aic-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 12px;
        padding: 16px;
    }

    .aic-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }

    .aic-name { margin: 0; font-size: 20px; font-weight: 500; color: #111; } 
    .aic-meta { margin: 2px 0 0; font-size: 15px; color: #64748b; }

    .aic-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .aic-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #efefef;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 16px;
        color: #333;
    }

    .aic-defaults {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .aic-field { display: flex; flex-direction: column; gap: 5px; }
    .aic-label { font-size: 14px; color: #475569; font-weight: 500; }        

    .aic-input,
    .aic-select {
        border: 1px solid #e5e5e5;
        border-radius: 10px;
        height: 42px;
        padding: 0 10px;
        font-size: 16px;
        color: #222;
        background: #fff;
    }

    .aic-actions { display: flex; justify-content: flex-end; }

    .aic-save {
        height: 40px;
        border: 0;
        border-radius: 10px;
        padding: 0 16px;
        background: #111;
        color: #fff;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
    }

    .aic-empty {
        background: #fff;
        border: 1px dashed #e5e5e5;
        border-radius: 12px;
        padding: 22px;
        color: #888;
        font-size: 16px;
        text-align: center;
    }
</style>

<div class="aic-wrap">
    <p class="aic-sub">Configure AI behavior per class. Global superadmin settings still apply as top-level defaults.</p>

    @if (session('status'))
        <div class="aic-flash success" role="status" aria-live="polite">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="aic-flash error" role="alert" aria-live="assertive">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @forelse($classes as $class)
        @php
            $settings = $classSettings[$class->id] ?? [
                'features' => [
                    'quiz_generation_enabled' => true,
                    'quiz_insights_enabled' => true,
                    'class_summary_enabled' => true,
                    'assessment_analysis_enabled' => true,
                ],
                'quiz_defaults' => [
                    'question_count' => 10,
                    'difficulty' => 'Normal',
                ],
            ];
        @endphp

        <form class="aic-card" method="POST" action="{{ route('admin.class-ai-settings.update', $class) }}" data-class-id="{{ $class->id }}">
            @csrf

            <div class="aic-head">
                <div>
                    <p class="aic-name">{{ $class->name }}</p>
                    <p class="aic-meta">
                        {{ $class->code ? 'Code: ' . $class->code : 'No code' }}
                        {{ $class->school_year ? ' · SY ' . $class->school_year : '' }}
                    </p>
                </div>
            </div>

            <div class="aic-grid">
                <label class="aic-toggle">
                    <span>Quiz generation</span>
                    <span>
                        <input type="hidden" name="features[quiz_generation_enabled]" value="0">
                        <input type="checkbox" name="features[quiz_generation_enabled]" value="1"
                            {{ data_get($settings, 'features.quiz_generation_enabled', true) ? 'checked' : '' }}>
                    </span>
                </label>

                <label class="aic-toggle">
                    <span>Quiz insights</span>
                    <span>
                        <input type="hidden" name="features[quiz_insights_enabled]" value="0">
                        <input type="checkbox" name="features[quiz_insights_enabled]" value="1"
                            {{ data_get($settings, 'features.quiz_insights_enabled', true) ? 'checked' : '' }}>
                    </span>
                </label>

                <label class="aic-toggle">
                    <span>Class summary</span>
                    <span>
                        <input type="hidden" name="features[class_summary_enabled]" value="0">
                        <input type="checkbox" name="features[class_summary_enabled]" value="1"
                            {{ data_get($settings, 'features.class_summary_enabled', true) ? 'checked' : '' }}>
                    </span>
                </label>

                <label class="aic-toggle">
                    <span>Assessment analysis</span>
                    <span>
                        <input type="hidden" name="features[assessment_analysis_enabled]" value="0">
                        <input type="checkbox" name="features[assessment_analysis_enabled]" value="1"
                            {{ data_get($settings, 'features.assessment_analysis_enabled', true) ? 'checked' : '' }}>
                    </span>
                </label>
            </div>

            <div class="aic-defaults">
                <div class="aic-field">
                    <label class="aic-label" for="question_count_{{ $class->id }}">Default question count</label>
                    <input id="question_count_{{ $class->id }}" class="aic-input" type="number" min="1" max="20"
                        name="quiz_defaults[question_count]"
                        value="{{ data_get($settings, 'quiz_defaults.question_count', 10) }}">
                </div>

                <div class="aic-field">
                    <label class="aic-label" for="difficulty_{{ $class->id }}">Default difficulty</label>
                    <select id="difficulty_{{ $class->id }}" class="aic-select" name="quiz_defaults[difficulty]">
                        @php $difficulty = data_get($settings, 'quiz_defaults.difficulty', 'Normal'); @endphp
                        <option value="Easy" {{ $difficulty === 'Easy' ? 'selected' : '' }}>Easy</option>
                        <option value="Normal" {{ $difficulty === 'Normal' ? 'selected' : '' }}>Normal</option>
                        <option value="Hard" {{ $difficulty === 'Hard' ? 'selected' : '' }}>Difficult</option>
                    </select>
                </div>
            </div>

            <div class="aic-actions">
                <button class="aic-save" type="submit" data-default-label="Save Class Settings">Save Class Settings</button>
            </div>
        </form>
    @empty
        <div class="aic-empty">No classes found yet. Create classes first to configure per-class AI settings.</div>
    @endforelse
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const forms = Array.from(document.querySelectorAll('.aic-card'));

    function setButtonState(button, label, disabled) {
        if (!button) {
            return;
        }

        button.textContent = label;
        button.disabled = disabled;
        button.style.opacity = disabled ? '0.75' : '1';
    }

    function saveClassSettings(form, button) {
        if (!form || form.dataset.saving === '1') {
            return;
        }

        form.dataset.saving = '1';
        setButtonState(button, 'Saving...', true);

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': String(formData.get('_token') || ''),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || payload.success === false) {
                    throw new Error(payload.message || 'Failed to save class settings.');
                }

                const settings = payload.settings || {};
                const features = settings.features || {};
                const quizDefaults = settings.quiz_defaults || {};

                const quizGenCheckbox = form.querySelector('input[type="checkbox"][name="features[quiz_generation_enabled]"]');
                const quizInsightsCheckbox = form.querySelector('input[type="checkbox"][name="features[quiz_insights_enabled]"]');
                const classSummaryCheckbox = form.querySelector('input[type="checkbox"][name="features[class_summary_enabled]"]');
                const assessmentAnalysisCheckbox = form.querySelector('input[type="checkbox"][name="features[assessment_analysis_enabled]"]');
                const questionCountInput = form.querySelector('input[name="quiz_defaults[question_count]"]');
                const difficultySelect = form.querySelector('select[name="quiz_defaults[difficulty]"]');

                if (quizGenCheckbox && Object.prototype.hasOwnProperty.call(features, 'quiz_generation_enabled')) {
                    quizGenCheckbox.checked = Boolean(features.quiz_generation_enabled);
                }

                if (quizInsightsCheckbox && Object.prototype.hasOwnProperty.call(features, 'quiz_insights_enabled')) {
                    quizInsightsCheckbox.checked = Boolean(features.quiz_insights_enabled);
                }

                if (classSummaryCheckbox && Object.prototype.hasOwnProperty.call(features, 'class_summary_enabled')) {
                    classSummaryCheckbox.checked = Boolean(features.class_summary_enabled);
                }

                if (assessmentAnalysisCheckbox && Object.prototype.hasOwnProperty.call(features, 'assessment_analysis_enabled')) {
                    assessmentAnalysisCheckbox.checked = Boolean(features.assessment_analysis_enabled);
                }

                if (questionCountInput && Object.prototype.hasOwnProperty.call(quizDefaults, 'question_count')) {
                    questionCountInput.value = String(quizDefaults.question_count);
                }

                if (difficultySelect && Object.prototype.hasOwnProperty.call(quizDefaults, 'difficulty')) {
                    difficultySelect.value = String(quizDefaults.difficulty);
                }

                const classId = form.dataset.classId;
                if (classId) {
                    try {
                        const payloadToStore = JSON.stringify({
                            class_id: Number(classId),
                            settings,
                            updated_at: Date.now(),
                        });
                        localStorage.setItem(`aiSettings.class.${classId}`, payloadToStore);
                        localStorage.setItem('aiSettings.lastUpdateAt', String(Date.now()));
                    } catch (_) {
                        // Ignore storage failures (private mode / quota) and keep save flow intact.
                    }
                }

                setButtonState(button, 'Saved', true);
                setTimeout(() => {
                    setButtonState(button, button?.dataset.defaultLabel || 'Save Class Settings', false);
                }, 700);
            })
            .catch(() => {
                setButtonState(button, 'Save Failed', true);
                setTimeout(() => {
                    setButtonState(button, button?.dataset.defaultLabel || 'Save Class Settings', false);
                }, 1000);
            })
            .finally(() => {
                form.dataset.saving = '0';
            });
    }

    forms.forEach((form) => {
        const saveButton = form.querySelector('.aic-save');

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            saveClassSettings(form, saveButton);
        });
    });
});
</script>
@endsection
