@extends('layouts.domain')



@section('title', $module->title . ' - Assessment')

@section('page-title', $module->title)



@section('head')

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Instrument+Serif&display=swap" rel="stylesheet">

<style>

    /* Layout */

    .at-page {

        flex: 1; overflow-y: auto;

        background: #f8f7f5;

        display: flex; justify-content: center;

        padding: 36px 20px 88px;

    }



    .at-main {

        width: 100%; max-width: 780px;

        font-family: 'DM Sans', sans-serif;

    }



    /* Quiz shell */

    .qz-wrap { display: flex; flex-direction: column; gap: 12px; }



    .qz-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }



    .qz-title { font-family: 'DM Sans', sans-serif; font-size: 22px; color: #111; margin: 0; }



    .qz-timer {

        display: flex; align-items: center; gap: 5px;

        background: #111827; color: #fff; padding: 5px 12px;

        border-radius: 7px; font-size: 17px; font-weight: 500; font-family: monospace;

    }



    .qz-timer.warning { background: #e24b4a; }



    .qz-nav {

        display: flex; flex-wrap: wrap; gap: 6px;

        padding: 13px 15px; background: #fff; border: 1px solid #ebebeb; border-radius: 9px;

    }



    .qz-pill {

        width: 36px; height: 36px; border-radius: 8px;

        border: 1px solid #e4e4e4; background: #fff;

        font-family: 'DM Sans', sans-serif; font-size: 17px; font-weight: 500;

        color: #555; cursor: pointer;

        display: flex; align-items: center; justify-content: center; transition: all 0.15s;

    }



    .qz-pill:hover    { border-color: #3b82f6; color: #3b82f6; }

    .qz-pill.answered { background: #1d9e75; border-color: #1d9e75; color: #fff; }

    .qz-pill.current  { border-color: #3b82f6; color: #3b82f6; font-weight: 500; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }

    .qz-pill.answered.current { background: #1d9e75; border-color: #1d9e75; color: #fff; box-shadow: 0 0 0 3px rgba(29,158,117,0.15); }



    .qz-q-card {

        background: #fff; border: 1px solid #ebebeb;

        border-radius: 11px; padding: 24px 28px; margin-bottom: 14px;

    }



    .qz-q-num  { font-size: 17px; font-weight: 500; color: #aaa; letter-spacing: 0.06em; text-transform: uppercase; margin: 0 0 8px; }

    .qz-q-text { font-size: 17px; color: #111; font-weight: 500; margin: 0 0 18px; line-height: 1.5; }



    .qz-option {

        display: flex; align-items: center; gap: 10px;

        padding: 12px 14px; border: 1px solid #e4e4e4; border-radius: 8px;

        margin-bottom: 8px; cursor: pointer; background: #fff;

        transition: border-color 0.15s, background 0.15s;

        font-size: 17px; color: #333; user-select: none;

    }



    .qz-option:last-child { margin-bottom: 0; }

    .qz-option:hover { border-color: #3b82f6; background: #eff6ff; }

    .qz-option.selected { border-color: #3b82f6; background: #eff6ff; color: #1e40af; font-weight: 500; }

    .qz-option input[type="radio"] { display: none; }



    .qz-option-key {

        width: 26px; height: 26px; border-radius: 6px;

        background: #f3f3f3; color: #555;

        display: flex; align-items: center; justify-content: center;

        font-size: 16px; font-weight: 500; flex-shrink: 0;

        transition: background 0.15s, color 0.15s;

    }



    .qz-option.selected .qz-option-key { background: #3b82f6; color: #fff; }



    .qz-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; }

    .qz-progress { font-size: 16px; color: #aaa; }



    .qz-btn {

        height: 38px; padding: 0 16px; border-radius: 8px;

        font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 500;

        cursor: pointer; display: inline-flex; align-items: center; gap: 5px;

        border: 1px solid transparent; transition: background 0.15s, transform 0.1s;

        text-decoration: none;

    }



    .qz-btn:active { transform: scale(0.98); }

    .qz-btn-dark    { background: #111827; color: #fff; border-color: #111827; }

    .qz-btn-dark:hover { background: #1f2937; color: #fff; }

    .qz-btn-outline { background: #fff; color: #555; border-color: #e4e4e4; }

    .qz-btn-outline:hover { border-color: #111; color: #111; }

    .qz-btn-success { background: #1d9e75; color: #fff; border-color: #1d9e75; }

    .qz-btn-success:hover { background: #0f6e56; color: #fff; }



    /* Result */
    .qz-result { max-width: 680px; margin: 0 auto; text-align: center; padding: 28px 0; }
    .qz-result h2 { font-family: 'DM Sans', serif; font-size: 25px; color: #111; margin: 0 0 24px; }

    .qz-gauge-wrap { position: relative; width: 240px; height: 138px; margin: 0 auto 18px; }
    .qz-gauge-score {
        position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%);
        font-family: 'DM Sans', sans-serif; font-size: 44px; color: #111; line-height: 1;
    }

    .qz-verdict {
        font-size: 16px; font-weight: 500; margin: 0 0 20px;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .qz-verdict.pass { color: #1d9e75; }
    .qz-verdict.fail { color: #e24b4a; }

    /* Item Analysis & Question Review Styles */
    .qz-analysis-wrap {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0 24px;
        text-align: left;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .qz-analysis-tab {
        padding: 6px 14px;
        border-radius: 20px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #4b5563;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .qz-analysis-tab:hover { background: #f3f4f6; }
    .qz-analysis-tab.active { background: #111827; color: #fff; border-color: #111827; }
    .qz-analysis-tab.incorrect.active { background: #dc2626; color: #fff; border-color: #dc2626; }
    .qz-analysis-tab.correct.active { background: #16a34a; color: #fff; border-color: #16a34a; }

    .qz-analysis-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px 18px;
        background: #fff;
        transition: border-color 0.15s;
    }
    .qz-analysis-card.is-correct { border-left: 4px solid #16a34a; }
    .qz-analysis-card.is-incorrect { border-left: 4px solid #dc2626; }

    .qz-analysis-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .qz-analysis-num {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .qz-analysis-domain {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        background: #eff6ff;
        color: #1e40af;
        border-radius: 99px;
    }
    .qz-analysis-diff {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 99px;
        text-transform: capitalize;
    }
    .qz-analysis-diff.easy { background: #dcfce7; color: #166534; }
    .qz-analysis-diff.moderate, .qz-analysis-diff.medium { background: #fef3c7; color: #92400e; }
    .qz-analysis-diff.hard { background: #fee2e2; color: #991b1b; }

    .qz-status-badge {
        font-size: 12px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 99px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .qz-status-badge.correct { background: #dcfce7; color: #166534; }
    .qz-status-badge.incorrect { background: #fee2e2; color: #991b1b; }
    .qz-status-badge.unanswered { background: #f3f4f6; color: #6b7280; }

    .qz-analysis-q-text {
        font-size: 15px;
        font-weight: 500;
        color: #111827;
        margin: 0 0 14px;
        line-height: 1.5;
    }

    .qz-analysis-options {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }
    .qz-analysis-opt {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 13px;
        border-radius: 8px;
        font-size: 14px;
        border: 1px solid #e5e7eb;
        background: #fafafa;
    }
    .qz-analysis-opt.opt-correct-selected {
        background: #ecfdf5;
        border-color: #6ee7b7;
        color: #065f46;
        font-weight: 500;
    }
    .qz-analysis-opt.opt-incorrect-selected {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #991b1b;
        font-weight: 500;
    }
    .qz-analysis-opt.opt-correct-target {
        background: #f0fdf4;
        border-color: #86efac;
        color: #166534;
        font-weight: 500;
    }
    .qz-analysis-opt-label {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 99px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .qz-analysis-opt.opt-correct-selected .qz-analysis-opt-label {
        background: #d1fae5;
        color: #047857;
    }
    .qz-analysis-opt.opt-incorrect-selected .qz-analysis-opt-label {
        background: #fee2e2;
        color: #b91c1c;
    }
    .qz-analysis-opt.opt-correct-target .qz-analysis-opt-label {
        background: #dcfce7;
        color: #15803d;
    }

    .qz-analysis-expl {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 3px solid #3b82f6;
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 10px;
    }
    .qz-analysis-expl-title {
        font-size: 12px;
        font-weight: 700;
        color: #2563eb;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .qz-analysis-expl-text {
        font-size: 13px;
        color: #334155;
        margin: 0;
        line-height: 1.5;
    }



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

        font-size: 15px;

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



    .qz-ai-box {

        background: #fff; border: 1px solid #ebebeb; border-left: 3px solid #7f77dd;

        border-radius: 11px; padding: 16px 20px; text-align: left; margin-bottom: 20px;

    }



    .qz-ai-title {

        font-size: 17px; font-weight: 500; color: #7f77dd;

        letter-spacing: 0.06em; text-transform: uppercase;

        margin: 0 0 9px; display: flex; align-items: center; gap: 5px;

    }



    .qz-ai-sec { margin-bottom: 7px; }

    .qz-ai-sec:last-child { margin-bottom: 0; }

    .qz-ai-label { font-size: 16px; font-weight: 500; color: #111; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 2px; }

    .qz-ai-value { font-size: 15px; color: #555; margin: 0; line-height: 1.5; }



    .qz-result-btns { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }

    /* Attempt history */

    .qz-history-card {
        background: #fff; border: 1px solid #ebebeb; border-radius: 11px;
        padding: 16px 20px; text-align: left; margin-bottom: 20px;
    }

    .qz-history-title {
        font-size: 17px; font-weight: 500; color: #111; margin: 0 0 12px;
        display: flex; align-items: center; gap: 6px;
    }

    .qz-history-item {
        border: 1px solid #ebebeb; border-radius: 8px; margin-bottom: 8px; overflow: hidden;
    }

    .qz-history-item:last-child { margin-bottom: 0; }

    .qz-history-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px; cursor: pointer; background: #fafafa; transition: background 0.15s;
    }

    .qz-history-row:hover { background: #f3f3f3; }

    .qz-history-left { display: flex; align-items: center; gap: 10px; }

    .qz-history-num { font-size: 15px; font-weight: 500; color: #555; }

    .qz-history-score {
        font-size: 14px; font-weight: 500; padding: 2px 8px; border-radius: 99px;
        background: #f3f3f3; color: #555;
    }

    .qz-history-score.pass { background: #e1f5ee; color: #0f6e56; }
    .qz-history-score.fail { background: #fcebeb; color: #a32d2d; }

    .qz-history-date { font-size: 13px; color: #aaa; }

    .qz-history-chevron { transition: transform 0.15s; color: #aaa; }
    .qz-history-item.open .qz-history-chevron { transform: rotate(180deg); }

    .qz-history-detail { display: none; padding: 14px; border-top: 1px solid #ebebeb; }
    .qz-history-item.open .qz-history-detail { display: block; }

    .qz-history-q {
        padding: 10px 12px; border-radius: 8px; margin-bottom: 8px;
        font-size: 14px; border: 1px solid #e4e4e4;
    }

    .qz-history-q:last-child { margin-bottom: 0; }
    .qz-history-q.correct   { background: #f0fdf7; border-color: #bfe8d6; }
    .qz-history-q.incorrect { background: #fff8f8; border-color: #f4c9c8; }

    .qz-history-q-text { font-weight: 500; color: #111; margin: 0 0 6px; }
    .qz-history-q-ans  { font-size: 13px; color: #555; margin: 2px 0; }

    .qz-history-empty { font-size: 14px; color: #aaa; text-align: center; padding: 12px 0; }

    /* Start screen */

    .at-start {

        background: #fff; border: 1px solid #ebebeb; border-radius: 14px;

        padding: 44px 36px; text-align: center;

        display: flex; flex-direction: column; align-items: center; gap: 16px;

    }



    .at-start-icon {

        width: 60px; height: 60px; border-radius: 14px;

        background: #e8f0fe; color: #3c62d9;

        display: flex; align-items: center; justify-content: center; font-size: 24px;

    }



    .at-start-title { font-family: 'DM Sans', sans-serif; font-size: 24px; color: #111; margin: 0; }

    .at-start-sub   { font-size: 16px; color: #888; margin: 0; }



    .at-start-stats { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; }

    .at-start-stat  { font-size: 16px; color: #aaa; display: flex; align-items: center; gap: 6px; }



    .at-start-notice {

        font-size: 16px; color: #aaa; background: #fafafa; border: 1px solid #f0f0f0;

        border-radius: 8px; padding: 8px 14px;

    }

    /* Resume warning modal */

    .qz-resume-modal {
        position: fixed; inset: 0; background: rgba(17,24,39,0.55);
        display: flex; align-items: center; justify-content: center; z-index: 14000;
    }

    .qz-resume-modal-box {
        background: #fff; border-radius: 14px; padding: 32px 28px; max-width: 360px; width: 90%;
        text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    }

    .qz-resume-icon {
        width: 56px; height: 56px; border-radius: 50%;
        background: #fff4e5; color: #b45309;
        display: flex; align-items: center; justify-content: center; font-size: 22px;
        margin-bottom: 4px;
    }

    .qz-resume-title { font-family: 'DM Sans', sans-serif; font-size: 20px; color: #111; margin: 0; }
    .qz-resume-sub   { font-size: 15px; color: #888; margin: 0 0 6px; }

    .qz-resume-countdown {
        font-family: monospace; font-size: 32px; font-weight: 600; color: #e24b4a;
        margin-bottom: 8px;
    }

</style>

@endsection



@section('content')

<div class="at-page">

    <div class="at-main" id="atMain">

        {{-- Mock Board Header (if applicable) --}}
        @if($isMockBoard ?? false)
            <div class="mock-board-header" style="padding: 12px 16px; background: #eff6ff; border-left: 4px solid #3b82f6; margin-bottom: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 4px 0; font-size: 18px; color: #1e40af; font-weight: 600;">
                    {{ $mockBoard->title }} - {{ $mockBoardPhase->phase_label ?? ($phase === 'pre_test' ? 'Pre-Test' : 'Pre-Boards') }}
                </h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">
                    Passing: {{ $mockBoard->passing_percentage }}% | Review Period: {{ $mockBoard->review_period_start->format('M d') }} - {{ $mockBoard->review_period_end->format('M d, Y') }}
                </p>
            </div>
        @endif

        {{-- Resume warning modal — shown kapag bumalik ang estudyante habang may in_progress attempt --}}
        <div class="qz-resume-modal" id="resumeModal" style="display:none;">
            <div class="qz-resume-modal-box">
                <div class="qz-resume-icon"><i class="fas fa-hourglass-half"></i></div>
                <h3 class="qz-resume-title">You left this assessment</h3>
                <p class="qz-resume-sub">Resume now or it will be automatically marked as failed.</p>
                <div class="qz-resume-countdown" id="resumeCountdown">01:00</div>
                <button class="qz-btn qz-btn-dark" onclick="resumeFromModal()">
                    <i class="fas fa-play"></i> Resume Now
                </button>
            </div>
        </div>

        {{-- Start screen --}}

        <div class="at-start" id="startScreen">

            <div class="at-start-icon"><i class="fas fa-clipboard-check"></i></div>

            <h2 class="at-start-title">{{ $module->title }}</h2>

            <p class="at-start-sub">{{ $module->description ?? 'Formal assessment - answer all questions carefully.' }}</p>



            <div class="at-start-stats">

                <span class="at-start-stat">

                    <i class="fas fa-list-ol"></i>

                    {{ $questions->count() }} question{{ $questions->count() !== 1 ? 's' : '' }}

                </span>

                @if(($module->time_limit ?? 0) > 0)

                    <span class="at-start-stat">

                        <i class="fas fa-clock"></i>

                        {{ $module->time_limit }} minute time limit

                    </span>

                @else

                    <span class="at-start-stat"><i class="fas fa-infinity"></i> No time limit</span>

                @endif

                <span class="at-start-stat">
                    <i class="fas fa-repeat"></i>
                    @if(! $can_start_attempt)
                        No attempts remaining
                    @elseif($is_resuming)
                        Resume attempt {{ $attempts_used }} of {{ $attempts_allowed }}
                    @else
                        Attempt {{ $attempts_used + 1 }} of {{ $attempts_allowed }}
                    @endif
                </span>

            </div>



            <p class="at-start-notice">

                <i class="fas fa-exclamation-triangle"></i>

                Do not switch tabs or windows during the assessment.

            </p>



            @if($can_start_attempt)
                <button class="qz-btn qz-btn-dark" onclick="launchAssessment()">
                    <i class="fas fa-play"></i> {{ $is_resuming ? 'Resume Assessment' : 'Begin Assessment' }}
                </button>
            @else
                <button class="qz-btn qz-btn-dark" disabled>
                    <i class="fas fa-ban"></i> Attempts Used Up
                </button>
            @endif

        </div>



        {{-- Quiz area (hidden until launched) --}}

        <div id="quizScreen" style="display:none;"></div>



    </div>

</div>

@endsection



@section('scripts')

<script>

    var csrfToken            = '{{ csrf_token() }}';

    var moduleId             = {{ $module->id }};

    var timeLimit            = {{ $module->time_limit ?? 0 }};

    var isFormalAssessment   = {{ $module->is_formal_assessment ? 'true' : 'false' }};

    var assessmentReturnUrl  = '{{ ($isMockBoard ?? false) && isset($mockBoard) ? route("student.mock-boards.results", $mockBoard->id) : route("assessment") }}';

    var isResuming              = @json($is_resuming ?? false);

    var resumeDeadlineMs        = @json($resume_deadline_ms ?? null);

    var currentAttemptId        = null;

    var resumeCountdownInterval = null;



    var currentQuizQuestions = @json($questions->toArray());

    var currentQIndex        = 0;

    var answeredQuestions    = new Set();

    var selectedAnswers      = {};

    var quizTimerInterval    = null;

    var timeLeft             = 0;

    var isQuizActive         = false;

    var isQuizInProgress     = false;

    var warningCount         = 0;

    var lastWarningTime      = 0; // Prevent duplicate warnings

    var quizToastTimer       = null;



    function showAssessmentWarningToast(message, type) {

        type = type || 'warn';

        var toast = document.getElementById('assessmentWarningToast');

        if (!toast) {

            toast = document.createElement('div');

            toast.id = 'assessmentWarningToast';

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

    function showResumeModal() {
        var modal = document.getElementById('resumeModal');
        if (!modal) { return; }
        modal.style.display = 'flex';
        tickResumeCountdown();
        clearInterval(resumeCountdownInterval);
        resumeCountdownInterval = setInterval(tickResumeCountdown, 1000);
    }

    function hideResumeModal() {
        var modal = document.getElementById('resumeModal');
        if (modal) { modal.style.display = 'none'; }
        clearInterval(resumeCountdownInterval);
    }

    function tickResumeCountdown() {
        var el = document.getElementById('resumeCountdown');
        if (!el || !resumeDeadlineMs) { return; }

        var msLeft = resumeDeadlineMs - Date.now();

        if (msLeft <= 0) {
            el.textContent = '00:00';
            clearInterval(resumeCountdownInterval);
            hideResumeModal();
            // Panahon na — tatawagin ang /quiz/start; ang backend na ang
            // magmamarka ng dating attempt bilang 0/failed dahil sa timeout.
            launchAssessment();
            return;
        }

        var totalSec = Math.ceil(msLeft / 1000);
        var min = Math.floor(totalSec / 60);
        var sec = totalSec % 60;
        el.textContent = (min < 10 ? '0' : '') + min + ':' + (sec < 10 ? '0' : '') + sec;
    }

    function resumeFromModal() {
        hideResumeModal();
        launchAssessment();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (isResuming && resumeDeadlineMs) {
            showResumeModal();
        }
    });



function launchAssessment() {

    var startBtn = document.querySelector('#startScreen .qz-btn-dark');
    if (startBtn) { startBtn.disabled = true; }

    fetch('/modules/' + moduleId + '/quiz/start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({}),
    })
    .then(function (r) {
        return r.json().then(function (data) {
            return { ok: r.ok, status: r.status, data: data };
        });
    })
    .then(function (result) {
        if (!result.ok) {
            showBlockedState(result.data);
            return;
        }
        currentAttemptId = result.data.attempt_id || null;
        beginQuizUi();
    })
    .catch(function () {
        if (startBtn) { startBtn.disabled = false; }
        showAssessmentWarningToast('Could not start the assessment. Please check your connection and try again.', 'fail');
    });

}

function showBlockedState(data) {
    var msg = (data && data.message) || 'You cannot start this assessment right now.';
    var used = data && data.attempts_used;
    var allowed = data && data.attempts_allowed;
    var timedOut = !!(data && data.timed_out);

    var iconBg = timedOut ? '#fff4e5' : '#fde8e8';
    var iconColor = timedOut ? '#b45309' : '#e24b4a';
    var iconClass = timedOut ? 'fa-clock' : 'fa-lock';
    var title = timedOut ? 'Assessment Timed Out' : 'Assessment Locked';

    document.getElementById('startScreen').innerHTML =
        '<div class="at-start-icon" style="background:' + iconBg + ';color:' + iconColor + ';"><i class="fas ' + iconClass + '"></i></div>' +
        '<h2 class="at-start-title">' + title + '</h2>' +
        '<p class="at-start-sub">' + escHtml(msg) + '</p>' +
        (used != null && allowed != null
            ? '<p class="at-start-notice">Attempts used: ' + used + ' / ' + allowed + '</p>'
            : '') +
        '<a href="' + assessmentReturnUrl + '" class="qz-btn qz-btn-outline">' +
        '<i class="fas fa-arrow-left"></i> Back to Assessments</a>';
}

function beginQuizUi() {
    document.getElementById('startScreen').style.display = 'none';

    document.getElementById('quizScreen').style.display  = '';

    renderQuizShell();

    currentQIndex     = 0;

    answeredQuestions = new Set();

    selectedAnswers   = {};

    renderNav();

    renderQ();

    startTimer(timeLimit);

    isQuizInProgress = true;
    window.addEventListener('beforeunload', handleBeforeUnload);

    if (isFormalAssessment) {
        startAntiCheat();
    }
}
    function handleBeforeUnload(e) {
        if (!isQuizInProgress) { return; }
        e.preventDefault();
        e.returnValue = '';
        return '';
    }


    function renderQuizShell() {

        document.getElementById('quizScreen').innerHTML = `

            <div class="qz-wrap">

                <div class="qz-header">

                    <p class="qz-title">${escHtml('{{ $module->title }}')}</p>

                    <div class="qz-timer" id="quizTimer">

                        <i class="fas fa-clock" style="font-size: 14px;opacity:0.7;"></i> Loading...

                    </div>

                </div>

                <div class="qz-nav" id="quizNav"></div>

                <div id="quizArea"></div>

                <div class="qz-actions">

                    <span class="qz-progress">Question <span id="qNum">-</span> of <span id="qTotal">-</span></span>

                    <div style="display:flex;gap:6px;">

                        <button class="qz-btn qz-btn-outline" onclick="prevQ()">

                            <i class="fas fa-chevron-left"></i> Prev

                        </button>

                        <button class="qz-btn qz-btn-dark" id="nextBtn" onclick="nextQ()">

                            Next <i class="fas fa-chevron-right"></i>

                        </button>

                    </div>

                </div>

            </div>

        `;

    }



    function renderNav() {

        var total = currentQuizQuestions.length;

        var html = '';

        for (var i = 0; i < total; i++) {

            var answered = answeredQuestions.has(i);

            var current  = i === currentQIndex;

            html += '<button class="qz-pill' +

                (answered ? ' answered' : '') +

                (current  ? ' current'  : '') +

                '" onclick="jumpTo(' + i + ')">' + (i + 1) + '</button>';

        }

        document.getElementById('quizNav').innerHTML = html;

        document.getElementById('qTotal').textContent = total;

    }



    function jumpTo(index) { currentQIndex = index; renderQ(); renderNav(); }



    function renderQ() {

        var q      = currentQuizQuestions[currentQIndex];

        var saved  = selectedAnswers[q.id];

        var isLast = currentQIndex === currentQuizQuestions.length - 1;



        document.getElementById('qNum').textContent = currentQIndex + 1;



        var opts = Object.keys(q.options).map(function (key) {

            return '<label class="qz-option' + (saved === key ? ' selected' : '') + '">' +

                '<input type="radio" name="choice_' + q.id + '" value="' + key + '"' +

                (saved === key ? ' checked' : '') +

                ' onchange="selectOpt(\'' + q.id + '\', \'' + key + '\', this.closest(\'.qz-option\'))">' +

                '<span class="qz-option-key">' + key + '</span>' +

                '<span>' + escHtml(q.options[key]) + '</span></label>';

        }).join('');



        document.getElementById('quizArea').innerHTML =

            '<div class="qz-q-card">' +

            '<p class="qz-q-num">Question ' + (currentQIndex + 1) + '</p>' +

            '<p class="qz-q-text">' + escHtml(q.question_text) + '</p>' +

            '<div id="optionsList">' + opts + '</div>' +

            '</div>';



        var nextBtn = document.getElementById('nextBtn');

        if (isLast) {

            nextBtn.innerHTML = 'Submit <i class="fas fa-check"></i>';

            nextBtn.className = 'qz-btn qz-btn-success';

        } else {

            nextBtn.innerHTML = 'Next <i class="fas fa-chevron-right"></i>';

            nextBtn.className = 'qz-btn qz-btn-dark';

        }

    }



    function selectOpt(qId, key, el) {

        el.closest('#optionsList').querySelectorAll('.qz-option').forEach(function (o) {

            o.classList.remove('selected');

            o.querySelector('.qz-option-key').style.background = '#f3f3f3';

            o.querySelector('.qz-option-key').style.color = '#555';

        });

        el.classList.add('selected');

        el.querySelector('.qz-option-key').style.background = '#3b82f6';

        el.querySelector('.qz-option-key').style.color = '#fff';

        selectedAnswers[qId] = key;

    }



    function nextQ() {

        var q = currentQuizQuestions[currentQIndex];

        if (!selectedAnswers[q.id]) {
            showAssessmentWarningToast('Please select an answer before continuing.', 'warn');
            return;
        }

        answeredQuestions.add(currentQIndex);

        if (currentQIndex < currentQuizQuestions.length - 1) {

            currentQIndex++;

            renderQ();

            renderNav();

        } else {

            submitQuiz();

        }

    }



    function prevQ() {

        if (currentQIndex > 0) { currentQIndex--; renderQ(); renderNav(); }

    }



    function submitQuiz(forcedFail) {

        forcedFail = forcedFail || false;

        clearInterval(quizTimerInterval);

        stopAntiCheat();



        var q = currentQuizQuestions[currentQIndex];

        if (q && selectedAnswers[q.id]) { answeredQuestions.add(currentQIndex); }



        var score = 0;

        var total = currentQuizQuestions.length;



        if (!forcedFail) {

            currentQuizQuestions.forEach(function (q) {

                if ((selectedAnswers[q.id] || '').toUpperCase() === (q.correct || '').toUpperCase()) {

                    score++;

                }

            });

        }



        var frontendScore = score;

        var frontendTotal = total;

        var frontendPct = forcedFail ? 0 : Math.round((score / total) * 100);



        // First save all answers, then submit score (so server can calculate from database)

        saveAnswers(forcedFail)
            .then(function () {
                return saveScore(frontendTotal);
            })
            .then(function (res) {
                // Use server-calculated values

                var finalScore = res && res.success ? (res.score ?? frontendScore) : frontendScore;

                var finalTotal = res && res.success ? (res.total ?? frontendTotal) : frontendTotal;

                var finalPct = res && res.success ? (res.percentage ?? frontendPct) : frontendPct;

                isQuizInProgress = false;
                window.removeEventListener('beforeunload', handleBeforeUnload);

                showResult(finalPct, finalScore, finalTotal);

                getAI();
            })
            .catch(function (err) {
                isQuizInProgress = false;
                window.removeEventListener('beforeunload', handleBeforeUnload);

                showAssessmentWarningToast(
                    'Some of your answers may not have saved correctly. Please screenshot this and contact your teacher before leaving this page.',
                    'fail'
                );
                console.error(err);

                showResult(frontendPct, frontendScore, frontendTotal);

                getAI();
            });

    }



    function showResult(pct, score, total) {

        var passed   = pct >= 50;

        var color    = passed ? '#1d9e75' : '#e24b4a';

        var dashArr  = 251;

        var dashOff  = dashArr - (pct / 100 * dashArr);



        document.getElementById('quizScreen').innerHTML =

            '<div class="qz-result">' +

            '<h2>Assessment Complete</h2>' +

            '<div class="qz-gauge-wrap">' +

            '<svg width="240" height="138" viewBox="0 0 240 138">' +

            '<path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="#f3f3f3" stroke-width="16" stroke-linecap="round"/>' +

            '<path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="' + color + '"' +

            ' stroke-width="16" stroke-linecap="round"' +

            ' stroke-dasharray="' + dashArr + '" stroke-dashoffset="' + dashOff + '"' +

            ' style="transition:stroke-dashoffset 1s ease;"/>' +

            '</svg><div class="qz-gauge-score">' + pct + '%</div></div>' +

            '<p class="qz-verdict ' + (passed ? 'pass' : 'fail') + '">' +

            '<i class="fas fa-' + (passed ? 'check-circle' : 'times-circle') + '"></i>' +

            (passed ? ' You passed!' : ' You did not pass.') + ' &nbsp;' + score + ' / ' + total + ' correct.' +

            '</p>' +

            '<div class="qz-ai-box" id="aiBox">' +

            '<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>' +

            '<p style="font-size: 14px;color:#aaa;margin:0;">Analyzing your performance...</p>' +

            '</div>' +

            '<div class="qz-analysis-wrap" id="itemAnalysisContainer">' +
            '<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px; border-bottom:1px solid #ebebeb; padding-bottom:14px;">' +
            '<div>' +
            '<h3 style="font-size:17px; font-weight:700; color:#111; margin:0 0 4px; display:flex; align-items:center; gap:8px;">' +
            '<i class="fas fa-chart-pie" style="color:#3b82f6;"></i> Item Analysis & Question Review' +
            '</h3>' +
            '<p style="font-size:13px; color:#666; margin:0;">Detailed breakdown of your answers and correct rationales.</p>' +
            '</div>' +
            '<div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;" id="analysisFilterBar">' +
            '<button type="button" class="qz-analysis-tab active" onclick="filterItemAnalysis(\'all\', this)">All (<span id="countAll">0</span>)</button>' +
            '<button type="button" class="qz-analysis-tab incorrect" onclick="filterItemAnalysis(\'incorrect\', this)"><i class="fas fa-times-circle"></i> Incorrect (<span id="countIncorrect">0</span>)</button>' +
            '<button type="button" class="qz-analysis-tab correct" onclick="filterItemAnalysis(\'correct\', this)"><i class="fas fa-check-circle"></i> Correct (<span id="countCorrect">0</span>)</button>' +
            '</div>' +
            '</div>' +
            '<div id="itemAnalysisList" style="display:flex; flex-direction:column; gap:14px;">' +
            '<div style="text-align:center; padding:24px; color:#888;">' +
            '<i class="fas fa-spinner fa-spin"></i> Loading item analysis...' +
            '</div>' +
            '</div>' +
            '</div>' +

            '<div class="qz-history-card" id="historyBox">' +

            '<p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p>' +

            '<p class="qz-history-empty">Loading history...</p>' +

            '</div>' +

            '<div class="qz-result-btns">' +

            '<a href="' + assessmentReturnUrl + '" class="qz-btn qz-btn-outline">' +

            '<i class="fas fa-arrow-left"></i> Back to Assessments</a>' +

            '</div></div>';

        loadAttemptHistory();
        loadItemAnalysis(moduleId);

    }



    function getAI() {

        fetch('/modules/' + moduleId + '/quiz/insights', {

            method: 'POST',

            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },

            body: JSON.stringify({ attempt_id: currentAttemptId }),

        })

        .then(function (r) { return r.json(); })

        .then(function (res) {

            var box = document.getElementById('aiBox');

            if (!box) { return; }

            if (res.success) {

                box.innerHTML =

                    '<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>' +

                    '<div class="qz-ai-sec"><p class="qz-ai-label">Strong Areas</p>' +

                    '<p class="qz-ai-value">' + escHtml(res.strong || 'None detected') + '</p></div>' +

                    '<div class="qz-ai-sec"><p class="qz-ai-label">Weak Areas</p>' +

                    '<p class="qz-ai-value">' + escHtml(res.weak || 'None detected') + '</p></div>' +

                    '<div class="qz-ai-sec"><p class="qz-ai-label">Recommendation</p>' +

                    '<p class="qz-ai-value">' + escHtml(res.recommendation || 'Review the material again') + '</p></div>';

            } else {

                box.innerHTML = '<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>' +

                    '<p style="font-size: 14px;color:#aaa;margin:0;">' + escHtml(res.message || 'No insights available.') + '</p>';

            }

        })

        .catch(function () {

            var box = document.getElementById('aiBox');

            if (box) {

                box.innerHTML = '<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>' +

                    '<p style="font-size: 14px;color:#aaa;margin:0;">Failed to load.</p>';

            }

        });

    }



    function loadAttemptHistory() {

        fetch('/modules/' + moduleId + '/quiz/history', {

            headers: { 'X-CSRF-TOKEN': csrfToken },

        })

        .then(function (r) { return r.json(); })

        .then(function (res) {

            var box = document.getElementById('historyBox');

            if (!box) { return; }

            if (res.success && res.attempts && res.attempts.length) {

                renderAttemptHistory(res.attempts);

            } else {

                box.innerHTML = '<p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p>' +

                    '<p class="qz-history-empty">No previous attempts yet.</p>';

            }

        })

        .catch(function () {

            var box = document.getElementById('historyBox');

            if (box) {

                box.innerHTML = '<p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p>' +

                    '<p class="qz-history-empty">Failed to load history.</p>';

            }

        });

    }



    function renderAttemptHistory(attempts) {

        var box = document.getElementById('historyBox');

        if (!box) { return; }

        var rows = attempts.map(function (a) {

            var pct = Math.round(a.percentage);

            var scoreClass = a.passed ? 'pass' : 'fail';

            var dateStr = a.completed_at

                ? new Date(a.completed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

                : '';

            return '<div class="qz-history-item" id="historyItem_' + a.id + '">' +

                '<div class="qz-history-row" onclick="toggleAttemptHistory(' + a.id + ')">' +

                '<div class="qz-history-left">' +

                '<span class="qz-history-num">Attempt ' + a.attempt_number + '</span>' +

                '<span class="qz-history-score ' + scoreClass + '">' + pct + '% &bull; ' + a.score + '/' + a.total + '</span>' +

                '</div>' +

                '<div style="display:flex;align-items:center;gap:8px;">' +

                '<span class="qz-history-date">' + dateStr + '</span>' +

                '<i class="fas fa-chevron-down qz-history-chevron"></i>' +

                '</div>' +

                '</div>' +

                '<div class="qz-history-detail" id="historyDetail_' + a.id + '">' +

                '<p class="qz-history-empty">Loading...</p>' +

                '</div>' +

                '</div>';

        }).join('');

        box.innerHTML = '<p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p>' + rows;

    }



    var loadedHistoryDetails = {};



    function toggleAttemptHistory(snapshotId) {

        var item = document.getElementById('historyItem_' + snapshotId);

        if (!item) { return; }

        var wasOpen = item.classList.contains('open');

        item.classList.toggle('open');

        if (wasOpen || loadedHistoryDetails[snapshotId]) { return; }

        loadedHistoryDetails[snapshotId] = true;

        fetch('/quiz/attempts/' + snapshotId + '/detail', {

            headers: { 'X-CSRF-TOKEN': csrfToken },

        })

        .then(function (r) { return r.json(); })

        .then(function (res) {

            var detailEl = document.getElementById('historyDetail_' + snapshotId);

            if (!detailEl) { return; }

            if (res.success && res.questions && res.questions.length) {

                detailEl.innerHTML = res.questions.map(function (q, i) {

                    var cls = q.is_correct ? 'correct' : 'incorrect';

                    var yourAns = q.selected_option

                        ? q.selected_option + (q.options && q.options[q.selected_option] ? ' - ' + q.options[q.selected_option] : '')

                        : 'No answer';

                    var correctAns = q.correct_option

                        ? q.correct_option + (q.options && q.options[q.correct_option] ? ' - ' + q.options[q.correct_option] : '')

                        : '-';

                    return '<div class="qz-history-q ' + cls + '">' +

                        '<p class="qz-history-q-text">' + (i + 1) + '. ' + escHtml(q.question_text || '') + '</p>' +

                        '<p class="qz-history-q-ans"><i class="fas fa-' + (q.is_correct ? 'check' : 'times') + '"></i> Your answer: ' + escHtml(yourAns) + '</p>' +

                        (!q.is_correct

                            ? '<p class="qz-history-q-ans"><i class="fas fa-check"></i> Correct answer: ' + escHtml(correctAns) + '</p>'

                            : '') +

                        '</div>';

                }).join('');

            } else {
                detailEl.innerHTML = '<p class="qz-history-empty">No details available for this attempt.</p>';
            }
        })
        .catch(function () {
            var detailEl = document.getElementById('historyDetail_' + snapshotId);
            if (detailEl) {
                detailEl.innerHTML = '<p class="qz-history-empty">Failed to load details.</p>';
            }
        });
    }

    var _loadedAnalysisQuestions = [];

    function loadItemAnalysis(targetModuleId) {
        var container = document.getElementById('itemAnalysisList');
        if (!container) return;

        fetch('/modules/' + targetModuleId + '/quiz/analysis', {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success || !res.questions || !res.questions.length) {
                container.innerHTML = '<p style="text-align:center;color:#888;padding:16px;">No item analysis available yet.</p>';
                return;
            }

            var summary = res.summary || {};
            var countAll = document.getElementById('countAll');
            var countIncorrect = document.getElementById('countIncorrect');
            var countCorrect = document.getElementById('countCorrect');

            if (countAll) countAll.textContent = summary.total || res.questions.length;
            if (countIncorrect) countIncorrect.textContent = summary.incorrect || 0;
            if (countCorrect) countCorrect.textContent = summary.correct || 0;

            _loadedAnalysisQuestions = res.questions;
            renderAnalysisCards(res.questions, 'all');
        })
        .catch(function () {
            container.innerHTML = '<p style="text-align:center;color:#e24b4a;padding:16px;">Failed to load item analysis.</p>';
        });
    }

    function renderAnalysisCards(questions, filter) {
        var container = document.getElementById('itemAnalysisList');
        if (!container) return;

        filter = filter || 'all';
        var filtered = questions.filter(function (q) {
            if (filter === 'correct') return q.is_correct;
            if (filter === 'incorrect') return !q.is_correct;
            return true;
        });

        if (!filtered.length) {
            container.innerHTML = '<p style="text-align:center;color:#888;padding:24px;">No ' + filter + ' items to display.</p>';
            return;
        }

        container.innerHTML = filtered.map(function (q) {
            var isCorrect = q.is_correct;
            var isUnanswered = q.is_unanswered;
            var statusClass = isCorrect ? 'is-correct' : 'is-incorrect';
            var cardStatus = isCorrect ? 'correct' : 'incorrect';

            var statusBadge = isCorrect
                ? '<span class="qz-status-badge correct"><i class="fas fa-check"></i> Correct (+' + q.points + ' pt)</span>'
                : (isUnanswered
                    ? '<span class="qz-status-badge unanswered"><i class="fas fa-minus-circle"></i> Unanswered (0 pt)</span>'
                    : '<span class="qz-status-badge incorrect"><i class="fas fa-times"></i> Incorrect (0 pt)</span>');

            var domainBadge = q.domain ? '<span class="qz-analysis-domain">' + escHtml(q.domain) + '</span>' : '';
            var diffBadge = q.difficulty ? '<span class="qz-analysis-diff ' + escHtml(q.difficulty.toLowerCase()) + '">' + escHtml(q.difficulty) + '</span>' : '';

            var optionsHtml = '';
            if (q.options && typeof q.options === 'object') {
                var keys = Object.keys(q.options);
                optionsHtml = keys.map(function (k) {
                    var optText = q.options[k];
                    var isSelected = q.selected_option && q.selected_option.toUpperCase() === k.toUpperCase();
                    var isTargetCorrect = q.correct_option && q.correct_option.toUpperCase() === k.toUpperCase();

                    var optClass = 'qz-analysis-opt';
                    var tagLabel = '';

                    if (isSelected && isTargetCorrect) {
                        optClass += ' opt-correct-selected';
                        tagLabel = '<span class="qz-analysis-opt-label"><i class="fas fa-check"></i> Your Answer (Correct)</span>';
                    } else if (isSelected && !isTargetCorrect) {
                        optClass += ' opt-incorrect-selected';
                        tagLabel = '<span class="qz-analysis-opt-label"><i class="fas fa-times"></i> Your Answer</span>';
                    } else if (isTargetCorrect) {
                        optClass += ' opt-correct-target';
                        tagLabel = '<span class="qz-analysis-opt-label"><i class="fas fa-check"></i> Correct Answer</span>';
                    }

                    return '<div class="' + optClass + '">' +
                        '<div><strong>' + k + '.</strong> ' + escHtml(optText) + '</div>' +
                        tagLabel +
                    '</div>';
                }).join('');
            }

            var explHtml = q.explanation ? (
                '<div class="qz-analysis-expl">' +
                    '<div class="qz-analysis-expl-title"><i class="fas fa-lightbulb"></i> Explanation / Rationale</div>' +
                    '<p class="qz-analysis-expl-text">' + escHtml(q.explanation) + '</p>' +
                '</div>'
            ) : '';

            return '<div class="qz-analysis-card ' + statusClass + '" data-status="' + cardStatus + '">' +
                '<div class="qz-analysis-card-head">' +
                    '<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">' +
                        '<span class="qz-analysis-num">Question ' + q.index + '</span>' +
                        domainBadge +
                        diffBadge +
                    '</div>' +
                    '<div>' + statusBadge + '</div>' +
                '</div>' +
                '<p class="qz-analysis-q-text">' + escHtml(q.question_text) + '</p>' +
                '<div class="qz-analysis-options">' + optionsHtml + '</div>' +
                explHtml +
            '</div>';
        }).join('');
    }

    function filterItemAnalysis(type, btn) {
        document.querySelectorAll('.qz-analysis-tab').forEach(function (b) { b.classList.remove('active'); });
        if (btn) btn.classList.add('active');
        if (_loadedAnalysisQuestions && _loadedAnalysisQuestions.length) {
            renderAnalysisCards(_loadedAnalysisQuestions, type);
        }
    }



    function saveScore(total) {

        return fetch('/modules/' + moduleId + '/quiz/submit', {

            method: 'POST',

            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },

            body: JSON.stringify({ total: total, attempt_id: currentAttemptId }),

        }).then(function (r) {

            if (!r.ok) {

                throw new Error('HTTP error ' + r.status);

            }

            return r.json();

        });

    }


function saveAnswers(forcedFail) {
    if (forcedFail) { return Promise.resolve(); }
    var requests = currentQuizQuestions
        .filter(function (q) { return selectedAnswers[q.id]; })
        .map(function (q) {
            return fetch('/quiz/' + moduleId + '/answer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ question_id: q.id, selected_option: selectedAnswers[q.id], attempt_id: currentAttemptId }),
            }).then(function (r) {
                if (!r.ok) {
                    return r.json().catch(function () { return {}; }).then(function (body) {
                        throw new Error('Failed to save answer for question ' + q.id + ': ' + (body.message || r.status));
                    });
                }
                return r.json();
            });
        });
    return requests.length ? Promise.all(requests) : Promise.resolve();
}



    function startTimer(minutes) {

        var timerEl = document.getElementById('quizTimer');

        if (!timerEl) { return; }

        if (minutes <= 0) {

            timerEl.innerHTML = '<i class="fas fa-infinity" style="font-size: 14px;opacity:0.7;"></i> No limit';

            return;

        }

        timeLeft = minutes * 60;

        clearInterval(quizTimerInterval);

        quizTimerInterval = setInterval(function () {

            var min = Math.floor(timeLeft / 60);

            var sec = timeLeft % 60;

            var timerEl = document.getElementById('quizTimer');

            if (timerEl) {

                timerEl.innerHTML = '<i class="fas fa-clock" style="font-size: 14px;opacity:0.7;"></i> ' +

                    min + ':' + (sec < 10 ? '0' : '') + sec;

                if (timeLeft <= 300) { timerEl.classList.add('warning'); }

            }

            if (timeLeft <= 0) { clearInterval(quizTimerInterval); submitQuiz(); }

            timeLeft--;

        }, 1000);

    }



    function startAntiCheat() {

        isQuizActive = true;

        warningCount = 0;

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

        if (!isQuizActive) { return; }
        if (!isFormalAssessment) { return; } // Skip anti-cheat for pre-assessments

        // Prevent duplicate warnings within 2 seconds
        var now = Date.now();
        if (now - lastWarningTime < 2000) { return; }
        lastWarningTime = now;

        warningCount++;

        if (warningCount >= 4) {

            showAssessmentWarningToast('You switched tabs too many times.\nThis assessment has been auto-submitted as FAILED.', 'fail');

            submitQuiz(true);

            stopAntiCheat();

            return;

        }

        showAssessmentWarningToast('Tab switching detected.\nContinued tab switching may cause this assessment to be auto-submitted as failed.', 'warn');

    }



    function escHtml(str) {

        var d = document.createElement('div');

        d.textContent = String(str);

        return d.innerHTML;

    }

    @if($viewResultsOnly ?? false)
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('startScreen').style.display = 'none';
        document.getElementById('quizScreen').style.display = '';
        showResult({{ (float) $resultPercentage }}, {{ (int) $resultScore }}, {{ (int) $resultTotal }});
        getAI();
    });
    @endif

</script>

@endsection



