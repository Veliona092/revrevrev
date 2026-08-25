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

    .qz-result { max-width: 520px; margin: 0 auto; text-align: center; padding: 28px 0; }

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

</style>

@endsection



@section('content')

<div class="at-page">

    <div class="at-main" id="atMain">

        {{-- Mock Board Header (if applicable) --}}
        @if($isMockBoard ?? false)
            <div class="mock-board-header" style="padding: 12px 16px; background: #eff6ff; border-left: 4px solid #3b82f6; margin-bottom: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 4px 0; font-size: 18px; color: #1e40af; font-weight: 600;">
                    {{ $mockBoard->title }} - {{ $phase === 'pre_test' ? 'Pre-Test' : 'Pre-Boards' }}
                </h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">
                    Passing: {{ $mockBoard->passing_percentage }}% | Review Period: {{ $mockBoard->review_period_start->format('M d') }} - {{ $mockBoard->review_period_end->format('M d, Y') }}
                </p>
            </div>
        @endif

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

    var assessmentReturnUrl  = '{{ route("assessment") }}';



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

    document.getElementById('startScreen').innerHTML =
        '<div class="at-start-icon" style="background:#fde8e8;color:#e24b4a;"><i class="fas fa-lock"></i></div>' +
        '<h2 class="at-start-title">Assessment Locked</h2>' +
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

        if (selectedAnswers[q.id]) { answeredQuestions.add(currentQIndex); }

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

.catch(function () {

                isQuizInProgress = false;
                window.removeEventListener('beforeunload', handleBeforeUnload);

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

            '<div class="qz-result-btns">' +

            '<a href="' + assessmentReturnUrl + '" class="qz-btn qz-btn-outline">' +

            '<i class="fas fa-arrow-left"></i> Back to Assessments</a>' +

            '</div></div>';

    }



    function getAI() {

        fetch('/modules/' + moduleId + '/quiz/insights', {

            method: 'POST',

            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },

            body: JSON.stringify({}),

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



    function saveScore(total) {

        return fetch('/modules/' + moduleId + '/quiz/submit', {

            method: 'POST',

            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },

            body: JSON.stringify({ total: total }),

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

                    body: JSON.stringify({ question_id: q.id, selected_option: selectedAnswers[q.id] }),

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

</script>

@endsection



