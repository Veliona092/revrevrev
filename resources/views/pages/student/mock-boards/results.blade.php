@php
    $programLayoutMap = [
        'psych' => 'layouts.appPsych',
        'accountancy' => 'layouts.appAcc',
        'educ' => 'layouts.appEduc',
    ];
    $layout = $programLayoutMap[auth()->user()->program] ?? 'layouts.appAcc';
@endphp
@extends($layout)

@section('title', 'Performance Report')
@section('page-heading', 'Performance Report')

@section('header-actions')
    <a href="{{ route('student.mock-boards.index') }}" class="rv-btn rv-btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Mock Boards
    </a>
@endsection

@section('content')
<div class="results-container">

    <div class="results-intro">
        <div class="results-icon"><i class="fas fa-chart-line"></i></div>
        <div>
            <h2 class="results-title">{{ $mockBoard->title }}</h2>
            <p class="results-subtitle">{{ ucfirst($mockBoard->program) }} &bull; Passing: {{ $mockBoard->passing_percentage }}%</p>
        </div>
    </div>

    <div class="score-cards">
        {{-- Pre-Test Card --}}
        <div class="score-card {{ isset($attempts['pre_test']) ? 'done' : 'pending' }}" id="scoreCard_pre_test">
            <div class="score-card-head" @if(isset($attempts['pre_test'])) onclick="toggleScoreCard('pre_test')" style="cursor:pointer;" @endif>
                <span class="score-card-label"><i class="fas fa-pencil-alt"></i> Pre-Test</span>
                <div style="display:flex;align-items:center;gap:10px;">
                    @if(isset($attempts['pre_test']))
                        <span class="score-status-badge {{ $attempts['pre_test']->passed ? 'pass' : 'fail' }}">
                            {{ $attempts['pre_test']->passed ? 'Passed' : 'Failed' }}
                        </span>
                        <span class="score-card-pct">{{ (int) round($attempts['pre_test']->percentage) }}%</span>
                        <i class="fas fa-chevron-down score-card-chevron"></i>
                    @else
                        <span class="score-status-badge pending">Not Taken</span>
                    @endif
                </div>
            </div>

            @if(isset($attempts['pre_test']))
                @php
                    $ptPct = (int) round($attempts['pre_test']->percentage);
                    $ptPassed = $attempts['pre_test']->passed;
                    $ptColor = $ptPassed ? '#1d9e75' : '#e24b4a';
                    $ptDashArr = 251;
                    $ptDashOff = $ptDashArr - ($ptPct / 100 * $ptDashArr);
                @endphp

                <div class="score-card-body" id="cardBody_pre_test">
                    <div class="score-card-body-inner">
                        <div class="qz-gauge-wrap">
                            <svg width="240" height="138" viewBox="0 0 240 138">
                                <path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="#f3f3f3" stroke-width="16" stroke-linecap="round"/>
                                <path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="{{ $ptColor }}"
                                    stroke-width="16" stroke-linecap="round"
                                    stroke-dasharray="{{ $ptDashArr }}" stroke-dashoffset="{{ $ptDashOff }}"
                                    style="transition:stroke-dashoffset 1s ease;"/>
                            </svg>
                            <div class="qz-gauge-score">{{ $ptPct }}%</div>
                        </div>

                        <p class="qz-verdict {{ $ptPassed ? 'pass' : 'fail' }}">
                            <i class="fas fa-{{ $ptPassed ? 'check-circle' : 'times-circle' }}"></i>
                            {{ $ptPassed ? ' You passed!' : ' You did not pass.' }}
                            &nbsp;{{ $attempts['pre_test']->score }} / {{ $attempts['pre_test']->total_questions }} correct.
                        </p>

                        <div class="qz-ai-box" id="aiBox_pre_test">
                            <p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>
                            <p style="font-size:13px;color:#aaa;margin:0;">Analyzing your performance...</p>
                        </div>

                        <div class="qz-history-card" id="historyBox_pre_test">
                            <p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p>
                            <p class="qz-history-empty">Loading history...</p>
                        </div>
                    </div>
                </div>
            @else
                <p class="score-detail muted" style="margin-top:16px;">You haven't taken this phase yet.</p>
            @endif
        </div>

        {{-- Pre-Board Card --}}
        <div class="score-card {{ isset($attempts['pre_boards']) ? 'done' : 'pending' }}" id="scoreCard_pre_boards">
            <div class="score-card-head" @if(isset($attempts['pre_boards'])) onclick="toggleScoreCard('pre_boards')" style="cursor:pointer;" @endif>
                <span class="score-card-label"><i class="fas fa-clipboard-check"></i> Pre-Board</span>
                <div style="display:flex;align-items:center;gap:10px;">
                    @if(isset($attempts['pre_boards']))
                        <span class="score-status-badge {{ $attempts['pre_boards']->passed ? 'pass' : 'fail' }}">
                            {{ $attempts['pre_boards']->passed ? 'Passed' : 'Failed' }}
                        </span>
                        <span class="score-card-pct">{{ (int) round($attempts['pre_boards']->percentage) }}%</span>
                        <i class="fas fa-chevron-down score-card-chevron"></i>
                    @else
                        <span class="score-status-badge pending">Not Taken</span>
                    @endif
                </div>
            </div>

            @if(isset($attempts['pre_boards']))
                @php
                    $pbPct = (int) round($attempts['pre_boards']->percentage);
                    $pbPassed = $attempts['pre_boards']->passed;
                    $pbColor = $pbPassed ? '#1d9e75' : '#e24b4a';
                    $pbDashArr = 251;
                    $pbDashOff = $pbDashArr - ($pbPct / 100 * $pbDashArr);
                @endphp

                <div class="score-card-body" id="cardBody_pre_boards">
                    <div class="score-card-body-inner">
                        <div class="qz-gauge-wrap">
                            <svg width="240" height="138" viewBox="0 0 240 138">
                                <path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="#f3f3f3" stroke-width="16" stroke-linecap="round"/>
                                <path d="M 35 118 A 85 85 0 0 1 205 118" fill="none" stroke="{{ $pbColor }}"
                                    stroke-width="16" stroke-linecap="round"
                                    stroke-dasharray="{{ $pbDashArr }}" stroke-dashoffset="{{ $pbDashOff }}"
                                    style="transition:stroke-dashoffset 1s ease;"/>
                            </svg>
                            <div class="qz-gauge-score">{{ $pbPct }}%</div>
                        </div>

                        <p class="qz-verdict {{ $pbPassed ? 'pass' : 'fail' }}">
                            <i class="fas fa-{{ $pbPassed ? 'check-circle' : 'times-circle' }}"></i>
                            {{ $pbPassed ? ' You passed!' : ' You did not pass.' }}
                            &nbsp;{{ $attempts['pre_boards']->score }} / {{ $attempts['pre_boards']->total_questions }} correct.
                        </p>

                        <div class="qz-ai-box" id="aiBox_pre_boards">
                            <p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>
                            <p style="font-size:13px;color:#aaa;margin:0;">Analyzing your performance...</p>
                        </div>

                        <div class="qz-history-card" id="historyBox_pre_boards">
                            <p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p>
                            <p class="qz-history-empty">Loading history...</p>
                        </div>
                    </div>
                </div>
            @else
                <p class="score-detail muted" style="margin-top:16px;">Locked or not taken yet.</p>
            @endif
        </div>
    </div>

    {{-- Growth Insight --}}
    @if(isset($attempts['pre_test']) && isset($attempts['pre_boards']))
        @php $diff = $attempts['pre_boards']->percentage - $attempts['pre_test']->percentage; @endphp
        <div class="growth-box {{ $diff >= 0 ? 'positive' : 'negative' }}">
            <div class="growth-icon">
                <i class="fas fa-{{ $diff >= 0 ? 'arrow-trend-up' : 'arrow-trend-down' }}"></i>
            </div>
            <div>
                <p class="growth-label">Your Growth</p>
                <p class="growth-value">
                    {{ $diff >= 0 ? '+' : '-' }}{{ abs($diff) }}%
                    <span class="growth-note">{{ $diff >= 0 ? 'improvement from Pre-Test to Pre-Board' : 'decline from Pre-Test to Pre-Board' }}</span>
                </p>
            </div>
        </div>
    @elseif(isset($attempts['pre_test']) && !isset($attempts['pre_boards']))
        <div class="growth-box neutral">
            <div class="growth-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <p class="growth-label">Keep Going</p>
                <p class="growth-value" style="font-size:15px;">Complete the Pre-Board phase to see your growth.</p>
            </div>
        </div>
    @endif

