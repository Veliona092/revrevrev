@extends(($isAdmin ?? false) ? 'layouts.appAdmin' : 'layouts.appTeach')

@section('title', 'Mock Board Analysis - ' . ucfirst($program))
@section('page-heading', 'Batch Performance: ' . ucfirst($program))

@section('header-actions')
    <button class="rv-btn rv-btn-secondary" onclick="window.print()">
        <i class="fas fa-download"></i> Export Report
    </button>
@endsection

@section('content')
<style>
    .filter-card { background: #fff; border: 1px solid #DDD8CF; border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .filter-group { display: flex; align-items: center; gap: 12px; }
    .filter-label { font-size: 14px; font-weight: 600; color: #5a5550; }
    .filter-select { background: #F7F2E9; border: 1px solid #DDD8CF; border-radius: 8px; padding: 8px 14px; font-size: 14px; color: #2D2D2B; outline: none; cursor: pointer; }
    .filter-select:focus { border-color: #ED773C; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 36px; }
    .stat-card { background: #fff; padding: 24px; border-radius: 16px; border: 1px solid #DDD8CF; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .stat-card.batch-hero { background: #1a4840; color: #fff; grid-column: span 2; }
    .stat-label { font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.8; margin-bottom: 8px; }
    .stat-value { font-size: 32px; font-weight: 600; }
    .stat-meta { font-size: 14px; margin-top: 8px; opacity: 0.7; }

    .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px;}
    .class-card { background: #F7F2E9; border: 1px solid #DDD8CF; border-radius: 12px; padding: 20px; transition: transform 0.2s; }
    .class-card:hover { transform: translateY(-4px); border-color: #ED773C; }

    .rv-table-card { background: #fff; border-radius: 16px; border: 1px solid #DDD8CF; overflow: hidden; }
    .rv-table { width: 100%; border-collapse: collapse; text-align: left; }
    .rv-table th { padding: 16px 24px; background: #F7F2E9; font-size: 13px; text-transform: uppercase; color: #8a8580; font-weight: 600; border-bottom: 1px solid #DDD8CF; }
    .rv-table td { padding: 16px 24px; border-bottom: 1px solid #F0EDE7; font-size: 16px; }

    .badge-pass { background: #d4e8e5; color: #1a4840; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; }
    .badge-fail { background: #f5e3e3; color: #9e2f2e; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; }
    .badge-pending { background: #f0f0f0; color: #8a8580; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; }

    .chart-card { background: #fff; border: 1px solid #DDD8CF; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
    .chart-card h4 { margin: 0 0 16px; font-size: 18px; color: #2D2D2B; }
    .chart-canvas-wrap { position: relative; width: 100%; }
    .chart-row { display: grid; grid-template-columns: 1fr 1.4fr; gap: 20px; margin-bottom: 32px; }
    @media (max-width: 900px) { .chart-row { grid-template-columns: 1fr; } }
</style>

{{-- Admin Control Bar --}}
@if($isAdmin ?? false)
<form method="GET" action="{{ route('admin.mock-board-analytics') }}" id="analyticsFilterForm" class="filter-card">
    <div class="filter-group">
        <span class="filter-label"><i class="fas fa-filter"></i> View Mode:</span>
        <select name="view_type" class="filter-select" onchange="document.getElementById('analyticsFilterForm').submit()">
            <option value="batch" {{ $viewType === 'batch' ? 'selected' : '' }}>Whole Batch (Global)</option>
            <option value="program" {{ $viewType === 'program' ? 'selected' : '' }}>Per Program</option>
            <option value="class" {{ $viewType === 'class' ? 'selected' : '' }}>Per Specific Class</option>
        </select>
    </div>

    @if($viewType === 'program')
    <div class="filter-group">
        <span class="filter-label">Select Program:</span>
        <select name="program" class="filter-select" onchange="document.getElementById('analyticsFilterForm').submit()">
            <option value="All">All Programs</option>
            @foreach($programsList as $p)
                <option value="{{ $p }}" {{ $selectedProgram === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    @endif

    @if($viewType === 'class')
    <div class="filter-group">
        <span class="filter-label">Select Class:</span>
        <select name="class_id" class="filter-select" onchange="document.getElementById('analyticsFilterForm').submit()">
            <option value="">All Classes</option>
            @foreach($classList as $c)
                <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->program }})</option>
            @endforeach
        </select>
    </div>
    @endif
</form>
@endif

@php
    $examLabelMap = [
        'accountancy' => 'CPALE',
        'education' => 'LEPT',
        'educ' => 'LEPT',
        'psychology' => 'RPsy Board Exam',
        'psych' => 'RPsy Board Exam',
    ];
    $examLabel = $examLabelMap[strtolower($program)] ?? strtoupper($program);
@endphp

{{-- Top Level Batch Stats --}}
<div class="stats-grid">
    <div class="stat-card batch-hero">
        <div class="stat-label">Overall Passing Rate</div>
        <div class="stat-value">{{ $summary['pre_boards_passing_rate'] ?? 0 }}%</div>
        <div class="stat-meta">Target Rate for {{ ucfirst($program) }}: {{ $mockBoard->passing_percentage ?? '75' }}%</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Participants</div>
        <div class="stat-value">{{ $summary['total_students'] }}</div>
        <div class="stat-meta">Active in this Mock Board</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">ANOVA Result</div>
        <div class="stat-value" style="color: {{ ($anova['significant'] ?? false) ? '#22C55E' : '#C63F3E' }}">
            {{ ($anova['significant'] ?? false) ? 'Significant' : 'No Change' }}
        </div>
        <div class="stat-meta">P-Value: {{ $anova['p_value'] ?? 'N/A' }}</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #ED773C;">
        <div class="stat-label">Projected {{ $examLabel }} Pass Rate</div>
        <div class="stat-value">{{ $forecast['projected_batch_pass_rate'] ?? 0 }}%</div>
        <div class="stat-meta">Sample: {{ $forecast['sample_size'] ?? 0 }} student(s) &bull; {{ $forecast['confidence_note'] ?? '' }}</div>
    </div>
</div>

<div class="tab-nav" style="display:flex; gap:8px; margin-bottom:24px; border-bottom:2px solid #DDD8CF;">
    <button class="tab-btn active" onclick="switchTab('overview', this)" style="padding:12px 20px; border:none; background:none; font-weight:600; cursor:pointer; border-bottom:3px solid #245E55;">Overview</button>
    <button class="tab-btn" onclick="switchTab('item-analysis', this)" style="padding:12px 20px; border:none; background:none; font-weight:600; cursor:pointer; color:#8a8580; border-bottom:3px solid transparent;">Item Analysis</button>
</div>

<div id="tab-overview">

{{-- Individual Student List --}}
<h3 style="margin-bottom: 20px; font-size: 24px;">Individual Student Results</h3>
<div class="rv-table-card">
    <table class="rv-table">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Program</th>
                <th>Pre-Test</th>
                <th>Pre-Board</th>
                <th>Improvement</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($student_results as $result)
            <tr>
                <td style="font-weight: 500; color: #2D2D2B;">{{ $result['name'] }}</td>
                <td style="color: #5a5550; font-size: 14px;">{{ $result['program'] }}</td>
                <td>{{ $result['pre_test_score'] !== null ? $result['pre_test_score'].'%' : '--' }}</td>
                <td>{{ $result['pre_boards_score'] !== null ? $result['pre_boards_score'].'%' : '--' }}</td>
                <td>
                    @if(($result['improvement'] ?? 0) > 0)
                        <span style="color: #22C55E; font-weight: 600;">+{{ $result['improvement'] }}%</span>
                    @elseif(($result['improvement'] ?? 0) < 0)
                        <span style="color: #C63F3E; font-weight: 600;">{{ $result['improvement'] }}%</span>
                    @else
                        <span style="color: #B5AFA5;">0%</span>
                    @endif
                </td>
                <td>
                    @php
                        $hasPassedCurrent = false;
                        if ($result['pre_boards_score'] !== null) {
                            $hasPassedCurrent = $result['pre_boards_passed'];
                        } else {
                            $hasPassedCurrent = $result['pre_test_passed'] ?? false;
                        }

                        $isStarted = $result['pre_test_score'] !== null || $result['pre_boards_score'] !== null;
                    @endphp

                    @if(!$isStarted)
                        <span class="badge-pending">NOT STARTED</span>
                    @elseif($hasPassedCurrent)
                        <span class="badge-pass">PASSED</span>
                    @else
                        <span class="badge-fail">FAILED</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #8a8580; padding: 24px;">No student results found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

</div><!-- end tab-overview -->

<div id="tab-item-analysis" style="display:none;">
    <div class="filter-card" style="justify-content:flex-start; gap:16px;">
        <span class="filter-label">Phase:</span>
        <button class="tab-btn phase-btn active" onclick="switchPhase('pre_test', this)" style="padding:8px 16px; border:1px solid #DDD8CF; border-radius:8px; background:#245E55; color:#fff; cursor:pointer;">Pre-Test</button>
        <button class="tab-btn phase-btn" onclick="switchPhase('pre_boards', this)" style="padding:8px 16px; border:1px solid #DDD8CF; border-radius:8px; background:#fff; color:#5a5550; cursor:pointer;">Pre-Boards</button>
    </div>

    @foreach(['pre_test', 'pre_boards'] as $phaseKey)
    @php
        $phaseQuestions = $item_analysis[$phaseKey] ?? [];

        $colorMap = [
            'Very Difficult' => '#C63F3E',
            'Difficult'      => '#ED773C',
            'Moderate'       => '#EAC119',
        ];
        $defaultColor = '#245E55';

        $distributionCounts = collect($phaseQuestions)->countBy('interpretation');
        $distLabels = $distributionCounts->keys()->values()->all();
        $distValues = $distributionCounts->values()->values()->all();
        $distColors = collect($distLabels)->map(fn ($label) => $colorMap[$label] ?? $defaultColor)->all();

        $barLabels = [];
        $barShortLabels = [];
        $barValues = [];
        $barColors = [];
        foreach ($phaseQuestions as $idx => $q) {
            $barLabels[] = 'Q'.($q['order'] ?? ($idx + 1)).' — '.\Illuminate\Support\Str::limit($q['question_text'], 60);
            $barShortLabels[] = 'Q'.($q['order'] ?? ($idx + 1));
            $barValues[] = round($q['difficulty'] * 100, 1);
            $barColors[] = $colorMap[$q['interpretation']] ?? $defaultColor;
        }
    @endphp
    <div id="phase-{{ $phaseKey }}" style="{{ $phaseKey !== 'pre_test' ? 'display:none;' : '' }}"
         data-dist-labels='@json($distLabels)'
         data-dist-values='@json($distValues)'
         data-dist-colors='@json($distColors)'
         data-bar-labels='@json($barLabels)'
         data-bar-short-labels='@json($barShortLabels)'
         data-bar-values='@json($barValues)'
         data-bar-colors='@json($barColors)'>

        @if(empty($phaseQuestions))
            <p style="color:#8a8580; padding:20px;">No data yet.</p>
        @else
            <div class="chart-row">
                <div class="chart-card">
                    <h4>Difficulty Distribution</h4>
                    <div class="chart-canvas-wrap" style="height: 280px;">
                        <canvas id="distChart_{{ $phaseKey }}"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h4>% Correct per Question <span style="font-weight:400; color:#8a8580; font-size:14px;">(lower percentage = more difficult)</span></h4>
                    <div class="chart-canvas-wrap" style="height: 320px; max-width: {{ max(400, count($barValues) * 60) }}px; margin-right: auto;">
                        <canvas id="barChart_{{ $phaseKey }}"></canvas>
                    </div>
                </div>
            </div>

            @php $hardest = collect($phaseQuestions)->sortBy('difficulty')->take(10)->values(); @endphp

            <h4 style="margin:24px 0 12px;">Needs Review — Hardest Question</h4>
            <div class="class-grid" style="margin-bottom:32px;">
                @foreach($hardest as $q)
                <div class="class-card">
                    <p style="font-size:14px; margin:0 0 8px; color:#2D2D2B;">{{ Str::limit($q['question_text'], 90) }}</p>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:12px; color:#8a8580;">{{ $q['correct_count'] }}/{{ $q['total_count'] }} correct</span>
                        <span style="font-weight:700; color:#C63F3E;">{{ round($q['difficulty'] * 100, 1) }}%</span>
                    </div>
                </div>
                @endforeach
            </div>

            <h4 style="margin:24px 0 12px;">All of the Questions</h4>
            <div class="rv-table-card">
                <table class="rv-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Answered</th>
                            <th>% Correct</th>
                            <th>Difficulty</th>
                            <th>Discrimination</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($phaseQuestions as $q)
                        <tr>
                            <td>{{ $q['order'] ?? $loop->iteration }}</td>
                            <td>{{ Str::limit($q['question_text'], 70) }}</td>
                            <td>{{ $q['correct_count'] }}/{{ $q['total_count'] }}</td>
                            <td>{{ round($q['difficulty'] * 100, 1) }}%</td>
                            <td>
                                @if($q['interpretation'] === 'Very Difficult')
                                    <span class="badge-fail">{{ $q['interpretation'] }}</span>
                                @elseif(in_array($q['interpretation'], ['Difficult', 'Moderate']))
                                    <span class="badge-pending">{{ $q['interpretation'] }}</span>
                                @else
                                    <span class="badge-pass">{{ $q['interpretation'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if($q['discrimination'] === null)
                                    <span style="color:#8a8580; font-size:12px;">No data yet.</span>
                                @elseif($q['discrimination'] < 0.1)
                                    <span class="badge-fail">{{ $q['discrimination'] }} — Need to review</span>
                                @elseif($q['discrimination'] < 0.3)
                                    <span class="badge-pending">{{ $q['discrimination'] }}</span>
                                @else
                                    <span class="badge-pass">{{ $q['discrimination'] }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endforeach
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    function switchTab(tab, btn) {
        document.getElementById('tab-overview').style.display = tab === 'overview' ? 'block' : 'none';
        document.getElementById('tab-item-analysis').style.display = tab === 'item-analysis' ? 'block' : 'none';

        document.querySelectorAll('.tab-nav .tab-btn').forEach(b => {
            b.style.borderBottomColor = 'transparent';
            b.style.color = '#8a8580';
        });
        btn.style.borderBottomColor = '#245E55';
        btn.style.color = '#2D2D2B';

        if (tab === 'item-analysis') {
            const activePhase = document.querySelector('#tab-item-analysis [id^="phase-"]:not([style*="display:none"])');
            if (activePhase) initPhaseCharts(activePhase.id.replace('phase-', ''));
        }
    }

    function switchPhase(phase, btn) {
        document.getElementById('phase-pre_test').style.display = phase === 'pre_test' ? 'block' : 'none';
        document.getElementById('phase-pre_boards').style.display = phase === 'pre_boards' ? 'block' : 'none';

        document.querySelectorAll('.phase-btn').forEach(b => {
            b.style.background = '#fff';
            b.style.color = '#5a5550';
        });
        btn.style.background = '#245E55';
        btn.style.color = '#fff';

        initPhaseCharts(phase);
    }

    const rvChartInstances = {};

    document.addEventListener('DOMContentLoaded', function () {
        initPhaseCharts('pre_test');
    });

    function initPhaseCharts(phase) {
        const wrap = document.getElementById('phase-' + phase);
        if (!wrap) return;

        const distKey = 'dist_' + phase;
        const barKey = 'bar_' + phase;

        const distCanvas = document.getElementById('distChart_' + phase);
        if (distCanvas && !rvChartInstances[distKey]) {
            rvChartInstances[distKey] = new Chart(distCanvas, {
                type: 'doughnut',
                data: {
                    labels: JSON.parse(wrap.dataset.distLabels || '[]'),
                    datasets: [{
                        data: JSON.parse(wrap.dataset.distValues || '[]'),
                        backgroundColor: JSON.parse(wrap.dataset.distColors || '[]'),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                    return ctx.label + ': ' + ctx.parsed + ' questions (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        const barCanvas = document.getElementById('barChart_' + phase);
        if (barCanvas && !rvChartInstances[barKey]) {
            const fullLabels = JSON.parse(wrap.dataset.barLabels || '[]');
            rvChartInstances[barKey] = new Chart(barCanvas, {
                type: 'bar',
                data: {
                    labels: JSON.parse(wrap.dataset.barShortLabels || '[]'),
                    datasets: [{
                        label: '% Correct (Difficulty)',
                        data: JSON.parse(wrap.dataset.barValues || '[]'),
                        backgroundColor: JSON.parse(wrap.dataset.barColors || '[]'),
                        borderRadius: 4,
                        maxBarThickness: 30,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function (items) {
                                    return fullLabels[items[0].dataIndex] || '';
                                },
                                label: function (ctx) {
                                    return ctx.parsed.y + '% correct';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { maxRotation: 45, minRotation: 0 }
                        },
                        y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
                    }
                }
            });
        } else if (barCanvas && rvChartInstances[barKey]) {
            rvChartInstances[barKey].resize();
        }

        if (distCanvas && rvChartInstances[distKey]) {
            rvChartInstances[distKey].resize();
        }
    }
</script>
@endsection