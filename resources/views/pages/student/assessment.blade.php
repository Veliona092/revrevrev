@extends($layout)

@section('title', 'Assessments')

@section('content')
<style>
    .as-wrap { display: flex; flex-direction: column; gap: 20px; }

    /* Class filter tabs */
    .as-tabs { display: flex; flex-wrap: wrap; gap: 6px; }

    .as-tab {
        height: 34px; padding: 0 16px;
        border-radius: 99px; font-size: 15px; font-weight: 500;
        border: 1px solid #e4e4e4; background: #fff; color: #777;
        cursor: pointer; transition: all 0.15s;
    }

    .as-tab:hover  { border-color: #aaa; color: #111; }
    .as-tab.active { background: #111; border-color: #111; color: #fff; }

    /* Grid */
    .as-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
        gap: 18px;
    }

    /* Card */
    .as-card {
        background: #fff; border: 1px solid #ebebeb; border-radius: 14px;
        padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;
        transition: box-shadow 0.15s, border-color 0.15s;
    }

    .as-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); border-color: #d8d8d8; }

    .as-card-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }

    .as-class-badge {
        font-size: 16px; font-weight: 500; padding: 4px 10px;
        border-radius: 99px; background: #111; color: #fff;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 55%;
    }

    /* Status pills */
    .sp-score-pill          { display: inline-block; font-size: 16px; font-weight: 500; padding: 3px 10px; border-radius: 99px; }
    .sp-score-pill.high     { background: #e1f5ee; color: #0f6e56; }
    .sp-score-pill.mid      { background: #faeeda; color: #854f0b; }
    .sp-score-pill.low      { background: #fcebeb; color: #a32d2d; }
    .sp-score-pill.neutral  { background: #f3f3f3; color: #888; }

    .as-title {
        font-family: 'DM Sans', sans-serif; font-size: 20px;
        color: #111; margin: 0 0 3px; line-height: 1.3;
    }

    .as-desc { font-size: 15px; color: #888; margin: 0; }

    /* Stats row */
    .as-stats { display: flex; gap: 16px; }
    .as-stat  { display: flex; align-items: center; gap: 6px; font-size: 16px; color: #aaa; }
    .as-stat i { font-size: 15px; }

    /* Score chip (if attempted) */
    .as-score-chip {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 16px; font-weight: 500; padding: 3px 10px;
        border-radius: 99px; background: #f3f4f6; color: #555;
    }

    /* Buttons */
    .as-btn {
        height: 38px; border-radius: 8px; font-size: 15px; font-weight: 500;
        background: #0f0f0f; color: #fff; border: none; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 5px; text-decoration: none; transition: background 0.15s;
    }

    .as-btn:hover { background: #333; color: #fff; }

    .as-btn.disabled {
        background: #f3f4f6; color: #aaa; cursor: not-allowed;
        pointer-events: none;
    }

    /* Empty state */
    .as-empty { text-align: center; padding: 4rem 0; color: #ccc; font-size: 16px; }
    .as-empty i { font-size: 32px; opacity: 0.2; display: block; margin-bottom: 10px; }
    .as-empty p { margin: 4px 0 0; }
    .as-empty .as-empty-sub { font-size: 15px; }
</style>

<div class="as-wrap">

    {{-- Class filter tabs --}}
    <div class="as-tabs">
        <button class="as-tab active" onclick="filterClass('all', this)">All</button>
        @foreach($classes as $cls)
            <button class="as-tab" onclick="filterClass({{ $cls->id }}, this)">
                {{ $cls->name }}
            </button>
        @endforeach
    </div>

    {{-- Assessment grid --}}
    @if($assessments->isEmpty())
        <div class="as-empty">
            <i class="fas fa-clipboard-list"></i>
            <p>No assessments yet</p>
            <p class="as-empty-sub">Your teacher hasn't published any formal assessments.</p>
        </div>
    @else
        <div class="as-grid" id="assessmentGrid">
            @foreach($assessments as $assessment)
                @php
                    $attempt   = $assessment->student_attempt;
                    $pct       = $attempt?->percentage;
                    $used      = $assessment->attempts_used ?? 0;
                    $allowed   = $assessment->attempts_allowed ?? 1;
                    $inProgress = $attempt !== null && $attempt->status === 'in_progress';
                    $exhausted = ! $inProgress && $used >= $allowed;
                    $pillClass = $attempt === null ? 'neutral'
                        : ($pct >= 75 ? 'high' : ($pct >= 50 ? 'mid' : 'low'));
                    $pillLabel = $attempt === null ? 'Not Started'
                        : ($pct >= 75 ? 'Passed with Merit' : ($pct >= 50 ? 'Passed' : 'Failed'));
                    $timeLabel = ($assessment->time_limit ?? 0) > 0
                        ? $assessment->time_limit . ' min'
                        : 'No limit';
                    $qCount = $assessment->quiz_questions_count;
                @endphp
                <div class="as-card" data-class="{{ $assessment->class_id }}">
                    <div class="as-card-meta">
                        <span class="as-class-badge">{{ $assessment->class->name ?? '-' }}</span>
                        <span class="sp-score-pill {{ $pillClass }}">{{ $pillLabel }}</span>
                    </div>

                    <div>
                        <p class="as-title">{{ $assessment->title }}</p>
                        @if($assessment->description)
                            <p class="as-desc">{{ Str::limit($assessment->description, 60) }}</p>
                        @endif
                    </div>

<div class="as-stats">
                        <span class="as-stat"><i class="fas fa-clock"></i> {{ $timeLabel }}</span>
                        <span class="as-stat"><i class="fas fa-list-ol"></i> {{ $qCount }} questions</span>
                        @if($assessment->due_date)
                            <span class="as-stat" style="{{ $assessment->isOverdue() ? 'color:#a32d2d;' : '' }}">
                                <i class="fas fa-calendar-alt"></i> Due {{ $assessment->due_date->format('M d, Y g:i A') }}
                            </span>
                        @endif
                        <span class="as-stat" style="{{ $exhausted ? 'color:#a32d2d;' : '' }}">
                            <i class="fas fa-redo"></i> Attempts: {{ $used }} / {{ $allowed }}
                        </span>
                        @if($attempt !== null)
                            <span class="as-score-chip"><i class="fas fa-star"></i> {{ $pct }}%</span>
                        @endif
                    </div>

                    @if($assessment->isOverdue())
                        <span class="as-btn disabled">
                            <i class="fas fa-lock"></i> Past Due
                        </span>
                    @elseif($inProgress)
                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn">
                            <i class="fas fa-play"></i> Resume
                        </a>
                    @elseif($attempt === null)
                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn">
                            <i class="fas fa-play"></i> Take Assessment
                        </a>
                    @elseif($exhausted)
                        <span class="as-btn disabled">
                            <i class="fas fa-lock"></i> Attempts Used Up
                        </span>
                    @else
                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn"
                           title="Retaking replaces your previous score">
                            <i class="fas fa-redo"></i> Retake ({{ $used + 1 }} of {{ $allowed }})
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>

@section('scripts')
<script>
    function filterClass(classId, btn) {
        document.querySelectorAll('.as-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('#assessmentGrid .as-card').forEach(card => {
            card.style.display = (classId === 'all' || card.dataset.class == classId) ? '' : 'none';
        });
    }
</script>
@endsection
@endsection

