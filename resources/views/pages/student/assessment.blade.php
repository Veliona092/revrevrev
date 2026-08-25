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

    /* Grid */
    .as-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
        gap: 16px;
    }

    /* Card — styled like an exam ticket stub */
    .as-card {
        background: var(--surface); border: 1px solid var(--line); border-radius: 12px;
        display: flex; flex-direction: column; overflow: hidden;
        transition: box-shadow 0.15s, border-color 0.15s, transform 0.15s;
    }

    .as-card:hover { box-shadow: 0 8px 24px rgba(27,29,42,0.08); border-color: #d3d3e6; transform: translateY(-2px); }

    .as-card-head {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        padding: 14px 18px 12px;
    }

    .as-eyebrow {
        font-family: var(--font); font-size: 11px; font-weight: 500;
        letter-spacing: 0.06em; text-transform: uppercase; color: var(--mute);
    }

    /* Status pills */
    .sp-score-pill {
        display: inline-block; font-size: 12px; font-weight: 600; padding: 4px 11px;
        border-radius: 99px; letter-spacing: 0.01em;
    }
    .sp-score-pill.high     { background: var(--good-tint); color: var(--good); }
    .sp-score-pill.mid      { background: var(--warn-tint); color: var(--warn); }
    .sp-score-pill.low      { background: var(--bad-tint);  color: var(--bad); }
    .sp-score-pill.neutral  { background: var(--accent-tint); color: var(--accent); }

    /* Perforated ticket divider */
    .as-perf {
        border-top: 1.5px dashed var(--line); margin: 0 18px;
    }

    .as-card-body { padding: 16px 18px 4px; flex: 1; }

    .as-title {
        font-family: var(--font); font-weight: 600; font-size: 18px;
        color: var(--ink); margin: 0 0 4px; line-height: 1.3;
    }

    .as-desc { font-size: 13.5px; color: var(--mute); margin: 0; line-height: 1.5; }

    /* Stats row */
    .as-stats {
        display: flex; gap: 14px; flex-wrap: wrap; align-items: center;
        padding: 14px 18px 4px; font-family: var(--font);
    }
    .as-stat  { display: flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--mute); white-space: nowrap; }
    .as-stat.due-soon { color: var(--warn); }
    .as-stat.overdue  { color: var(--bad); }
    .as-stat i { font-size: 12px; }

    .as-score-chip {
        display: inline-flex; align-items: center; gap: 5px;
        font-family: var(--font);
        font-size: 12.5px; font-weight: 600; padding: 3px 10px;
        border-radius: 99px; background: var(--accent-tint); color: var(--accent);
    }

    /* Footer — button always docked to the bottom */
    .as-card-footer { padding: 16px 18px 18px; margin-top: auto; }

    .as-btn {
        width: 100%; height: 42px; border-radius: 9px; font-size: 14.5px; font-weight: 600;
        background: var(--ink); color: #fff; border: none; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 6px; text-decoration: none; transition: background 0.15s;
        font-family: var(--font);
    }

    .as-btn:hover { background: var(--accent); color: #fff; }

    .as-btn.disabled {
        background: var(--paper); color: #b9b9c8; cursor: not-allowed;
        pointer-events: none; border: 1px solid var(--line);
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
                @endphp
                <div class="as-section" data-class="{{ $classId }}">
                    <div class="as-section-head">
                        <p class="as-section-title">
                            <i class="fas fa-graduation-cap" style="font-size:15px;color:var(--accent);"></i>
                            {{ $className }}
                            <span class="as-section-count">{{ $group->count() }} {{ Str::plural('assessment', $group->count()) }}</span>
                        </p>
                        @if($avgScore !== null)
                            <span class="as-section-avg"><i class="fas fa-chart-line"></i> Class avg {{ $avgScore }}%</span>
                        @else
                            <span class="as-section-avg" style="color:var(--mute);">No attempts yet</span>
                        @endif
                    </div>

                    <div class="as-grid">
                        @foreach($orderedGroup as $assessment)
                            @php
                                $attempt   = $assessment->student_attempt;
                                $pct       = $attempt?->percentage;
                                $pillClass = $attempt === null || $attempt->status === 'in_progress' ? 'neutral'
                                    : ($pct >= 75 ? 'high' : ($pct >= 50 ? 'mid' : 'low'));
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
                                $isOverdue = $assessment->isOverdue();
                                $isDueSoon = !$isOverdue && $assessment->due_date && $assessment->due_date->isFuture()
                                    && $assessment->due_date->diffInHours(now()) <= 48;
                            @endphp
                            <div class="as-card">
                                <div class="as-card-head">
                                    <span class="as-eyebrow">{{ $className }} · Assessment</span>
                                    <span class="sp-score-pill {{ $pillClass }}">{{ $pillLabel }}</span>
                                </div>

                                <div class="as-perf"></div>

                                <div class="as-card-body">
                                    <p class="as-title">{{ $assessment->title }}</p>
                                    @if($assessment->description)
                                        <p class="as-desc">{{ Str::limit($assessment->description, 60) }}</p>
                                    @endif
                                </div>

                                <div class="as-stats">
                                    <span class="as-stat"><i class="fas fa-clock"></i> {{ $timeLabel }}</span>
                                    <span class="as-stat"><i class="fas fa-list-ol"></i> {{ $qCount }} items</span>
                                    <span class="as-stat">
                                        <i class="fas fa-repeat"></i> Attempts: {{ $assessment->attempts_used }} / {{ $assessment->attempts_allowed }}
                                    </span>
                                    @if($assessment->due_date)
                                        <span class="as-stat {{ $isOverdue ? 'overdue' : ($isDueSoon ? 'due-soon' : '') }}">
                                            <i class="fas fa-calendar-alt"></i> Due {{ $assessment->due_date->format('M d, g:i A') }}
                                        </span>
                                    @endif
                                    @if($attempt !== null)
                                        <span class="as-score-chip"><i class="fas fa-star"></i> {{ $pct }}%</span>
                                    @endif
                                </div>

                                <div class="as-card-footer">
                                    @if($isOverdue)
                                        <span class="as-btn disabled">
                                            <i class="fas fa-lock"></i> Past Due
                                        </span>
                                    @elseif($attempt?->status === 'in_progress')
                                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn">
                                            <i class="fas fa-play"></i> Resume Assessment
                                        </a>
                                    @elseif($attempt === null)
                                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn">
                                            <i class="fas fa-play"></i> Take Assessment
                                        </a>
                                    @elseif($assessment->attempts_used < $assessment->attempts_allowed)
                                        <a href="{{ route('assessment.take', $assessment) }}" class="as-btn">
                                            <i class="fas fa-redo"></i> Retake ({{ $assessment->attempts_used + 1 }} of {{ $assessment->attempts_allowed }})
                                        </a>
                                    @else
                                        <span class="as-btn disabled">
                                            <i class="fas fa-ban"></i> Attempts Used Up
                                        </span>
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
