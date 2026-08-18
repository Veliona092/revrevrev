@extends('layouts.domain')

@section('content')
<style>
    /* Override content-wrapper to be a flex row */
    .content-wrapper {
        display: flex !important;
        flex-direction: row !important;
        height: calc(100vh - 58px) !important;
        overflow: hidden !important;
    }

    /* Sidebar */
    .mod-sidebar {
        width: 312px;
        min-width: 312px;
        background: #111827;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        border-right: 1px solid rgba(255,255,255,0.07);
        font-family: 'DM Sans', sans-serif;
    }

    .mod-sidebar-head {
        padding: 16px 18px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        flex-shrink: 0;
    }

    .mod-sidebar-label {
        font-size: 14px; font-weight: 500;
        letter-spacing: 0.1em; text-transform: uppercase;
        color: rgba(255,255,255,0.3); margin: 0 0 9px;
    }

    .mod-search {
        width: 100%; background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
        padding: 9px 12px; font-family: 'DM Sans', sans-serif;
        font-size: 15px; color: #fff; outline: none; transition: border-color 0.15s;
    }

    .mod-search::placeholder { color: rgba(255,255,255,0.25); }
    .mod-search:focus { border-color: #3b82f6; }

    .mod-list { flex: 1; overflow-y: auto; padding: 6px 0; }
    .mod-list::-webkit-scrollbar { width: 3px; }
    .mod-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 99px; }

    .mod-item {
        padding: 12px 18px; cursor: pointer;
        border-left: 3px solid transparent;
        transition: background 0.15s, border-color 0.15s;
        border-bottom: 1px solid rgba(255,255,255,0.03);
    }

    .mod-item:hover { background: rgba(255,255,255,0.04); }
    .mod-item.active { background: rgba(59,130,246,0.1); border-left-color: #3b82f6; }

    .mod-item.locked {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
    }

    .mod-badge.locked-badge { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.35); }

    .mod-item-row {
        display: flex; align-items: center;
        justify-content: space-between; gap: 8px; margin-bottom: 6px;
    }

    .mod-item-title {
        font-size: 15px; font-weight: 500;
        color: rgba(255,255,255,0.65); line-height: 1.4; flex: 1;
    }

    .mod-item.active .mod-item-title { color: #fff; }

    .mod-badge {
        font-size: 14px; font-weight: 500;
        padding: 2px 8px; border-radius: 99px; white-space: nowrap; flex-shrink: 0;
    }

    .mod-badge.quiz { background: rgba(59,130,246,0.2); color: #93c5fd; }
    .mod-badge.doc  { background: rgba(29,158,117,0.2); color: #6ee7b7; }

    .mod-bar-track { height: 4px; background: rgba(255,255,255,0.08); border-radius: 99px; overflow: hidden; }
    .mod-bar-fill  { height: 100%; border-radius: 99px; background: #1d9e75; transition: width 0.4s ease; }
    .mod-bar-fill.quiz { background: #3b82f6; }

    /* Main */
    .mod-main {
        flex: 1; display: flex; flex-direction: column;
        overflow: hidden; background: #f8f7f5; min-width: 0;
    }

    .mod-content {
        flex: 1; overflow-y: auto; padding: 40px 48px;
        font-family: 'DM Sans', sans-serif;
        font-size: 16px;
    }

    .mod-content::-webkit-scrollbar { width: 4px; }
    .mod-content::-webkit-scrollbar-thumb { background: #ddd; border-radius: 99px; }

    /* Footer */
    .mod-footer {
        background: #fff; border-top: 1px solid #ebebeb;
        padding: 12px 24px; display: flex; align-items: center;
        justify-content: space-between; flex-shrink: 0;
        font-family: 'DM Sans', sans-serif;
    }

    .mod-progress-label { font-size: 16px; color: #555; font-weight: 500; }
    .mod-progress-label strong { color: #111; }

    .mod-footer-track {
        flex: 1; max-width: 260px; height: 6px;
        background: #f3f3f3; border-radius: 99px; overflow: hidden; margin: 0 16px;
    }

    .mod-footer-fill { height: 100%; background: #1d9e75; border-radius: 99px; transition: width 0.4s ease; }

    .mod-nav-btns { display: flex; gap: 6px; }

    .mod-nav-btn {
        height: 36px; padding: 0 15px; border: 1px solid #e4e4e4;
        background: #fff; border-radius: 8px; font-family: 'DM Sans', sans-serif;
        font-size: 15px; font-weight: 500; color: #555; cursor: pointer;
        display: flex; align-items: center; gap: 5px; transition: border-color 0.15s, color 0.15s;
    }

    .mod-nav-btn:hover { border-color: #111; color: #111; }

    /* Placeholder */
    .mod-placeholder {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center; height: 100%; text-align: center; color: #ccc; gap: 10px;
    }

    .mod-placeholder i { font-size: 34px; opacity: 0.2; }
    .mod-placeholder p { font-size: 16px; }

    /* Doc view */
    .mod-doc-heading {
        font-family: 'DM Sans', sans-serif;
        font-size: 28px; font-weight: 500; color: #111; margin: 0 0 6px; letter-spacing: -0.02em;
    }

    .mod-doc-desc { font-size: 16px; color: #64748b; margin: 0 0 22px; }

    .mod-pdf-wrap {
        width: 100%; height: 640px; border: 1px solid #ebebeb;
        border-radius: 12px; overflow: hidden; background: #fff;
    }

    .mod-pdf-wrap iframe { border: none; width: 100%; height: 100%; }

    /* Quiz intro */
    .qi-wrap { max-width: 620px; margin: 0 auto; text-align: center; padding: 30px 0; }

    .qi-icon {
        width: 64px; height: 64px; border-radius: 15px;
        background: #eff6ff; color: #2563eb;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; margin: 0 auto 18px;
    }

    .qi-title { font-family: 'DM Sans', sans-serif; font-size: 28px; font-weight: 500; color: #111; margin: 0 0 6px; }
    .qi-sub   { font-size: 16px; color: #64748b; margin: 0 0 24px; }

    .qi-rules {
        background: #fff; border: 1px solid #ebebeb;
        border-radius: 10px; padding: 18px 22px; text-align: left; margin-bottom: 24px;
    }

    .qi-rules-title { font-size: 14px; font-weight: 500; color: #64748b; letter-spacing: 0.06em; text-transform: uppercase; margin: 0 0 10px; }

    .qi-rule {
        display: flex; align-items: flex-start; gap: 9px;
        font-size: 16px; color: #444; padding: 7px 0; border-bottom: 1px solid #f7f7f7;
    }

    .qi-rule:last-child { border-bottom: none; }
    .qi-rule-dot { width: 6px; height: 6px; border-radius: 50%; background: #3b82f6; flex-shrink: 0; margin-top: 5px; }

    .qi-start-btn {
        height: 44px; padding: 0 28px; background: #111827; color: #fff;
        border: none; border-radius: 10px; font-family: 'DM Sans', sans-serif;
        font-size: 15px; font-weight: 500; cursor: pointer;
        display: inline-flex; align-items: center; gap: 7px;
        transition: background 0.15s, transform 0.1s;
    }

    .qi-start-btn:hover  { background: #1f2937; }
    .qi-start-btn:active { transform: scale(0.98); }

    /* Quiz */
    .qz-wrap { max-width: 760px; margin: 0 auto; }

    .qz-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }

    .qz-title { font-family: 'DM Sans', sans-serif; font-size: 24px; font-weight: 500; color: #111; margin: 0; }

    .qz-timer {
        display: flex; align-items: center; gap: 5px;
        background: #111827; color: #fff; padding: 5px 12px;
        border-radius: 7px; font-size: 17px; font-weight: 500; font-family: monospace;
    }

    .qz-timer.warning { background: #e24b4a; }

    .qz-nav {
        display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 18px;
        padding: 12px 14px; background: #fff; border: 1px solid #ebebeb; border-radius: 9px;
    }

    .qz-pill {
        width: 36px; height: 36px; border-radius: 8px;
        border: 1px solid #e4e4e4; background: #fff;
        font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 500;
        color: #555; cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: all 0.15s;
    }

    .qz-pill:hover    { border-color: #3b82f6; color: #3b82f6; }
    .qz-pill.answered { background: #1d9e75; border-color: #1d9e75; color: #fff; }
    .qz-pill.current  { border-color: #3b82f6; color: #3b82f6; font-weight: 500; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
    .qz-pill.answered.current { background: #1d9e75; border-color: #1d9e75; color: #fff; box-shadow: 0 0 0 3px rgba(29,158,117,0.15); }

    .qz-q-card {
        background: #fff; border: 1px solid #ebebeb;
        border-radius: 11px; padding: 22px 26px; margin-bottom: 14px;
    }

    .qz-q-num  { font-size: 14px; font-weight: 500; color: #64748b; letter-spacing: 0.06em; text-transform: uppercase; margin: 0 0 8px; }
    .qz-q-text { font-size: 16px; color: #111; font-weight: 500; margin: 0 0 18px; line-height: 1.5; }

    .qz-option {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; border: 1px solid #e4e4e4; border-radius: 8px;
        margin-bottom: 8px; cursor: pointer; background: #fff;
        transition: border-color 0.15s, background 0.15s;
        font-size: 16px; color: #333; user-select: none;
    }

    .qz-option:last-child { margin-bottom: 0; }
    .qz-option:hover:not(.disabled) { border-color: #3b82f6; background: #eff6ff; }
    .qz-option.selected  { border-color: #3b82f6; background: #eff6ff; color: #1e40af; font-weight: 500; }
    .qz-option.disabled  { cursor: not-allowed; opacity: 0.6; }
    .qz-option input[type="radio"] { display: none; }

    .qz-option-key {
        width: 26px; height: 26px; border-radius: 6px;
        background: #f3f3f3; color: #555;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; font-weight: 500; flex-shrink: 0;
        transition: background 0.15s, color 0.15s;
    }

    .qz-option.selected .qz-option-key { background: #3b82f6; color: #fff; }

    .qz-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .qz-progress { font-size: 15px; color: #64748b; }

    .qz-btn {
        height: 38px; padding: 0 17px; border-radius: 8px;
        font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500;
        cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
        border: 1px solid transparent; transition: background 0.15s, transform 0.1s;
    }

    .qz-btn:active { transform: scale(0.98); }
    .qz-btn-dark    { background: #111827; color: #fff; }
    .qz-btn-dark:hover { background: #1f2937; }
    .qz-btn-outline { background: #fff; color: #555; border-color: #e4e4e4; }
    .qz-btn-outline:hover { border-color: #111; color: #111; }
    .qz-btn-success { background: #1d9e75; color: #fff; }
    .qz-btn-success:hover { background: #0f6e56; }

    /* Result */
    .qz-result { max-width: 560px; margin: 0 auto; text-align: center; padding: 30px 0; }

    .qz-result h2 { font-family: 'DM Sans', sans-serif; font-size: 26px; font-weight: 500; color: #111; margin: 0 0 24px; }

    .qz-gauge-wrap { position: relative; width: 240px; height: 138px; margin: 0 auto 18px; }

    .qz-gauge-score {
        position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%);
        font-family: 'DM Sans', sans-serif; font-size: 44px; font-weight: 500; color: #111; line-height: 1;
    }

    .qz-verdict {
        font-size: 16px; font-weight: 500; margin: 0 0 20px;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }

    .qz-verdict.pass { color: #1d9e75; }
    .qz-verdict.fail { color: #e24b4a; }

    .qz-ai-box {
        background: #fff; border: 1px solid #ebebeb; border-left: 3px solid #7f77dd;
        border-radius: 11px; padding: 16px 20px; text-align: left; margin-bottom: 20px;
    }

    .qz-ai-title {
        font-size: 14px; font-weight: 500; color: #7f77dd;
        letter-spacing: 0.06em; text-transform: uppercase;
        margin: 0 0 9px; display: flex; align-items: center; gap: 5px;
    }

    .qz-ai-sec { margin-bottom: 7px; }
    .qz-ai-sec:last-child { margin-bottom: 0; }
    .qz-ai-label { font-size: 14px; font-weight: 500; color: #111; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 2px; }
    .qz-ai-value { font-size: 15px; color: #555; margin: 0; line-height: 1.5; }

    .qz-result-btns { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }

    .qz-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 13000;
        min-width: 260px;
        max-width: min(92vw, 380px);
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #f2deaf;
        background: #fff9ea;
        color: #7b5a10;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.14);
        font-size: 14px;
        line-height: 1.45;
        opacity: 0;
        transform: translateY(-6px);
        transition: opacity 0.2s ease, transform 0.2s ease;
        pointer-events: none;
        white-space: pre-line;
    }

    .qz-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .qz-toast.fail {
        border-color: #f4b3b2;
        background: #fff4f3;
        color: #7b2220;
    }

    /* Reset Confirmation Modal */
    .reset-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .reset-modal-overlay.active {
        display: flex;
        opacity: 1;
    }
    .reset-modal {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        transform: scale(0.95);
        transition: transform 0.2s ease;
    }
    .reset-modal-overlay.active .reset-modal {
        transform: scale(1);
    }
    .reset-modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #111;
        margin: 0 0 12px 0;
    }
    .reset-modal-message {
        font-size: 14px;
        color: #6b7280;
        margin: 0 0 24px 0;
        line-height: 1.5;
    }
    .reset-modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    .reset-modal-btn {
        padding: 10px 20px;
        border-radius: 10px;
        border: none;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .reset-modal-btn:hover {
        transform: translateY(-1px);
    }
    .reset-modal-btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    .reset-modal-btn-secondary:hover {
        background: #e5e7eb;
    }
    .reset-modal-btn-primary {
        background: #111;
        color: #fff;
    }
    .reset-modal-btn-primary:hover {
        background: #374151;
    }
</style>

{{-- Sidebar --}}
<div class="mod-sidebar">
    <div class="mod-sidebar-head">
        <p class="mod-sidebar-label">Course Outline</p>
        <input type="text" class="mod-search" id="modSearch" placeholder="Search modules...">
    </div>
    <div class="mod-list" id="modList">
        @forelse($modules as $module)
            @php $isLocked = $locked[$module->id] ?? false; @endphp
              <div class="mod-item {{ $isLocked ? 'locked' : '' }}"
                 data-module-id="{{ $module->id }}"
                 data-is-quiz="{{ $module->is_quiz ? '1' : '0' }}"
                 data-locked="{{ $isLocked ? '1' : '0' }}">
                <div class="mod-item-row">
                    <span class="mod-item-title">{{ $loop->iteration }}. {{ $module->title }}</span>
                    @if($isLocked)
                        <span class="mod-badge locked-badge"><i class="fas fa-lock" style="font-size:9px;"></i></span>
                    @elseif($module->is_quiz)
                        <span class="mod-badge quiz">Quiz</span>
                    @else
                        <span class="mod-badge doc module-progress-badge" data-module-id="{{ $module->id }}">
                            {{ $progress[$module->id] ?? 0 }}%
                        </span>
                    @endif
                </div>
                <div class="mod-bar-track">
                    <div class="mod-bar-fill {{ $module->is_quiz ? 'quiz' : '' }} module-progress-bar"
                         data-module-id="{{ $module->id }}"
                         style="width:{{ $progress[$module->id] ?? 0 }}%">
                    </div>
                </div>
            </div>
        @empty
            <div style="padding:2rem;text-align:center;color:rgba(255,255,255,0.2);font-size: 15px;font-family:'DM Sans',sans-serif;">
                No modules yet.
            </div>
        @endforelse
    </div>
</div>

{{-- Main --}}
<div class="mod-main">
    <div class="mod-content" id="modContent">
        <div class="mod-placeholder">
            <i class="fas fa-book-open"></i>
            <p>Select a module from the outline to begin.</p>
        </div>
    </div>
    <div class="mod-footer">
        <span class="mod-progress-label">
            Progress: <strong id="overallProgress">{{ $overallCompletion }}%</strong>
        </span>
        <div class="mod-footer-track">
            <div class="mod-footer-fill" id="overallProgressBar" style="width:{{ $overallCompletion }}%"></div>
        </div>
        <div class="mod-nav-btns">
            <button class="mod-nav-btn" id="prevModule"><i class="fas fa-chevron-left"></i> Prev</button>
            <button class="mod-nav-btn" id="nextModule">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

@section('scripts')
<script>
    let modules              = @json($modules->toArray() ?? []);
    let lockedModules        = @json($locked ?? []);
    let quizAttempts         = @json($quizAttempts ?? []);
    let currentModuleId      = null;
    let quizTimerInterval    = null;
    let progressSaveTimer    = null;
    let timeLeft             = 0;
    let currentQuizQuestions = [];
    let currentQIndex        = 0;
    let answeredQuestions    = new Set();
    let selectedAnswers      = {};
    let isQuizActive         = false;
    let warningCount         = 0;
    let lastWarningTime      = 0; // Prevent duplicate warnings
    let isFormalAssessment   = false; // Track if current module is formal assessment
    let moduleProgressMap    = @json($progress ?? []);
    let quizToastTimer       = null;
    let attemptLimits        = @json($attemptLimits ?? []);

    function showQuizWarningToast(message, type) {
        type = type || 'warn';
        let toast = document.getElementById('quizWarningToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'quizWarningToast';
            toast.className = 'qz-toast';
            document.body.appendChild(toast);
        }

        toast.classList.remove('fail');
        if (type === 'fail') {
            toast.classList.add('fail');
        }

        toast.textContent = message;
        toast.classList.add('show');

        if (quizToastTimer) {
            clearTimeout(quizToastTimer);
        }
        quizToastTimer = setTimeout(function () {
            toast.classList.remove('show');
        }, type === 'fail' ? 4200 : 3200);
    }

    $(document).ready(function () {
        $(document).on('click', '.mod-item', function () {
            if ($(this).data('locked') == '1') {
                $('#modContent').html('<div class="mod-placeholder"><i class="fas fa-lock" style="font-size:2rem;color:#bbb;"></i><p style="margin-top:12px;color:#888;">Complete the previous module to unlock this one.</p></div>');
                return;
            }
            $('.mod-item').removeClass('active');
            $(this).addClass('active');
            loadModule($(this).data('module-id'), $(this).data('is-quiz') == '1');
        });

        $('#modSearch').on('input', function () {
            const q = $(this).val().toLowerCase();
            $('.mod-item').each(function () {
                $(this).toggle($(this).find('.mod-item-title').text().toLowerCase().includes(q));
            });
        });

        $('#prevModule').on('click', function () { navigateModule(-1); });
        $('#nextModule').on('click', function () { navigateModule(1); });

        selectInitialModule();
    });

    function selectInitialModule() {
        if (modules.length === 0) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const focus = params.get('focus');

        let initialModule = null;

        if (focus === 'lecture') {
            initialModule = modules.find(module => !module.is_quiz && !lockedModules[module.id]);
            if (!initialModule) {
                return;
            }
        }

        if (!initialModule) {
            initialModule = modules.find(module => !lockedModules[module.id]);
        }

        if (!initialModule) {
            return;
        }

        const initialItem = document.querySelector('.mod-item[data-module-id="' + initialModule.id + '"]');
        if (initialItem) {
            initialItem.click();
        }
    }

    function navigateModule(dir) {
        const items  = $('.mod-item').toArray();
        const active = items.findIndex(el => $(el).hasClass('active'));
        const next   = active + dir;
        if (next >= 0 && next < items.length) $(items[next]).trigger('click');
    }

    function loadModule(moduleId, isQuiz) {
        stopProgressTracking();

        currentModuleId = moduleId;
        const mod = modules.find(m => m.id == moduleId);
        if (!mod) return;

        if (isQuiz) {
            const existingAttempt = quizAttempts[moduleId];

            if (existingAttempt) {
                // Already completed - show locked result directly, no retake.
                showResult(
                    existingAttempt.percentage,
                    existingAttempt.score,
                    existingAttempt.total,
                    true,
                    existingAttempt.attempt_count,
                    null
                );
                // Always re-check AI availability per class to avoid stale cached insights when feature is disabled.
                getAI(moduleId);
            } else {
                $('#modContent').html(`
                    <div class="qi-wrap">
                        <div class="qi-icon"><i class="fas fa-clipboard-list"></i></div>
                        <h2 class="qi-title">${mod.title}</h2>
                        <p class="qi-sub">Complete this quiz to test your understanding.</p>
                        <div class="qi-rules">
                            <p class="qi-rules-title">Instructions</p>
                            <div class="qi-rule"><div class="qi-rule-dot"></div><span>A score of 70% or higher is required to pass.</span></div>
                            <div class="qi-rule"><div class="qi-rule-dot"></div><span>${getAttemptRuleText(moduleId)}</span></div>
                            <div class="qi-rule"><div class="qi-rule-dot"></div><span>Do not switch tabs - you will be warned twice before auto-fail.</span></div>
                            <div class="qi-rule"><div class="qi-rule-dot"></div><span>Answer all questions before submitting.</span></div>
                        </div>
                        <button class="qi-start-btn" onclick="startQuiz(${moduleId})">
                            <i class="fas fa-play"></i> Start Quiz
                        </button>
                            <button class="qz-btn qz-btn-outline" style="margin-left:8px;" onclick="backToModuleList()">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                    </div>
                `);
            }
        } else {
            // Document module (PDF or video)
            const isVideo = mod.file_type === 'mov';
            $('#modContent').html(`
                <h2 class="mod-doc-heading">${mod.title}</h2>
                <p class="mod-doc-desc">${mod.description || 'No description provided.'}</p>
                ${mod.file_path ? `
                    <div class="mod-pdf-wrap">
                        ${isVideo
                            ? `<video controls style="width:100%;height:100%;background:#000;">
                                 <source src="/modules/${mod.id}/view" type="video/quicktime">
                                 Your browser does not support the video tag.
                               </video>`
                            : `<iframe id="pdfViewerFrame" src="/modules/${mod.id}/pdfjs" width="100%" height="100%" allowfullscreen></iframe>`
                        }
                    </div>
                ` : '<p style="color:#64748b;font-size:16px;font-family:\'DM Sans\',sans-serif;">No file attached.</p>'}
            `);

            startProgressTracking(moduleId);
        }
    }

    function startProgressTracking(moduleId) {
        const $content = $('#modContent');
        if (!$content.length) {
            return;
        }

        const current = Number(moduleProgressMap[moduleId] || 0);
        if (current < 10) {
            persistProgress(moduleId, 10, false);
        }

        const videoElement = $content.find('video').get(0);
        if (videoElement) {
            const syncVideoProgress = function () {
                if (!videoElement.duration || !Number.isFinite(videoElement.duration)) {
                    return;
                }

                const computedProgress = Math.round((videoElement.currentTime / videoElement.duration) * 100);
                queueProgressSave(moduleId, computedProgress);
            };

            $(videoElement)
                .off('.videoProgress')
                .on('loadedmetadata.videoProgress timeupdate.videoProgress seeked.videoProgress ended.videoProgress', function (event) {
                    if (event.type === 'ended') {
                        queueProgressSave(moduleId, 100);
                        return;
                    }

                    syncVideoProgress();
                });

            syncVideoProgress();
            return;
        }

        // Outer container scroll (for non-iframe content)
        $content.off('scroll.moduleProgress').on('scroll.moduleProgress', function () {
            const el = this;
            const maxScrollable = Math.max(1, el.scrollHeight - el.clientHeight);
            const ratio = Math.max(0, Math.min(1, el.scrollTop / maxScrollable));
            const computedProgress = Math.round(ratio * 100);

            queueProgressSave(moduleId, computedProgress);
        });
    }

    // Receive scroll-progress messages from the pdfjs-viewer iframe
    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) return;
        if (!event.data || event.data.type !== 'pdf-scroll-progress') return;
        if (event.data.moduleId !== currentModuleId) return;

        queueProgressSave(event.data.moduleId, event.data.progress);
    });

    function stopProgressTracking() {
        $('#modContent').off('scroll.moduleProgress');
        $('#modContent').find('video').off('.videoProgress');

        if (progressSaveTimer) {
            clearTimeout(progressSaveTimer);
            progressSaveTimer = null;
        }
    }

    function queueProgressSave(moduleId, candidateProgress) {
        if (progressSaveTimer) {
            clearTimeout(progressSaveTimer);
        }

        progressSaveTimer = setTimeout(function () {
            const current = Number(moduleProgressMap[moduleId] || 0);
            const next = Math.max(current, Math.min(100, candidateProgress));

            if (next > current) {
                persistProgress(moduleId, next, next >= 100);
            }
        }, 350);
    }

    function persistProgress(moduleId, progressValue, completed) {
        const current = Number(moduleProgressMap[moduleId] || 0);
        const normalized = Math.max(current, Math.min(100, Math.round(progressValue)));

        if (normalized <= current) {
            return;
        }

        moduleProgressMap[moduleId] = normalized;
        updateProgressUI(moduleId, normalized);

        $.post(`/modules/${moduleId}/progress`, {
            _token: '{{ csrf_token() }}',
            progress: normalized,
            completed: completed ? 1 : 0
        }).fail(function () {
            // Revert local optimistic update when persistence fails.
            moduleProgressMap[moduleId] = current;
            updateProgressUI(moduleId, current);
        });
    }

    function updateProgressUI(moduleId, progressValue) {
        const pct = Math.max(0, Math.min(100, Math.round(progressValue)));
        const bar = document.querySelector(`.module-progress-bar[data-module-id="${moduleId}"]`);
        if (bar) {
            bar.style.width = `${pct}%`;
        }

        const badge = document.querySelector(`.module-progress-badge[data-module-id="${moduleId}"]`);
        if (badge) {
            badge.textContent = `${pct}%`;
        }

        const values = modules
            .filter(m => !m.is_quiz)
            .map(m => Number(moduleProgressMap[m.id] || 0));

        const overall = values.length
            ? Math.round(values.reduce((sum, val) => sum + val, 0) / values.length)
            : 0;

        $('#overallProgress').text(`${overall}%`);
        $('#overallProgressBar').css('width', `${overall}%`);
    }

    function startQuiz(moduleId) {
        const mod = modules.find(m => m.id == moduleId);
        isFormalAssessment = mod?.is_formal_assessment ?? false;

        // Kailangan munang i-check/i-record ang pagsisimula ng attempt bago
        // magpakita ng tanong — dito i-e-enforce ang max_attempts + grants
        // para sa formal assessments (Pre-Test, Post-Test, Mock Board).
        $.post(`/modules/${moduleId}/quiz/start`, { _token: '{{ csrf_token() }}' })
            .done(function (startRes) {
                renderQuizShell(moduleId, mod);

                if (isFormalAssessment) {
                    startAntiCheat();
                }

                $.get(`/modules/${moduleId}/quiz/questions`, function (res) {
                    if (res.success) {
                        currentQuizQuestions = res.questions;
                        currentQIndex        = 0;
                        answeredQuestions    = new Set();
                        selectedAnswers      = {};
                        renderNav();
                        renderQ();
                        startTimer(parseInt(res.time_limit) || 0);
                    }
                });
            })
            .fail(function (xhr) {
                const message = xhr?.responseJSON?.message || 'Hindi ka makapagsimula ng bagong attempt ngayon.';
                $('#modContent').html(`
                    <div class="mod-placeholder">
                        <i class="fas fa-lock" style="font-size:2rem;color:#e24b4a;"></i>
                        <p style="margin-top:12px;color:#444;max-width:420px;">${message}</p>
                        <button class="qz-btn qz-btn-outline" style="margin-top:12px;" onclick="backToModuleList()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                `);
            });
    }

    function renderQuizShell(moduleId, mod) {
        $('#modContent').html(`
            <div class="qz-wrap">
                <div class="qz-header">
                    <p class="qz-title">${mod?.title ?? 'Quiz'}</p>
                    <div class="qz-timer" id="quizTimer">
                        <i class="fas fa-clock" style="font-size: 14px;opacity:0.7;"></i> Loading...
                    </div>
                </div>
                <div class="qz-nav" id="quizNav"></div>
                <div id="quizArea"></div>
                <div class="qz-actions">
                    <span class="qz-progress">Question <span id="qNum">-</span> of <span id="qTotal">-</span></span>
                    <div style="display:flex;gap:6px;">
                        <button class="qz-btn qz-btn-outline" onclick="prevQ()"><i class="fas fa-chevron-left"></i> Prev</button>
                        <button class="qz-btn qz-btn-dark" id="nextBtn" onclick="nextQ()">Next <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        `);
    }

    function renderNav() {
        const total = currentQuizQuestions.length;
        let html = '';
        for (let i = 0; i < total; i++) {
            const a = answeredQuestions.has(i), c = i === currentQIndex;
            html += `<button class="qz-pill ${a ? 'answered' : ''} ${c ? 'current' : ''}" onclick="jumpTo(${i})">${i + 1}</button>`;
        }
        $('#quizNav').html(html);
        $('#qTotal').text(total);
    }

    function jumpTo(index) { currentQIndex = index; renderQ(); renderNav(); }

    function renderQ() {
        const q      = currentQuizQuestions[currentQIndex];
        const saved  = selectedAnswers[q.id];
        const isLast = currentQIndex === currentQuizQuestions.length - 1;

        $('#qNum').text(currentQIndex + 1);

        const opts = Object.keys(q.options).map(key => `
            <label class="qz-option ${saved === key ? 'selected' : ''}">
                <input type="radio" name="choice_${q.id}" value="${key}"
                       ${saved === key ? 'checked' : ''}
                       onchange="selectOpt('${q.id}', '${key}', this.closest('.qz-option'))">
                <span class="qz-option-key">${key}</span>
                <span>${q.options[key]}</span>
            </label>
        `).join('');

        $('#quizArea').html(`
            <div class="qz-q-card">
                <p class="qz-q-num">Question ${currentQIndex + 1}</p>
                <p class="qz-q-text">${q.question_text}</p>
                <div id="optionsList">${opts}</div>
            </div>
        `);

        if (isLast) {
            $('#nextBtn').html('Submit Quiz <i class="fas fa-check"></i>').removeClass('qz-btn-dark').addClass('qz-btn-success');
        } else {
            $('#nextBtn').html('Next <i class="fas fa-chevron-right"></i>').removeClass('qz-btn-success').addClass('qz-btn-dark');
        }
    }

    function selectOpt(qId, key, el) {
        $(el).closest('#optionsList').find('.qz-option').removeClass('selected');
        $(el).closest('#optionsList').find('.qz-option-key').css({ background: '#f3f3f3', color: '#555' });
        $(el).addClass('selected');
        $('.qz-option-key', el).css({ background: '#3b82f6', color: '#fff' });
        selectedAnswers[qId] = key;
    }

    function nextQ() {
        const q = currentQuizQuestions[currentQIndex];
        if (selectedAnswers[q.id]) answeredQuestions.add(currentQIndex);
        if (currentQIndex < currentQuizQuestions.length - 1) {
            currentQIndex++; renderQ(); renderNav();
        } else { submitQuiz(); }
    }

    function prevQ() {
        if (currentQIndex > 0) { currentQIndex--; renderQ(); renderNav(); }
    }

    function submitQuiz(forcedFail = false) {
        clearInterval(quizTimerInterval);
        stopAntiCheat();
        stopProgressTracking();

        const q = currentQuizQuestions[currentQIndex];
        if (selectedAnswers[q?.id]) answeredQuestions.add(currentQIndex);

        let score = 0;
        const total = currentQuizQuestions.length;
        if (!forcedFail) {
            currentQuizQuestions.forEach(q => {
                if (selectedAnswers[q.id]?.toUpperCase() === q.correct?.toUpperCase()) score++;
            });
        }

        const pct = forcedFail ? 0 : Math.round((score / total) * 100);

        let savedAttemptCount = 1;
        let savedAttemptId = null;

        const persistAnswers = saveAnswers(currentModuleId, forcedFail)
            .catch(() => null);

        persistAnswers
            .then(() => saveScore(currentModuleId, score, total, pct))
            .then((res) => {
                if (res && res.attempt_count) { savedAttemptCount = res.attempt_count; }
                if (res && res.attempt_id) { savedAttemptId = res.attempt_id; }
            })
            .then(() => new Promise(resolve => setTimeout(resolve, 300)))
            .then(() => {
                // Record the completed attempt client-side so re-opening the module shows locked result.
                quizAttempts[currentModuleId] = {
                    score: score,
                    total: total,
                    percentage: pct,
                    passed: pct >= 50,
                    attempt_count: savedAttemptCount,
                    attempt_id: savedAttemptId,
                };
                showResult(pct, score, total, false, savedAttemptCount, null);
                getAI(currentModuleId, savedAttemptId);
            })
            .catch(() => { showResult(pct, score, total, false, savedAttemptCount, null); getAI(currentModuleId, savedAttemptId); });
    }

    function showResult(pct, score, total, isLocked = false, attemptCount = 1, cachedInsights = null) {
        const passed  = pct >= 50;
        const color   = passed ? '#1d9e75' : '#e24b4a';
        const dashArr = 251;
        const dashOff = dashArr - (pct / 100 * dashArr);

        const aiHtml = cachedInsights && cachedInsights.strong
            ? `<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>
               <div class="qz-ai-sec"><p class="qz-ai-label">Strong Areas</p><p class="qz-ai-value">${cachedInsights.strong}</p></div>
               <div class="qz-ai-sec"><p class="qz-ai-label">Weak Areas</p><p class="qz-ai-value">${cachedInsights.weak || 'None detected'}</p></div>
               <div class="qz-ai-sec"><p class="qz-ai-label">Recommendation</p><p class="qz-ai-value">${cachedInsights.recommendation || 'Review the module again'}</p></div>`
            : `<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>
               <p style="font-size: 14px;color:#aaa;margin:0;">Analyzing your performance...</p>`;

        $('#modContent').html(`
            <div class="qz-result">
                <h2>Quiz Complete</h2>
                <p style="font-size: 14px;color:#999;margin:-8px 0 16px;">Attempt ${attemptCount}</p>
                <div class="qz-gauge-wrap">
                    <svg width="240" height="138" viewBox="0 0 240 138">
                        <path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="#f3f3f3" stroke-width="16" stroke-linecap="round"/>
                        <path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="${color}"
                              stroke-width="16" stroke-linecap="round"
                              stroke-dasharray="${dashArr}" stroke-dashoffset="${dashOff}"
                              style="transition:stroke-dashoffset 1s ease;"/>
                    </svg>
                    <div class="qz-gauge-score">${pct}%</div>
                </div>
                <p class="qz-verdict ${passed ? 'pass' : 'fail'}">
                    <i class="fas fa-${passed ? 'check-circle' : 'times-circle'}"></i>
                    ${passed ? 'You passed!' : 'You did not pass.'} &nbsp;${score} / ${total} correct.
                </p>
                <div class="qz-ai-box" id="aiBox">
                    ${aiHtml}
                </div>
                <div class="qz-result-btns">
                    <button class="qz-btn qz-btn-outline" onclick="backToModuleList()"><i class="fas fa-arrow-left"></i> Back</button>
                    ${!isFormalAssessment ? `
                    <button class="qz-btn qz-btn-dark" onclick="resetMyAttempt(${currentModuleId})"><i class="fas fa-undo"></i> Reset</button>
                    ` : ''}
                </div>
            </div>
        `);
    }

    function backToModuleList() {
        stopAntiCheat();
        stopProgressTracking();

        $('.mod-item').removeClass('active');
        $('#modContent').html(`
            <div class="mod-placeholder">
                <i class="fas fa-book-open"></i>
                <p>Select a module from the outline to begin.</p>
            </div>
        `);
    }

    let resetModuleId = null;

    function showResetModal(moduleId) {
        resetModuleId = moduleId;
        document.getElementById('resetModalOverlay').classList.add('active');
    }

    function hideResetModal() {
        document.getElementById('resetModalOverlay').classList.remove('active');
        resetModuleId = null;
    }

    function confirmReset() {
        if (!resetModuleId) return;
        performReset(resetModuleId);
        hideResetModal();
    }

    function resetMyAttempt(moduleId) {
        showResetModal(moduleId);
    }

    function getAI(moduleId, attemptId = null) {
        function renderAiMessage(message) {
            $('#aiBox').html(`<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p><p style="font-size: 14px;color:#aaa;margin:0;">${message}</p>`);
        }

        const payload = { _token: '{{ csrf_token() }}' };
        if (attemptId) {
            payload.attempt_id = attemptId;
        }

        $.post(`/modules/${moduleId}/quiz/insights`, payload)
            .done(function (res) {
                if (res.success) {
                    $('#aiBox').html(`
                        <p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>
                        <div class="qz-ai-sec"><p class="qz-ai-label">Strong Areas</p><p class="qz-ai-value">${res.strong || 'None detected'}</p></div>
                        <div class="qz-ai-sec"><p class="qz-ai-label">Weak Areas</p><p class="qz-ai-value">${res.weak || 'None detected'}</p></div>
                        <div class="qz-ai-sec"><p class="qz-ai-label">Recommendation</p><p class="qz-ai-value">${res.recommendation || 'Review the module again'}</p></div>
                    `);
                    // Cache insights client-side so re-navigating to the result doesn't refetch.
                    if (quizAttempts[moduleId]) {
                        quizAttempts[moduleId].ai_strong = res.strong;
                        quizAttempts[moduleId].ai_weak = res.weak;
                        quizAttempts[moduleId].ai_recommendation = res.recommendation;
                    }
                } else {
                    if (quizAttempts[moduleId]) {
                        quizAttempts[moduleId].ai_strong = null;
                        quizAttempts[moduleId].ai_weak = null;
                        quizAttempts[moduleId].ai_recommendation = null;
                    }
                    renderAiMessage(res.message || 'No insights available.');
                }
            })
            .fail(function (xhr) {
                const apiMessage = xhr?.responseJSON?.message;
                if (quizAttempts[moduleId] && (xhr?.status === 403 || apiMessage)) {
                    quizAttempts[moduleId].ai_strong = null;
                    quizAttempts[moduleId].ai_weak = null;
                    quizAttempts[moduleId].ai_recommendation = null;
                }
                renderAiMessage(apiMessage || 'Failed to load.');
            });
    }

    function saveScore(moduleId, score, total, pct) {
        return $.post(`/modules/${moduleId}/quiz/submit`, { _token: '{{ csrf_token() }}', score, total, percentage: pct });
    }

    function saveAnswers(moduleId, forcedFail) {
        if (forcedFail) return $.Deferred().resolve().promise();
        const requests = currentQuizQuestions
            .filter(q => selectedAnswers[q.id])
            .map(q => $.post(`/quiz/${moduleId}/answer`, {
                _token: '{{ csrf_token() }}', question_id: q.id, selected_option: selectedAnswers[q.id]
            }));
        return requests.length ? $.when.apply($, requests) : $.Deferred().resolve().promise();
    }

    function startTimer(minutes) {
        if (minutes <= 0) { $('#quizTimer').html('<i class="fas fa-infinity" style="font-size: 14px;opacity:0.7;"></i> No limit'); return; }
        timeLeft = minutes * 60;
        clearInterval(quizTimerInterval);
        quizTimerInterval = setInterval(() => {
            const min = Math.floor(timeLeft / 60), sec = timeLeft % 60;
            $('#quizTimer').html(`<i class="fas fa-clock" style="font-size: 14px;opacity:0.7;"></i> ${min}:${sec < 10 ? '0' : ''}${sec}`);
            if (timeLeft <= 300) $('#quizTimer').addClass('warning');
            if (timeLeft <= 0)  { clearInterval(quizTimerInterval); submitQuiz(); }
            timeLeft--;
        }, 1000);
    }

    function startAntiCheat() {
        isQuizActive = true; warningCount = 0;
        document.removeEventListener('visibilitychange', handleTab);
        window.removeEventListener('blur', handleTab);
        document.addEventListener('visibilitychange', handleTab);
        window.addEventListener('blur', handleTab);
    }

    function stopAntiCheat() {
        isQuizActive = false;
        document.removeEventListener('visibilitychange', handleTab);
        window.removeEventListener('blur', handleTab);
    }

    function handleTab() {
        if (!isQuizActive) return;
        if (!isFormalAssessment) return; // Skip anti-cheat for pre-assessments
        
        // Prevent duplicate warnings within 2 seconds
        let now = Date.now();
        if (now - lastWarningTime < 2000) return;
        lastWarningTime = now;
        
        warningCount++;
        if (warningCount >= 4) {
            showQuizWarningToast('You switched tabs too many times.\nThe quiz is now auto-submitted as FAILED.', 'fail');
            submitQuiz(true); stopAntiCheat(); return;
        }
        showQuizWarningToast(`Warning ${warningCount}/2: You switched tabs.\nDo this again and the quiz will fail automatically.`, 'warn');
    }

    function performReset(moduleId) {
        $.ajax({
            url: `/modules/${moduleId}/quiz/my-attempt`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function () {
                delete quizAttempts[moduleId];
                loadModule(moduleId, true);
            },
            error: function (xhr) {
                const message = xhr?.responseJSON?.message || 'Could not reset your attempt. Please try again.';
                alert(message);
            },
        });
    }
</script>

{{-- Reset Confirmation Modal --}}
<div id="resetModalOverlay" class="reset-modal-overlay" onclick="if(event.target === this) hideResetModal()">
    <div class="reset-modal">
        <h3 class="reset-modal-title">Reset your attempt?</h3>
        <p class="reset-modal-message">You will be able to retake this quiz. Your previous progress will be cleared.</p>
        <div class="reset-modal-actions">
            <button class="reset-modal-btn reset-modal-btn-secondary" onclick="hideResetModal()">Cancel</button>
            <button class="reset-modal-btn reset-modal-btn-primary" onclick="confirmReset()">Reset</button>
        </div>
    </div>
</div>

@endsection
@endsection
