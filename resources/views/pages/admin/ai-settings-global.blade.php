@extends('layouts.appAdmin')

@section('title', 'Global AI Settings')
@section('page-heading', 'Global AI Settings')

@section('header-actions')
    <a class="rv-btn rv-btn-secondary" href="{{ route('admin.class-ai-settings') }}">
        <i class="fas fa-sliders-h"></i> Class Settings
    </a>
@endsection

@section('content')
<style>
    .aig-wrap { display: flex; flex-direction: column; gap: 14px; }

    .aig-sub { font-size: 15px; color: #64748b; margin: -8px 0 6px; }

    /* ── Card ── */
    .aig-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 12px;
        overflow: hidden;
    }

    .aig-card-head {
        padding: 13px 18px;
        border-bottom: 1px solid #f3f3f3;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .aig-card-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }

    .aig-card-title { font-size: 20px; font-weight: 500; color: #111; margin: 0; }
    .aig-card-sub   { font-size: 15px; color: #64748b; margin: 0; margin-left: auto; }

    .aig-card-body { padding: 16px 18px; }

    /* ── Toggle grid ── */
    .aig-toggle-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }

    .aig-toggle-wrap {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; padding: 12px 14px;
        border: 1px solid #f0f0f0; border-radius: 9px;
        background: #fafafa; cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
    }

    .aig-toggle-wrap:has(input:checked) {
        border-color: #d4eddf; background: #f0fdf7;
    }

    .aig-toggle-left { display: flex; align-items: center; gap: 9px; }

    .aig-toggle-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #e4e4e4; flex-shrink: 0; transition: background 0.2s;
    }

    .aig-toggle-wrap:has(input:checked) .aig-toggle-dot { background: #1d9e75; }

    .aig-toggle-name { font-size: 16px; font-weight: 500; color: #334155; }  

    /* Custom toggle switch */
    .aig-switch { position: relative; width: 36px; height: 20px; flex-shrink: 0; }
    .aig-switch input { opacity: 0; width: 0; height: 0; position: absolute; }

    .aig-switch-track {
        position: absolute; inset: 0; border-radius: 99px;
        background: #e4e4e4; cursor: pointer; transition: background 0.2s;
    }

    .aig-switch-track::after {
        content: ''; position: absolute;
        width: 14px; height: 14px; border-radius: 50%;
        background: #fff; top: 3px; left: 3px;
        transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    .aig-switch input:checked + .aig-switch-track { background: #1d9e75; }
    .aig-switch input:checked + .aig-switch-track::after { transform: translateX(16px); }

    /* ── Form fields ── */
    .aig-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
    .aig-field:last-child { margin-bottom: 0; }

    .aig-label {
        font-size: 13px; font-weight: 500; color: #64748b;
        letter-spacing: 0.05em; text-transform: uppercase;
    }

    .aig-input, .aig-select, .aig-textarea {
        width: 100%; padding: 9px 12px;
        border: 1px solid #e4e4e4; border-radius: 8px;
        font-family: 'DM Sans', sans-serif; font-size: 16px; color: #111;
        background: #fff; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        appearance: none;
    }

    .aig-textarea { min-height: 110px; resize: vertical; line-height: 1.6; }

    .aig-input:focus, .aig-select:focus, .aig-textarea:focus {
        border-color: #111;
        box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
    }

    .aig-hint { font-size: 14px; color: #64748b; margin: 2px 0 0; }

    /* ── Prompt sections — two-col ── */
    .aig-prompt-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    @media (max-width: 700px) {
        .aig-prompt-grid { grid-template-columns: 1fr; }
        .aig-toggle-grid { grid-template-columns: 1fr; }
    }

    /* ── Model select with custom arrow ── */
    .aig-select-wrap { position: relative; }
    .aig-select-wrap .aig-select { padding-right: 32px; cursor: pointer; }
    .aig-select-wrap::after {
        content: '\f078';
        font-family: 'Font Awesome 5 Free';
        font-weight: 500;
        font-size: 12px;
        color: #aaa;
        position: absolute;
        right: 12px; top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }

    /* ── Model card — two-col ── */
    .aig-model-grid {
        display: grid;
        grid-template-columns: 1fr 200px;
        gap: 14px;
        align-items: start;
    }

    @media (max-width: 600px) {
        .aig-model-grid { grid-template-columns: 1fr; }
    }

    /* ── Save bar ── */
    .aig-save-bar {
        display: flex; justify-content: flex-end;
        padding: 14px 18px; border-top: 1px solid #f3f3f3;
        background: #fafafa;
    }

    .aig-save-btn {
        height: 40px; padding: 0 22px;
        background: #0f0f0f; color: #fff; border: none;
        border-radius: 8px; font-family: 'DM Sans', sans-serif;
        font-size: 16px; font-weight: 500; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        transition: background 0.15s, transform 0.1s;
    }

    .aig-save-btn:hover  { background: #333; }
    .aig-save-btn:active { transform: scale(0.98); }

    /* ── Alert ── */
    .aig-alert { padding: 12px 14px; border-radius: 8px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .aig-alert.success { background: #e1f5ee; color: #0f6e56; }
    .aig-alert.error   { background: #fcebeb; color: #a32d2d; }

    /* ── Prompt label tag ── */
    .aig-prompt-tag {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 12px; font-weight: 500; padding: 3px 8px;
        border-radius: 99px; letter-spacing: 0.04em; text-transform: uppercase;
    }

    .aig-prompt-tag.system { background: #eff6ff; color: #2563eb; }
    .aig-prompt-tag.user   { background: #f3f0ff; color: #6d28d9; }

    /* ── Friendly tips ── */
    .aig-tips {
        margin: 0;
        padding-left: 20px;
        display: grid;
        gap: 8px;
    }

    .aig-tips li {
        font-size: 15px;
        color: #475569;
        line-height: 1.6;
    }
</style>

<form method="POST" action="{{ route('superadmin.ai-settings.update') }}">
@csrf

<div class="aig-wrap">

    <p class="aig-sub">Control global AI feature toggles, prompt templates, and model settings for all classes.</p>

    <div class="aig-card">
        <div class="aig-card-head">
            <div class="aig-card-icon" style="background:#eef7ff;color:#1d4ed8;">
                <i class="fas fa-lightbulb"></i>
            </div>
            <p class="aig-card-title">Quick Tips</p>
        </div>
        <div class="aig-card-body">
            <ul class="aig-tips">
                <li>Start with defaults first. Only change one setting at a time so it is easy to see what improved.</li>
                <li>If quiz questions feel too hard or too easy, adjust the difficulty before changing anything else.</li>
                <li>Keep prompt text short and clear. Simple instructions usually give more consistent results.</li>
                <li>If AI answers are too long, lower Max Tokens. If answers feel cut off, increase it a little.</li>
                <li>After saving, test with one class before applying bigger changes across all classes.</li>
            </ul>
        </div>
    </div>

    @if(session('status'))
        <div class="aig-alert success"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="aig-alert error">
            <i class="fas fa-exclamation-circle"></i>
            <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        </div>
    @endif

    {{-- Feature toggles --}}
    <div class="aig-card">
        <div class="aig-card-head">
            <div class="aig-card-icon" style="background:#eff6ff;color:#2563eb;">
                <i class="fas fa-toggle-on"></i>
            </div>
            <p class="aig-card-title">Feature Toggles</p>
            <span class="aig-card-sub">Enable or disable AI features globally</span>
        </div>
        <div class="aig-card-body">
            <div class="aig-toggle-grid">

                <label class="aig-toggle-wrap">
                    <div class="aig-toggle-left">
                        <span class="aig-toggle-dot"></span>
                        <span class="aig-toggle-name">Quiz Generation</span>
                    </div>
                    <div class="aig-switch">
                        <input type="hidden" name="feature[quiz_generation_enabled]" value="0">
                        <input type="checkbox" name="feature[quiz_generation_enabled]" value="1"
                               {{ old('feature.quiz_generation_enabled', ($settings['feature.quiz_generation_enabled'] ?? true) ? 1 : 0) ? 'checked' : '' }}>
                        <span class="aig-switch-track"></span>
                    </div>
                </label>

                <label class="aig-toggle-wrap">
                    <div class="aig-toggle-left">
                        <span class="aig-toggle-dot"></span>
                        <span class="aig-toggle-name">Quiz Insights</span>
                    </div>
                    <div class="aig-switch">
                        <input type="hidden" name="feature[quiz_insights_enabled]" value="0">
                        <input type="checkbox" name="feature[quiz_insights_enabled]" value="1"
                               {{ old('feature.quiz_insights_enabled', ($settings['feature.quiz_insights_enabled'] ?? true) ? 1 : 0) ? 'checked' : '' }}>
                        <span class="aig-switch-track"></span>
                    </div>
                </label>

                <label class="aig-toggle-wrap">
                    <div class="aig-toggle-left">
                        <span class="aig-toggle-dot"></span>
                        <span class="aig-toggle-name">Class Summary</span>
                    </div>
                    <div class="aig-switch">
                        <input type="hidden" name="feature[class_summary_enabled]" value="0">
                        <input type="checkbox" name="feature[class_summary_enabled]" value="1"
                               {{ old('feature.class_summary_enabled', ($settings['feature.class_summary_enabled'] ?? true) ? 1 : 0) ? 'checked' : '' }}>
                        <span class="aig-switch-track"></span>
                    </div>
                </label>

                <label class="aig-toggle-wrap">
                    <div class="aig-toggle-left">
                        <span class="aig-toggle-dot"></span>
                        <span class="aig-toggle-name">Assessment analysis</span>
                    </div>
                    <div class="aig-switch">
                        <input type="hidden" name="feature[assessment_analysis_enabled]" value="0">
                        <input type="checkbox" name="feature[assessment_analysis_enabled]" value="1"
                               {{ old('feature.assessment_analysis_enabled', ($settings['feature.assessment_analysis_enabled'] ?? true) ? 1 : 0) ? 'checked' : '' }}>
                        <span class="aig-switch-track"></span>
                    </div>
                </label>

            </div>
        </div>
    </div>

    {{-- Quiz Generation Prompt --}}
    <div class="aig-card">
        <div class="aig-card-head">
            <div class="aig-card-icon" style="background:#faeeda;color:#854f0b;">
                <i class="fas fa-magic"></i>
            </div>
            <p class="aig-card-title">Quiz Generation Prompt</p>
        </div>
        <div class="aig-card-body">
            <div class="aig-prompt-grid">
                <div class="aig-field">
                    <label class="aig-label">
                        <span class="aig-prompt-tag system">System</span>
                        System Prompt
                    </label>
                    <textarea class="aig-textarea" name="prompt[quiz_generation][system]">{{ old('prompt.quiz_generation.system', $settings['prompt.quiz_generation.system'] ?? '') }}</textarea>
                    <p class="aig-hint">This tells the AI how to create quiz questions.</p>
                </div>
                <div class="aig-field">
                    <label class="aig-label">
                        <span class="aig-prompt-tag user">User</span>
                        User Template
                    </label>
                    <textarea class="aig-textarea" name="prompt[quiz_generation][user_template]">{{ old('prompt.quiz_generation.user_template', $settings['prompt.quiz_generation.user_template'] ?? '') }}</textarea>
                    <p class="aig-hint">This is the instruction format sent with your lesson/module content.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quiz Insights Prompt --}}
    <div class="aig-card">
        <div class="aig-card-head">
            <div class="aig-card-icon" style="background:#f3f0ff;color:#6d28d9;">
                <i class="fas fa-brain"></i>
            </div>
            <p class="aig-card-title">Quiz Insights Prompt</p>
        </div>
        <div class="aig-card-body">
            <div class="aig-prompt-grid">
                <div class="aig-field">
                    <label class="aig-label">
                        <span class="aig-prompt-tag system">System</span>
                        System Prompt
                    </label>
                    <textarea class="aig-textarea" name="prompt[quiz_insights][system]">{{ old('prompt.quiz_insights.system', $settings['prompt.quiz_insights.system'] ?? '') }}</textarea>
                    <p class="aig-hint">This controls how the AI explains one student's quiz result.</p>
                </div>
                <div class="aig-field">
                    <label class="aig-label">
                        <span class="aig-prompt-tag user">User</span>
                        User Template
                    </label>
                    <textarea class="aig-textarea" name="prompt[quiz_insights][user_template]">{{ old('prompt.quiz_insights.user_template', $settings['prompt.quiz_insights.user_template'] ?? '') }}</textarea>
                    <p class="aig-hint">This is the template used with score details and answer breakdown.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Class Summary Prompt --}}
    <div class="aig-card">
        <div class="aig-card-head">
            <div class="aig-card-icon" style="background:#e1f5ee;color:#0f6e56;">
                <i class="fas fa-chart-bar"></i>
            </div>
            <p class="aig-card-title">Class Summary Prompt</p>
        </div>
        <div class="aig-card-body">
            <div class="aig-prompt-grid">
                <div class="aig-field">
                    <label class="aig-label">
                        <span class="aig-prompt-tag system">System</span>
                        System Prompt
                    </label>
                    <textarea class="aig-textarea" name="prompt[class_summary][system]">{{ old('prompt.class_summary.system', $settings['prompt.class_summary.system'] ?? '') }}</textarea>
                    <p class="aig-hint">This controls how the AI writes a class-wide performance summary.</p>
                </div>
                <div class="aig-field">
                    <label class="aig-label">
                        <span class="aig-prompt-tag user">User</span>
                        User Template
                    </label>
                    <textarea class="aig-textarea" name="prompt[class_summary][user_template]">{{ old('prompt.class_summary.user_template', $settings['prompt.class_summary.user_template'] ?? '') }}</textarea>
                    <p class="aig-hint">This is the template used with class averages and weak-topic data.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Model & Limits --}}
    <div class="aig-card">
        <div class="aig-card-head">
            <div class="aig-card-icon" style="background:#f3f3f3;color:#555;">
                <i class="fas fa-microchip"></i>
            </div>
            <p class="aig-card-title">Model &amp; Limits</p>
        </div>
        <div class="aig-card-body">
            <div class="aig-model-grid">
                <div class="aig-field">
                    <label class="aig-label">AI Model</label>
                    <div class="aig-select-wrap">
                        <select class="aig-select" name="model[default]">
                            @foreach(\App\Services\AiSettingsResolver::AVAILABLE_MODELS as $modelId => $modelLabel)
                                <option value="{{ $modelId }}"
                                    {{ old('model.default', $settings['model.default'] ?? '@cf/meta/llama-3.2-3b-instruct') === $modelId ? 'selected' : '' }}>
                                    {{ $modelLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="aig-hint">Choose the AI brain for all features. Better models may be slower.</p>
                </div>
                <div class="aig-field">
                    <label class="aig-label">Max Tokens</label>
                    <input type="number" class="aig-input" name="model[max_tokens]"
                           min="50" max="8192"
                           value="{{ old('model.max_tokens', $settings['model.max_tokens'] ?? 400) }}">
                    <p class="aig-hint">Controls response length. Lower for shorter answers, higher for more detail.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Save bar --}}
    <div class="aig-card" style="padding:0;">
        <div class="aig-save-bar">
            <button type="submit" class="aig-save-btn">
                <i class="fas fa-save"></i> Save AI Settings
            </button>
        </div>
    </div>

</div>
</form>
@endsection