@extends($layout)

@section('title', 'Assessments')

@section('content')
<style>
    :root {
        --ink:        #1B1D2A;
        --paper:      #F7F7FB;
        --surface:    #FFFFFF;
        --line:       #E7E7F1;
        --accent:     #3730A9;
        --accent-tint:#EEEDFB;
        --good:       #0F766E;
        --good-tint:  #E3F5F1;
        --warn:       #B45309;
        --warn-tint:  #FBF0DE;
        --bad:        #B91C1C;
        --bad-tint:   #FBEAEA;
        --mute:       #8A8A9C;
    }

    .as-wrap * { box-sizing: border-box; }

    .as-wrap {
        display: flex; flex-direction: column; gap: 28px;
        font-family: var(--font); color: var(--ink);
    }

    /* Filter chips */
    .as-tabs { display: flex; flex-wrap: wrap; gap: 8px; }

    .as-tab {
        height: 36px; padding: 0 16px;
        border-radius: 99px; font-size: 14px; font-weight: 500;
        border: 1px solid var(--line); background: var(--surface); color: var(--mute);
        cursor: pointer; transition: all 0.15s; font-family: var(--font);
    }

    .as-tab:hover  { border-color: var(--accent); color: var(--accent); }
    .as-tab.active { background: var(--accent); border-color: var(--accent); color: #fff; }

    /* Class section */
    .as-section { display: flex; flex-direction: column; gap: 14px; }

    .as-section-head {
        display: flex; align-items: baseline; justify-content: space-between;
        gap: 12px; padding-bottom: 10px; border-bottom: 2px solid var(--ink);
    }

    .as-section-title {
        font-family: var(--font); font-weight: 600;
        font-size: 19px; margin: 0; letter-spacing: -0.01em;
        display: flex; align-items: center; gap: 10px;
    }

    .as-section-count {
        font-family: var(--font); font-size: 12px;
        color: var(--mute); font-weight: 500;
    }

    .as-section-avg {
        font-family: var(--font); font-size: 13px;
        color: var(--good); font-weight: 500; white-space: nowrap;
    }

    /* Status breakdown chips */
    .as-status-chip {
        display: inline-flex; align-items: center; gap: 4px;
        font-family: var(--font); font-size: 11.5px; font-weight: 600;
        padding: 3px 10px; border-radius: 99px; white-space: nowrap;
        vertical-align: middle;
        margin-left: 4px;
    }

    .as-status-chip.not-started { background: var(--paper); color: var(--mute); }
    .as-status-chip.in-progress { background: var(--accent-tint); color: var(--accent); }
    .as-status-chip.merit       { background: var(--good); color: #fff; }
    .as-status-chip.passed      { background: var(--good-tint); color: var(--good); }
    .as-status-chip.failed      { background: var(--bad-tint); color: var(--bad); }

    /* Table-style list */
    .as-table {
        display: flex; flex-direction: column; gap: 10px;
    }

    .as-table-head {
        display: grid;
        grid-template-columns: 2fr 1.3fr 1.3fr 1.3fr 1fr 1.3fr;
        gap: 12px; padding: 0 20px 8px;
        font-family: var(--font); font-size: 12px; font-weight: 700;
        color: var(--ink); text-transform: uppercase; letter-spacing: 0.04em;
    }

    .as-row {
        display: grid;
        grid-template-columns: 2fr 1.3fr 1.3fr 1.3fr 1fr 1.3fr;
        gap: 12px; align-items: center;
        padding: 16px 20px; border-radius: 12px;
        background: var(--surface); border: 1px solid var(--line);
        transition: box-shadow 0.15s, border-color 0.15s;
    }

    .as-row:nth-child(even) { background: var(--paper); }
    .as-row:hover { box-shadow: 0 4px 14px rgba(27,29,42,0.06); border-color: #d3d3e6; }

    .as-row-title {
        font-family: var(--font); font-weight: 600; font-size: 16px;
        color: var(--ink); margin: 0;
    }

    .as-row-info { font-size: 13.5px; color: var(--mute); font-family: var(--font); }
    .as-row-info.overdue  { color: var(--bad);  font-weight: 600; }
    .as-row-info.due-soon { color: var(--warn); font-weight: 600; }

    .as-row-score {
        font-family: var(--font); font-weight: 700; font-size: 14.5px; color: var(--ink);
    }
    .as-row-score .max { color: var(--mute); font-weight: 500; }

    .as-row-action { display: flex; justify-content: flex-end; gap: 6px; }

    .as-btn-pill {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        height: 38px; padding: 0 20px; border-radius: 99px;
        background: #86EFAC; color: #14532D; font-weight: 700; font-size: 13.5px;
        border: none; text-decoration: none; cursor: pointer;
        font-family: var(--font); transition: background 0.15s;
    }
    .as-btn-pill:hover { background: #6EE7A0; color: #14532D; }

    .as-btn-pill.outline {
        background: var(--surface); color: var(--accent); border: 1px solid var(--accent-tint);
    }
    .as-btn-pill.outline:hover { background: var(--accent-tint); }

    .as-btn-pill.disabled {
        background: var(--paper); color: #b9b9c8; cursor: not-allowed;
        pointer-events: none; border: 1px solid var(--line);
    }

    @media (max-width: 768px) {
        .as-table-head { display: none; }
        .as-row {
            grid-template-columns: 1fr; gap: 6px;
            padding: 16px;
        }
        .as-row-action { justify-content: stretch; }
        .as-btn-pill { width: 100%; }
    }

    /* Empty states */
    .as-empty {
        text-align: center; padding: 4rem 0; color: var(--mute);
        font-family: var(--font);
    }
    .as-empty i { font-size: 30px; opacity: 0.25; display: block; margin-bottom: 12px; color: var(--accent); }
    .as-empty p { margin: 4px 0 0; font-size: 15px; color: var(--ink); font-weight: 500; }
    .as-empty .as-empty-sub { font-size: 13.5px; color: var(--mute); font-weight: 400; margin-top: 2px; }

    @media (max-width: 480px) {
        .as-grid { grid-template-columns: 1fr; }
        .as-section-head { flex-direction: column; align-items: flex-start; gap: 4px; }
    }
</style>

<div class="as-wrap">

    {{-- Class filter chips --}}
    <div class="as-tabs">
        <button class="as-tab active" onclick="filterClass('all', this)">All classes</button>
        @foreach($classes as $cls)
            <button class="as-tab" onclick="filterClass({{ $cls->id }}, this)">
                {{ $cls->name }}
            </button>
        @endforeach
    </div>

    @if($assessments->isEmpty())
        <div class="as-empty">
            <i class="fas fa-clipboard-list"></i>
            <p>No assessments yet</p>
            <p class="as-empty-sub">Your teacher hasn't published any formal assessments.</p>
        </div>
    @else
        @php
            $sections = $assessments->groupBy('class_id')->sortBy(function ($group) {
                return $group->first()->class->name ?? '';
            });
        @endphp

        <div id="assessmentSections">
            @foreach($sections as $classId => $group)
                @php
                    $className = $group->first()->class->name ?? 'Unassigned';
                    $scored    = $group->pluck('student_attempt.percentage')->filter(fn ($v) => !is_null($v));
                    $avgScore  = $scored->isNotEmpty() ? round($scored->avg()) : null;
                    $orderedGroup = $group->sortBy(function ($assessment) {
                        $attempt = $assessment->student_attempt;

                        $priority = match (true) {
                            $attempt === null => 0,
                            $attempt->status === 'in_progress' => 1,
                            $attempt->percentage < 50 => 2,
                            $attempt->percentage < 75 => 3,
                            default => 4,
                        };

                        return sprintf('%d-%s', $priority, $assessment->title);
                    });

                    // Bilangin ang status ng bawat assessment sa section na ito
                    $statusCounts = ['not_started' => 0, 'in_progress' => 0, 'merit' => 0, 'passed' => 0, 'failed' => 0];
                    foreach ($group as $item) {
                        $itemAttempt = $item->student_attempt;
                        if ($itemAttempt === null) {
                            $statusCounts['not_started']++;
                        } elseif ($itemAttempt->status === 'in_progress') {
                            $statusCounts['in_progress']++;
                        } elseif ($itemAttempt->percentage >= 75) {
                            $statusCounts['merit']++;
                        } elseif ($itemAttempt->percentage >= 50) {
                            $statusCounts['passed']++;
                        } else {
                            $statusCounts['failed']++;
                        }
                    }
                @endphp
                <div class="as-section" data-class="{{ $classId }}">
                    <div class="as-section-head">
                        <p class="as-section-title">
                            <i class="fas fa-graduation-cap" style="font-size:15px;color:var(--accent);"></i>
                            {{ $className }}
                            <span class="as-section-count">{{ $group->count() }} {{ Str::plural('assessment', $group->count()) }}</span>
                            @if($statusCounts['not_started'] > 0)
                                <span class="as-status-chip not-started">Not Started: {{ $statusCounts['not_started'] }}</span>
                            @endif
                            @if($statusCounts['in_progress'] > 0)
                                <span class="as-status-chip in-progress">In Progress: {{ $statusCounts['in_progress'] }}</span>
                            @endif
                            @if($statusCounts['merit'] > 0)
                                <span class="as-status-chip merit">Passed with Merit: {{ $statusCounts['merit'] }}</span>
                            @endif
                            @if($statusCounts['passed'] > 0)
                                <span class="as-status-chip passed">Passed: {{ $statusCounts['passed'] }}</span>
                            @endif
                            @if($statusCounts['failed'] > 0)
                                <span class="as-status-chip failed">Failed: {{ $statusCounts['failed'] }}</span>
                            @endif
                        </p>
                        @if($avgScore !== null)
                            <span class="as-section-avg"><i class="fas fa-chart-line"></i> Class avg {{ $avgScore }}%</span>
                        @else
                            <span class="as-section-avg" style="color:var(--mute);">No attempts yet</span>
                        @endif
                    </div>

                    <div class="as-table">
                        <div class="as-table-head">
                            <span>Title</span>
                            <span>Status</span>
                            <span>Time / Items</span>
                            <span>Due / Attempts</span>
                            <span>Best Score</span>
                            <span></span>
                        </div>

                        @foreach($orderedGroup as $assessment)
                            @php
                                $attempt   = $assessment->student_attempt;
                                $pct       = $attempt?->percentage;
                                $pillLabel = match (true) {
                                    $attempt === null => 'Not Started',
                                    $attempt->status === 'in_progress' => 'In Progress',
                                    $pct >= 75 => 'Passed with Merit',
                                    $pct >= 50 => 'Passed',
                                    default => 'Failed',
                                };
                                $timeLabel = ($assessment->time_limit ?? 0) > 0
                                    ? $assessment->time_limit . ' min'
                                    : 'No limit';
                                $qCount    = $assessment->quiz_questions_count;
                                $isUpcoming = $assessment->isUpcoming();
                                $isInactive = ! ($assessment->is_active ?? true);
                                $isOverdue  = $assessment->isOverdue();
                                $isDueSoon  = !$isOverdue && !$isUpcoming && $assessment->due_date && $assessment->due_date->isFuture()
                                    && $assessment->due_date->diffInHours(now()) <= 48;
                            @endphp
                            <div class="as-row">
                                <p class="as-row-title">{{ $assessment->title }}</p>

                                <span class="as-row-info">{{ $pillLabel }}</span>

                                <span class="as-row-info">{{ $timeLabel }} · {{ $qCount }} items</span>

                                <span class="as-row-info {{ $isOverdue ? 'overdue' : ($isUpcoming ? 'due-soon' : ($isDueSoon ? 'due-soon' : '')) }}">
                                    @if($isInactive)
                                        <span style="color:#6b7280;">Inactive</span>
                                    @elseif($isUpcoming)
                                        <span style="color:#d97706;"><i class="fas fa-clock"></i> Opens {{ $assessment->available_at->format('M d, g:i A') }}</span>
                                    @elseif($assessment->due_date)
                                        Due {{ $assessment->due_date->format('M d, g:i A') }}
                                    @else
                                        Attempts: {{ $assessment->attempts_used }} / {{ $assessment->attempts_allowed }}
                                    @endif
                                </span>

                                <span class="as-row-score">
                                    @if($attempt !== null)
                                        {{ $pct }}<span class="max">%</span>
                                    @else
                                        <span class="max">—</span>
                                    @endif
                                </span>

                                <div class="as-row-action">
                                    @if($attempt !== null && $attempt->status !== 'in_progress')
                                        <a href="{{ route('assessment.results', $assessment) }}" class="as-btn-pill outline"><i class="fas fa-clipboard-check"></i> Review</a>
                                    @endif

                                    @if($isInactive)
                                        <span class="as-btn-pill disabled"><i class="fas fa-lock"></i> Inactive</span>
                                    @elseif($isUpcoming)
                                        <span class="as-btn-pill disabled" title="Available on {{ $assessment->available_at->format('M d, Y g:i A') }}"><i class="fas fa-clock"></i> Opens {{ $assessment->available_at->format('M d, g:i A') }}</span>
                                    @elseif($isOverdue)
                                        <span class="as-btn-pill disabled"><i class="fas fa-lock"></i> Past Due</span>
                                    @elseif($attempt?->status === 'in_progress')
                                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn-pill">Resume Assessment</a>
                                    @elseif($attempt === null)
                                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn-pill">Take Assessment</a>
                                    @elseif($assessment->attempts_used < $assessment->attempts_allowed)
                                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn-pill">Retake ({{ $assessment->attempts_used + 1 }} of {{ $assessment->attempts_allowed }})</a>
                                    @else
                                        <span class="as-btn-pill disabled">Attempts Used Up</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
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

        document.querySelectorAll('#assessmentSections .as-section').forEach(section => {
            section.style.display = (classId === 'all' || section.dataset.class == classId) ? '' : 'none';
        });
    }
</script>
@endsection
@endsection