</div>

<script>
    var csrfToken = '{{ csrf_token() }}';
    var historyData = @json($history);
    var insightsRoutes = {
        pre_test: '{{ route("student.mock-boards.insights", [$mockBoard, "pre_test"]) }}',
        pre_boards: '{{ route("student.mock-boards.insights", [$mockBoard, "pre_boards"]) }}'
    };
    var cachedInsights = {
        pre_test: @json(isset($attempts['pre_test']) ? ['strong' => $attempts['pre_test']->ai_strong, 'weak' => $attempts['pre_test']->ai_weak, 'recommendation' => $attempts['pre_test']->ai_recommendation] : null),
        pre_boards: @json(isset($attempts['pre_boards']) ? ['strong' => $attempts['pre_boards']->ai_strong, 'weak' => $attempts['pre_boards']->ai_weak, 'recommendation' => $attempts['pre_boards']->ai_recommendation] : null)
    };

    var loadedPhases = {}; // tracks which phases already had AI/history fetched (lazy load)

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = String(str);
        return d.innerHTML;
    }

    function renderAiBox(phase, data) {
        var box = document.getElementById('aiBox_' + phase);
        if (!box) return;
        box.innerHTML =
            '<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p>' +
            '<div class="qz-ai-sec"><p class="qz-ai-label">Strong Areas</p><p class="qz-ai-value">' + escHtml(data.strong || 'None detected') + '</p></div>' +
            '<div class="qz-ai-sec"><p class="qz-ai-label">Weak Areas</p><p class="qz-ai-value">' + escHtml(data.weak || 'None detected') + '</p></div>' +
            '<div class="qz-ai-sec"><p class="qz-ai-label">Recommendation</p><p class="qz-ai-value">' + escHtml(data.recommendation || 'Review the material again') + '</p></div>';
    }

    function loadAiInsights(phase) {
        var box = document.getElementById('aiBox_' + phase);
        if (!box) return;
        var cached = cachedInsights[phase];
        if (cached && (cached.strong || cached.weak || cached.recommendation)) {
            renderAiBox(phase, cached);
            return;
        }
        fetch(insightsRoutes[phase], {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({}),
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            renderAiBox(phase, {
                strong: (res.strong_areas || []).join(', '),
                weak: (res.weak_areas || []).join(', '),
                recommendation: res.recommendation
            });
        })
        .catch(function () {
            box.innerHTML = '<p class="qz-ai-title"><i class="fas fa-brain"></i> AI Insights</p><p style="font-size:13px;color:#aaa;margin:0;">Failed to load insights.</p>';
        });
    }

    function renderHistory(phase) {
        var box = document.getElementById('historyBox_' + phase);
        if (!box) return;
        var attempts = historyData[phase] || [];
        if (!attempts.length) {
            box.innerHTML = '<p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p><p class="qz-history-empty">No previous attempts yet.</p>';
            return;
        }
        var rows = attempts.map(function (a) {
            var pct = Math.round(a.percentage);
            var scoreClass = a.passed ? 'pass' : 'fail';
            var dateStr = a.completed_at ? new Date(a.completed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
            var itemId = phase + '_' + a.attempt_number;
            var qHtml = (a.questions || []).map(function (q, i) {
                var cls = q.is_correct ? 'correct' : 'incorrect';
                var yourAns = q.selected_option ? q.selected_option + (q.options && q.options[q.selected_option] ? ' - ' + q.options[q.selected_option] : '') : 'No answer';
                var correctAns = q.correct_option ? q.correct_option + (q.options && q.options[q.correct_option] ? ' - ' + q.options[q.correct_option] : '') : '-';
                return '<div class="qz-history-q ' + cls + '">' +
                    '<p class="qz-history-q-text">' + (i + 1) + '. ' + escHtml(q.question_text || '') + '</p>' +
                    '<p class="qz-history-q-ans"><i class="fas fa-' + (q.is_correct ? 'check' : 'times') + '"></i> Your answer: ' + escHtml(yourAns) + '</p>' +
                    (!q.is_correct ? '<p class="qz-history-q-ans"><i class="fas fa-check"></i> Correct answer: ' + escHtml(correctAns) + '</p>' : '') +
                    '</div>';
            }).join('');
            return '<div class="qz-history-item" id="historyItem_' + itemId + '">' +
                '<div class="qz-history-row" onclick="toggleHistoryItem(\'' + itemId + '\')">' +
                '<div class="qz-history-left"><span class="qz-history-num">Attempt ' + a.attempt_number + '</span>' +
                '<span class="qz-history-score ' + scoreClass + '">' + pct + '% &bull; ' + a.score + '/' + a.total + '</span></div>' +
                '<div style="display:flex;align-items:center;gap:8px;"><span class="qz-history-date">' + dateStr + '</span>' +
                '<i class="fas fa-chevron-down qz-history-chevron"></i></div></div>' +
                '<div class="qz-history-detail" id="historyDetail_' + itemId + '">' + qHtml + '</div></div>';
        }).join('');
        box.innerHTML = '<p class="qz-history-title"><i class="fas fa-history"></i> Attempt History</p>' + rows;
    }

    function toggleHistoryItem(itemId) {
        var item = document.getElementById('historyItem_' + itemId);
        if (item) item.classList.toggle('open');
    }

    // ---- Accordion behavior for Pre-Test / Pre-Board cards ----
    var scorePhases = ['pre_test', 'pre_boards'];

    function toggleScoreCard(phase) {
        var card = document.getElementById('scoreCard_' + phase);
        if (!card) return;
        var isOpen = card.classList.contains('expanded');

        // close every card first (accordion: only one open at a time)
        scorePhases.forEach(function (p) {
            var c = document.getElementById('scoreCard_' + p);
            if (c) c.classList.remove('expanded');
        });

        // if it wasn't open before, open it now (clicking an open card just closes it)
        if (!isOpen) {
            card.classList.add('expanded');
            if (!loadedPhases[phase]) {
                loadAiInsights(phase);
                renderHistory(phase);
                loadedPhases[phase] = true;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // auto-open the first available phase by default: pre_boards if taken, else pre_test
        var defaultPhase = @json(isset($attempts['pre_boards']) ? 'pre_boards' : (isset($attempts['pre_test']) ? 'pre_test' : null));
        if (defaultPhase) {
            toggleScoreCard(defaultPhase);
        }
    });
</script>
@endsection

@section('head')
<style>
    html {
        overflow-y: scroll;
    }

    .results-container {
        max-width: 640px; margin: 0 auto; padding: 4px 0 20px;
        font-family: 'DM Sans', sans-serif;
    }

    .results-intro { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; }
    .results-icon { width: 52px; height: 52px; border-radius: 12px; background: #e6f4ea; color: #245E55; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .results-title { margin: 0; font-family: 'DM Sans', sans-serif; font-size: 24px; font-weight: 500; color: #111; }
    .results-subtitle { margin: 4px 0 0; font-size: 14px; color: #aaa; font-weight: 500; }

    .score-cards { display: flex; flex-direction: column; gap: 24px; margin-bottom: 24px; }
    .score-card { background: #fff; padding: 30px 28px; border-radius: 14px; text-align: center; border: 1px solid #ebebeb; }
    .score-card.done { border-top: 4px solid #245E55; }
    .score-card.pending { border-top: 4px solid #ebebeb; opacity: 0.85; }

    .score-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .score-card-label { font-size: 14px; font-weight: 500; letter-spacing: 0.03em; color: #aaa; display: flex; align-items: center; gap: 6px; }

    .score-status-badge { font-size: 13px; font-weight: 500; padding: 4px 11px; border-radius: 99px; white-space: nowrap; }
    .score-status-badge.pass { background: #e1f5ee; color: #0f6e56; }
    .score-status-badge.fail { background: #fcebeb; color: #a32d2d; }
    .score-status-badge.pending { background: #f3f3f3; color: #aaa; }

    .score-card-pct { font-size: 14px; font-weight: 500; color: #111; }
    .score-card-chevron { color: #aaa; transition: transform 0.2s ease; }
    .score-card.expanded .score-card-chevron { transform: rotate(180deg); }

    .score-detail { font-size: 15px; color: #555; margin: 0; }
    .score-detail.muted { color: #aaa; }

    /* Collapsible body */
    .score-card-body {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.35s ease;
    }
    .score-card.expanded .score-card-body {
        max-height: 3000px; /* large enough to fit content */
    }
    .score-card-body-inner { padding-top: 18px; }

    .qz-gauge-wrap { position: relative; width: 240px; height: 138px; margin: 8px auto 4px; }
    .qz-gauge-score {
        position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%);
        font-family: 'DM Sans', sans-serif; font-size: 42px; color: #111; line-height: 1;
    }

    .qz-verdict {
        font-size: 15px; font-weight: 500; margin: 0 0 20px;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .qz-verdict.pass { color: #1d9e75; }
    .qz-verdict.fail { color: #e24b4a; }

    .qz-ai-box {
        background: #fff; border: 1px solid #ebebeb; border-left: 3px solid #7f77dd;
        border-radius: 11px; padding: 16px 18px; text-align: left; margin-bottom: 16px;
    }
    .qz-ai-title {
        font-size: 14px; font-weight: 500; color: #7f77dd;
        letter-spacing: 0.06em; text-transform: uppercase;
        margin: 0 0 9px; display: flex; align-items: center; gap: 5px;
    }
    .qz-ai-sec { margin-bottom: 7px; }
    .qz-ai-sec:last-child { margin-bottom: 0; }
    .qz-ai-label { font-size: 13px; font-weight: 500; color: #111; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 2px; }
    .qz-ai-value { font-size: 14px; color: #555; margin: 0; line-height: 1.5; }

    .qz-history-card {
        background: #fff; border: 1px solid #ebebeb; border-radius: 11px;
        padding: 16px 18px; text-align: left;
    }
    .qz-history-title { font-size: 14px; font-weight: 500; color: #111; margin: 0 0 12px; display: flex; align-items: center; gap: 6px; }
    .qz-history-item { border: 1px solid #ebebeb; border-radius: 8px; margin-bottom: 8px; overflow: hidden; }
    .qz-history-item:last-child { margin-bottom: 0; }
    .qz-history-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; cursor: pointer; background: #fafafa; }
    .qz-history-row:hover { background: #f3f3f3; }
    .qz-history-left { display: flex; align-items: center; gap: 10px; }
    .qz-history-num { font-size: 13px; font-weight: 500; color: #555; }
    .qz-history-score { font-size: 12px; font-weight: 500; padding: 2px 8px; border-radius: 99px; background: #f3f3f3; color: #555; }
    .qz-history-score.pass { background: #e1f5ee; color: #0f6e56; }
    .qz-history-score.fail { background: #fcebeb; color: #a32d2d; }
    .qz-history-date { font-size: 12px; color: #aaa; }
    .qz-history-chevron { transition: transform 0.15s; color: #aaa; }
    .qz-history-item.open .qz-history-chevron { transform: rotate(180deg); }
    .qz-history-detail { display: none; padding: 14px; border-top: 1px solid #ebebeb; }
    .qz-history-item.open .qz-history-detail { display: block; }
    .qz-history-q { padding: 10px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 13px; border: 1px solid #e4e4e4; }
    .qz-history-q:last-child { margin-bottom: 0; }
    .qz-history-q.correct   { background: #f0fdf7; border-color: #bfe8d6; }
    .qz-history-q.incorrect { background: #fff8f8; border-color: #f4c9c8; }
    .qz-history-q-text { font-weight: 500; color: #111; margin: 0 0 6px; }
    .qz-history-q-ans  { font-size: 12px; color: #555; margin: 2px 0; }
    .qz-history-empty { font-size: 13px; color: #aaa; text-align: center; padding: 12px 0; }

    .growth-box { display: flex; align-items: center; gap: 16px; padding: 20px 24px; border-radius: 14px; border: 1px solid; }
    .growth-box.positive { background: #f0fdfa; border-color: #a7e8d8; }
    .growth-box.negative { background: #fef2f2; border-color: #fecaca; }
    .growth-box.neutral { background: #fafafa; border-color: #ebebeb; }

    .growth-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .growth-box.positive .growth-icon { background: #e1f5ee; color: #0f6e56; }
    .growth-box.negative .growth-icon { background: #fcebeb; color: #a32d2d; }
    .growth-box.neutral .growth-icon { background: #f3f3f3; color: #888; }

    .growth-label { font-size: 14px; font-weight: 500; letter-spacing: 0.03em; margin: 0 0 4px; color: #aaa; }
    .growth-value { margin: 0; font-family: 'DM Sans', sans-serif; font-size: 24px; font-weight: 500; display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
    .growth-box.positive .growth-value { color: #0f6e56; }
    .growth-box.negative .growth-value { color: #a32d2d; }
    .growth-note { font-size: 14px; font-weight: 500; color: #555; }
</style>
@endsection