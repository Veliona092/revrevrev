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
        <div class="score-card {{ isset($attempts['pre_test']) ? 'done' : 'pending' }}">
            <div class="score-card-head">
                <span class="score-card-label"><i class="fas fa-pencil-alt"></i> Pre-Test</span>
                @if(isset($attempts['pre_test']))
                    <span class="score-status-badge {{ $attempts['pre_test']->passed ? 'pass' : 'fail' }}">
                        {{ $attempts['pre_test']->passed ? 'Passed' : 'Failed' }}
                    </span>
                @else
                    <span class="score-status-badge pending">Not Taken</span>
                @endif
            </div>

            @if(isset($attempts['pre_test']))
                <div class="big-score">{{ $attempts['pre_test']->percentage }}%</div>
                <div class="score-bar-bg">
                    <div class="score-bar-fill {{ $attempts['pre_test']->passed ? 'pass' : 'fail' }}" style="width: {{ $attempts['pre_test']->percentage }}%;"></div>
                </div>
                <p class="score-detail">{{ $attempts['pre_test']->score }} / {{ $attempts['pre_test']->total_questions }} correct</p>
            @else
                <div class="big-score empty">--</div>
                <p class="score-detail muted">You haven't taken this phase yet.</p>
            @endif
        </div>

        {{-- Pre-Board Card --}}
        <div class="score-card {{ isset($attempts['pre_boards']) ? 'done' : 'pending' }}">
            <div class="score-card-head">
                <span class="score-card-label"><i class="fas fa-clipboard-check"></i> Pre-Board</span>
                @if(isset($attempts['pre_boards']))
                    <span class="score-status-badge {{ $attempts['pre_boards']->passed ? 'pass' : 'fail' }}">
                        {{ $attempts['pre_boards']->passed ? 'Passed' : 'Failed' }}
                    </span>
                @else
                    <span class="score-status-badge pending">Not Taken</span>
                @endif
            </div>

            @if(isset($attempts['pre_boards']))
                <div class="big-score">{{ $attempts['pre_boards']->percentage }}%</div>
                <div class="score-bar-bg">
                    <div class="score-bar-fill {{ $attempts['pre_boards']->passed ? 'pass' : 'fail' }}" style="width: {{ $attempts['pre_boards']->percentage }}%;"></div>
                </div>
                <p class="score-detail">{{ $attempts['pre_boards']->score }} / {{ $attempts['pre_boards']->total_questions }} correct</p>
            @else
                <div class="big-score empty">--</div>
                <p class="score-detail muted">Locked or not taken yet.</p>
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
@endsection

@section('head')
<style>
    .results-container {
        max-width: 800px; margin: 0 auto; padding: 4px 0 20px;
        font-family: 'DM Sans', sans-serif;
    }

    .results-intro { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; }
    .results-icon { width: 52px; height: 52px; border-radius: 12px; background: #e6f4ea; color: #245E55; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .results-title { margin: 0; font-family: 'DM Sans', sans-serif; font-size: 24px; font-weight: 500; color: #111; }
    .results-subtitle { margin: 4px 0 0; font-size: 14px; color: #aaa; font-weight: 500; }

    .score-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .score-card { background: #fff; padding: 26px; border-radius: 14px; text-align: center; border: 1px solid #ebebeb; }
    .score-card.done { border-top: 4px solid #245E55; }
    .score-card.pending { border-top: 4px solid #ebebeb; opacity: 0.85; }

    .score-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .score-card-label { font-size: 14px; font-weight: 500; letter-spacing: 0.03em; color: #aaa; display: flex; align-items: center; gap: 6px; }

    .score-status-badge { font-size: 13px; font-weight: 500; padding: 4px 11px; border-radius: 99px; white-space: nowrap; }
    .score-status-badge.pass { background: #e1f5ee; color: #0f6e56; }
    .score-status-badge.fail { background: #fcebeb; color: #a32d2d; }
    .score-status-badge.pending { background: #f3f3f3; color: #aaa; }

    .big-score { font-family: 'DM Sans', sans-serif; font-size: 34px; font-weight: 500; color: #111; margin: 6px 0 12px; line-height: 1; }
    .big-score.empty { color: #ccc; }

    .score-bar-bg { width: 100%; height: 8px; background: #f3f3f3; border-radius: 99px; overflow: hidden; margin-bottom: 12px; }
    .score-bar-fill { height: 100%; border-radius: 99px; transition: width 0.5s ease; }
    .score-bar-fill.pass { background: #245E55; }
    .score-bar-fill.fail { background: #e24b4a; }

    .score-detail { font-size: 15px; color: #555; margin: 0; }
    .score-detail.muted { color: #aaa; }

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

    @media (max-width: 620px) {
        .score-cards { grid-template-columns: 1fr; }
    }
</style>
@endsection