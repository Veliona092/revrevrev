@extends($layout)

@section('title', 'Progress Tracker')
@section('page-heading', 'Progress Tracker')

@section('content')
<style>
    .pt-wrap { display: flex; flex-direction: column; gap: 24px; }

    .pt-stat-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .pt-stat {
        background: #fff;
        border: 1px solid #e8e2d8;
        border-radius: 14px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .pt-stat-icon {
        width: 46px; height: 46px;
        border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .pt-stat-label {
        font-size: 16px;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #9a9185;
        margin: 0;
    }

    .pt-stat-value {
        font-size: 30px;
        font-weight: 500;
        color: #1f2937;
        margin: 2px 0 0;
        line-height: 1;
    }

    .pt-stat-sub {
        font-size: 15px;
        color: #b0a899;
        margin: 3px 0 0;
    }

    .pt-classes-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }

    .pt-class-card {
        background: #fff;
        border: 1px solid #e8e2d8;
        border-radius: 14px;
        padding: 18px 20px;
    }

    .pt-class-name {
        font-size: 16px;
        font-weight: 500;
        color: #1f2937;
        margin: 0 0 2px;
    }

    .pt-class-teacher {
        font-size: 15px;
        color: #9a9185;
        margin: 0 0 14px;
    }

    .pt-bar-track {
        background: #f0ede8;
        border-radius: 99px;
        height: 8px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .pt-bar-fill {
        height: 100%;
        border-radius: 99px;
        transition: width 0.4s ease;
    }

    .pt-bar-green  { background: #1d9e75; }
    .pt-bar-amber  { background: #ef9f27; }
    .pt-bar-red    { background: #e24b4a; }

    .pt-class-meta {
        display: flex;
        gap: 16px;
        font-size: 14px;
        color: #9a9185;
        margin-bottom: 12px;
    }

    .pt-class-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .pt-pct {
        font-size: 16px;
        font-weight: 500;
    }

    .pt-continue-btn {
        background: #f0ede8;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 15px;
        font-weight: 500;
        color: #3f3830;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s;
    }

    .pt-continue-btn:hover { background: #e6e0d7; color: #1f2937; }

    .pt-bottom-row {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 18px;
        align-items: start;
    }

    .pt-card {
        background: #fff;
        border: 1px solid #e8e2d8;
        border-radius: 14px;
        overflow: hidden;
    }

    .pt-card-head {
        padding: 15px 18px;
        border-bottom: 1px solid #f0ede8;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pt-card-title {
        font-size: 16px;
        font-weight: 500;
        color: #1f2937;
        margin: 0;
    }

    .pt-card-body { padding: 16px 18px; }

    .pt-timeline { display: flex; flex-direction: column; gap: 12px; }

    .pt-tl-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f7f4ed;
    }

    .pt-tl-item:last-child { border-bottom: none; padding-bottom: 0; }

    .pt-tl-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 5px;
    }

    .dot-green  { background: #1d9e75; }
    .dot-amber  { background: #ef9f27; }
    .dot-red    { background: #e24b4a; }
    .dot-purple { background: #7f77dd; }

    .pt-type-badge {
        display: inline-block;
        font-size: 13px;
        font-weight: 500;
        padding: 2px 7px;
        border-radius: 4px;
        margin-right: 4px;
        letter-spacing: 0.04em;
        vertical-align: middle;
    }

    .pt-type-quiz { background: #e9f8ef; color: #157a54; }
    .pt-type-assessment { background: #ede9f8; color: #5a52b6; }
    .pt-type-module { background: #f0ede8; color: #6b5f4a; }

    .pt-class-type-row {
        display: flex;
        gap: 14px;
        font-size: 15px;
        color: #9a9185;
        margin-bottom: 8px;
    }

    .pt-class-type-item { display: flex; align-items: center; gap: 4px; }

    .pt-tl-label { font-size: 16px; color: #2f2a22; margin: 0; }
    .pt-tl-time  { font-size: 15px; color: #a09583; margin: 3px 0 0; }

    .pt-weak-card {
        background: #fff;
        border: 1px solid #e8e2d8;
        border-left: 4px solid #7f77dd;
        border-radius: 14px;
        overflow: hidden;
        height: fit-content;
    }

    .pt-weak-item {
        padding: 12px 18px;
        border-bottom: 1px solid #f0ede8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .pt-weak-item:last-child { border-bottom: none; }

    .pt-weak-name { font-size: 16px; color: #2f2a22; font-weight: 500; margin: 0; }
    .pt-weak-count {
        font-size: 16px;
        font-weight: 500;
        background: #ede9f8;
        color: #5a52b6;
        padding: 2px 8px;
        border-radius: 99px;
    }

    .pt-empty {
        text-align: center;
        padding: 36px;
        color: #c0b8ae;
        font-size: 16px;
    }

    .pt-pager {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
    }

    .pt-pager-info {
        font-size: 14px;
        color: #9a9185;
    }

    .pt-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 32px;
        min-width: 72px;
        padding: 0 12px;
        border-radius: 7px;
        border: 1px solid #e8e2d8;
        background: #fff;
        color: #3f3830;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
    }

    .pt-page-btn:hover {
        background: #f8f6f2;
        color: #1f2937;
    }

    .pt-page-btn.disabled {
        opacity: 0.45;
        pointer-events: none;
    }

    @media (max-width: 1100px) {
        .pt-stat-row { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 900px) {
        .pt-classes-grid { grid-template-columns: 1fr; }
        .pt-bottom-row { grid-template-columns: 1fr; }
    }

    @media (max-width: 540px) {
        .pt-stat-row { grid-template-columns: 1fr; }
    }
</style>

@php
    use App\Models\Module;
    use App\Models\ModuleProgress;
    use App\Models\QuizAttempt;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;

    $student = Auth::user();

    // Enrolled classes with all modules
    $enrolledClasses = $student->classes()
        ->with(['modules' => fn ($q) => $q->orderBy('order')])
        ->get();

    // All module IDs the student has access to, split by type
    $allModules = $enrolledClasses->flatMap(fn ($c) => $c->modules);
    $docModuleIds   = $allModules->where('is_quiz', false)->where('is_formal_assessment', false)->pluck('id');
    $quizModuleIds  = $allModules->where('is_quiz', true)->where('is_formal_assessment', false)->pluck('id');
    $assessModuleIds = $allModules->where('is_formal_assessment', true)->pluck('id');

    // Module progress for this student
    $progressRecords = ModuleProgress::query()
        ->where('user_id', $student->id)
        ->get()
        ->keyBy('module_id');

    // Stat 1: Document/content modules completed
    $totalDocModules      = $docModuleIds->count();
    $completedDocModules  = $progressRecords->whereIn('module_id', $docModuleIds->toArray())
                                            ->where('completed', true)->count();

    // Stat 2: Pre-assessments (practice quizzes - ungraded, avg only)
    $practiceAttempts      = QuizAttempt::query()
        ->where('user_id', $student->id)
        ->whereIn('module_id', $quizModuleIds->toArray())
        ->get();
    $totalPracticeAttempts = $practiceAttempts->count();
    $preAssessAvg          = $totalPracticeAttempts > 0 ? round($practiceAttempts->avg('score') ?? 0) : null;
    $preAssessAvgColor     = $preAssessAvg === null ? '#9a9185' : ($preAssessAvg >= 75 ? '#1d9e75' : ($preAssessAvg >= 50 ? '#ef9f27' : '#e24b4a'));

    // Stat 3: Formal assessments (graded)
    $assessAttempts       = QuizAttempt::query()
        ->where('user_id', $student->id)
        ->whereIn('module_id', $assessModuleIds->toArray())
        ->get();
    $totalAssessAttempts  = $assessAttempts->count();
    $passedAssessAttempts = $assessAttempts->where('passed', true)->count();
    $assessAvg            = $totalAssessAttempts > 0 ? round($assessAttempts->avg('score') ?? 0) : null;

    // Stat 4: Assessment avg only (pre-assessments are ungraded so excluded)
    $assessAvgColor = $assessAvg === null ? '#9a9185' : ($assessAvg >= 75 ? '#1d9e75' : ($assessAvg >= 50 ? '#ef9f27' : '#e24b4a'));

    // Per-class cards
    $classCards = $enrolledClasses->map(function ($class) use ($progressRecords, $student) {
        $modules     = $class->modules;
        $docMods     = $modules->where('is_quiz', false)->where('is_formal_assessment', false);
        $quizMods    = $modules->where('is_quiz', true)->where('is_formal_assessment', false);
        $assessMods  = $modules->where('is_formal_assessment', true);

        $totalDocs  = $docMods->count();
        $doneDocs   = $docMods->filter(fn ($m) => ($progressRecords[$m->id]?->completed ?? false))->count();
        $pct        = $totalDocs > 0 ? round(($doneDocs / $totalDocs) * 100) : 0;
        $barClass   = $pct >= 75 ? 'pt-bar-green' : ($pct >= 40 ? 'pt-bar-amber' : 'pt-bar-red');

        // Pre-assessment avg per class (ungraded - just show avg score)
        $classQuizIds   = $quizMods->pluck('id')->toArray();
        $classAssessIds = $assessMods->pluck('id')->toArray();

        $classPreAssessAttempts = QuizAttempt::query()
            ->where('user_id', $student->id)
            ->whereIn('module_id', $classQuizIds)
            ->get();
        $classPreAssessAvg = $classPreAssessAttempts->count() > 0
            ? round($classPreAssessAttempts->avg('score') ?? 0)
            : null;

        $passedAssessHere = QuizAttempt::query()
            ->where('user_id', $student->id)
            ->whereIn('module_id', $classAssessIds)
            ->where('passed', true)
            ->count();
        $classAssessAvg = ($classAssessAttempts = QuizAttempt::query()
            ->where('user_id', $student->id)
            ->whereIn('module_id', $classAssessIds)
            ->get())->count() > 0
            ? round($classAssessAttempts->avg('score') ?? 0)
            : null;

        $nextModule = $modules->first(fn ($m) => ! ($progressRecords[$m->id]?->completed ?? false));

        return (object) [
            'name'              => $class->name ?? 'Unnamed Class',
            'teacher'           => $class->teacher->name ?? 'Instructor',
            'totalDocs'         => $totalDocs,
            'doneDocs'          => $doneDocs,
            'pct'               => $pct,
            'barClass'          => $barClass,
            'totalPreAssess'    => count($classQuizIds),
            'preAssessAvg'      => $classPreAssessAvg,
            'totalAssess'       => count($classAssessIds),
            'passedAssess'      => $passedAssessHere,
            'assessAvg'         => $classAssessAvg,
            'nextModuleClassId' => $class->id,
            'hasNext'           => $nextModule !== null,
        ];
    });

    // Pagination for class cards (4 per page)
    $classPerPage    = 4;
    $classPage       = max((int) request('class_page', 1), 1);
    $classTotal      = $classCards->count();
    $classTotalPages = max((int) ceil($classTotal / $classPerPage), 1);
    $classPage       = min($classPage, $classTotalPages);
    $classPageItems  = $classCards->forPage($classPage, $classPerPage)->values();

    // Activity timeline
    $completedProgressRows = ModuleProgress::query()
        ->where('user_id', $student->id)
        ->where('completed', true)
        ->with('module.class')
        ->latest('updated_at')
        ->limit(20)
        ->get()
        ->map(fn ($p) => (object) [
            'dot'   => 'dot-green',
            'type'  => 'module',
            'label' => 'Completed '.($p->module->title ?? 'a module').' · '.($p->module->class->name ?? ''),
            'time'  => $p->updated_at?->diffForHumans() ?? '',
            'ts'    => $p->updated_at,
        ]);

    $recentAttempts = QuizAttempt::query()
        ->where('user_id', $student->id)
        ->with('module.class')
        ->latest('created_at')
        ->limit(20)
        ->get()
        ->map(fn ($a) => (object) [
            'dot'   => ($a->passed ?? ($a->score ?? 0) >= 60) ? (($a->module->is_formal_assessment ?? false) ? 'dot-purple' : 'dot-green') : 'dot-red',
            'type'  => ($a->module->is_formal_assessment ?? false) ? 'assessment' : 'pre-assessment',
            'label' => (($a->passed ?? ($a->score ?? 0) >= 60) ? 'Passed' : 'Attempted')
                .' '.($a->module->is_formal_assessment ?? false ? 'assessment' : 'pre-assessment')
                .' on '.($a->module->title ?? 'a module')
                .' with '.round($a->score ?? 0).'%'
                .' · '.($a->module->class->name ?? ''),
            'time'  => $a->created_at?->diffForHumans() ?? '',
            'ts'    => $a->created_at,
        ]);

    $timelineItems = $completedProgressRows->concat($recentAttempts)
        ->sortByDesc(fn ($item) => $item->ts)
        ->take(20)
        ->values();

    $activityPerPage    = 5;
    $activityPage       = max((int) request('activity_page', 1), 1);
    $activityTotal      = $timelineItems->count();
    $activityTotalPages = max((int) ceil($activityTotal / $activityPerPage), 1);
    $activityPage       = min($activityPage, $activityTotalPages);
    $timelinePageItems  = $timelineItems->forPage($activityPage, $activityPerPage)->values();

    // Weak areas - practice quizzes only, joined through quiz_attempts
    $weakAreas = DB::table('quiz_answers')
        ->join('quiz_attempts', 'quiz_answers.attempt_id', '=', 'quiz_attempts.id')
        ->join('modules', 'quiz_attempts.module_id', '=', 'modules.id')
        ->where('quiz_attempts.user_id', $student->id)
        ->where('quiz_answers.is_correct', false)
        ->where('modules.is_formal_assessment', false)
        ->selectRaw('quiz_attempts.module_id, COUNT(*) as wrong_count')
        ->groupBy('quiz_attempts.module_id')
        ->orderByDesc('wrong_count')
        ->get()
        ->map(fn ($row) => (object) [
            'module'      => Module::with('class')->find($row->module_id),
            'wrong_count' => $row->wrong_count,
        ]);

    $weakPerPage    = 5;
    $weakPage       = max((int) request('weak_page', 1), 1);
    $weakTotal      = $weakAreas->count();
    $weakTotalPages = max((int) ceil($weakTotal / $weakPerPage), 1);
    $weakPage       = min($weakPage, $weakTotalPages);
    $weakPageItems  = $weakAreas->forPage($weakPage, $weakPerPage)->values();
@endphp

<div class="pt-wrap">
    {{-- Stat row --}}
    <div class="pt-stat-row">
        <div class="pt-stat">
            <div class="pt-stat-icon" style="background:#e9f8ef;color:#1d9e75;">
                <i class="fas fa-book-open"></i>
            </div>
            <div>
                <p class="pt-stat-label">Modules Read</p>
                <p class="pt-stat-value">{{ $completedDocModules }}</p>
                <p class="pt-stat-sub">of {{ $totalDocModules }} materials</p>
            </div>
        </div>

        <div class="pt-stat">
            <div class="pt-stat-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-tasks"></i>
            </div>
            <div>
                <p class="pt-stat-label">Pre-Assessment Avg</p>
                <p class="pt-stat-value" style="color:{{ $preAssessAvgColor }};">
                    {{ $preAssessAvg !== null ? $preAssessAvg.'%' : '-' }}
                </p>
                <p class="pt-stat-sub">{{ $totalPracticeAttempts }} attempt{{ $totalPracticeAttempts !== 1 ? 's' : '' }} · ungraded</p>
            </div>
        </div>

        <div class="pt-stat">
            <div class="pt-stat-icon" style="background:#ede9f8;color:#7f77dd;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div>
                <p class="pt-stat-label">Assessments Passed</p>
                <p class="pt-stat-value">{{ $passedAssessAttempts }}</p>
                <p class="pt-stat-sub">of {{ $totalAssessAttempts }} attempted
                    @if($assessAvg !== null)
                        · avg {{ $assessAvg }}%
                    @endif
                </p>
            </div>
        </div>

        <div class="pt-stat">
            <div class="pt-stat-icon" style="background:#e0f2fe;color:#0369a1;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <p class="pt-stat-label">Assessment Avg</p>
                <p class="pt-stat-value" style="color:{{ $assessAvgColor }};">
                    {{ $assessAvg !== null ? $assessAvg.'%' : '-' }}
                </p>
                <p class="pt-stat-sub">{{ $totalAssessAttempts }} assessment{{ $totalAssessAttempts !== 1 ? 's' : '' }} taken</p>
            </div>
        </div>
    </div>

    {{-- Per-class cards --}}
    @if($classPageItems->isNotEmpty())
    <div>
        <p style="font-size: 15px;font-weight:500;color:#555;margin:0 0 10px;">Your Classes</p>
        <div class="pt-classes-grid">
            @foreach($classPageItems as $card)
            <div class="pt-class-card">
                <p class="pt-class-name">{{ $card->name }}</p>
                <p class="pt-class-teacher">{{ $card->teacher }}</p>
                {{-- Reading/doc progress bar --}}
                @if($card->totalDocs > 0)
                <div class="pt-bar-track">
                    <div class="pt-bar-fill {{ $card->barClass }}" style="width:{{ $card->pct }}%;"></div>
                </div>
                @endif
                {{-- Split type stats --}}
                <div class="pt-class-type-row">
                    <span class="pt-class-type-item">
                        <span class="pt-type-badge pt-type-module">Docs</span>
                        {{ $card->doneDocs }}/{{ $card->totalDocs }}
                    </span>
                    <span class="pt-class-type-item">
                        <span class="pt-type-badge pt-type-quiz">Pre-Assess</span>
                        {{ $card->preAssessAvg !== null ? 'avg '.$card->preAssessAvg.'%' : 'no attempts' }}
                        ({{ $card->totalPreAssess }})
                    </span>
                    <span class="pt-class-type-item">
                        <span class="pt-type-badge pt-type-assessment">Assessment</span>
                        {{ $card->passedAssess }}/{{ $card->totalAssess }} passed
                        @if($card->assessAvg !== null)
                            · avg {{ $card->assessAvg }}%
                        @endif
                    </span>
                </div>
                <div class="pt-class-footer">
                    <span class="pt-pct" style="color:{{ $card->pct >= 75 ? '#1d9e75' : ($card->pct >= 40 ? '#ef9f27' : '#e24b4a') }};">
                        {{ $card->pct }}% content read
                    </span>
                    @if($card->hasNext)
                    <a href="{{ route('student.modules', $card->nextModuleClassId) }}" class="pt-continue-btn">
                        Continue →
                    </a>
                    @else
                    <span style="font-size: 15px;color:#1d9e75;font-weight:500;">All done ✓</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Class pagination --}}
        @if($classTotalPages > 1)
            <div class="pt-pager" style="margin-top: 18px;">
                <span class="pt-pager-info">Page {{ $classPage }} / {{ $classTotalPages }}</span>
                @if($classPage > 1)
                    <a class="pt-page-btn" href="{{ request()->fullUrlWithQuery(['class_page' => $classPage - 1]) }}">Previous</a>
                @else
                    <span class="pt-page-btn disabled">Previous</span>
                @endif

                @if($classPage < $classTotalPages)
                    <a class="pt-page-btn" href="{{ request()->fullUrlWithQuery(['class_page' => $classPage + 1]) }}">Next</a>
                @else
                    <span class="pt-page-btn disabled">Next</span>
                @endif
            </div>
        @endif
    </div>
    @else
    <div class="pt-card">
        <div class="pt-empty"><i class="fas fa-book-open" style="font-size:28px;display:block;margin-bottom:8px;"></i>You're not enrolled in any classes yet.</div>
    </div>
    @endif

    {{-- Bottom row: timeline + weak areas --}}
    <div class="pt-bottom-row">
        <div class="pt-card">
            <div class="pt-card-head">
                <i class="fas fa-stream" style="color:#9a9185;font-size: 15px;"></i>
                <h3 class="pt-card-title">Recent Activity</h3>
            </div>
            <div class="pt-card-body">
                @if($timelineItems->isEmpty())
                    <div class="pt-empty">No activity yet. Start a module to see your progress here.</div>
                @else
                    <div class="pt-timeline">
                        @foreach($timelinePageItems as $item)
                        <div class="pt-tl-item">
                            <div class="pt-tl-dot {{ $item->dot }}"></div>
                            <div>
                                <p class="pt-tl-label">
                                    @if($item->type === 'pre-assessment')
                                        <span class="pt-type-badge pt-type-quiz">Pre-Assessment</span>
                                    @elseif($item->type === 'assessment')
                                        <span class="pt-type-badge pt-type-assessment">Assessment</span>
                                    @else
                                        <span class="pt-type-badge pt-type-module">Docs</span>
                                    @endif
                                    {{ $item->label }}
                                </p>
                                <p class="pt-tl-time">{{ $item->time }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($activityTotalPages > 1)
                        <div class="pt-pager">
                            <span class="pt-pager-info">Page {{ $activityPage }} / {{ $activityTotalPages }}</span>
                            @if($activityPage > 1)
                                <a class="pt-page-btn" href="{{ request()->fullUrlWithQuery(['activity_page' => $activityPage - 1]) }}">Previous</a>
                            @else
                                <span class="pt-page-btn disabled">Previous</span>
                            @endif

                            @if($activityPage < $activityTotalPages)
                                <a class="pt-page-btn" href="{{ request()->fullUrlWithQuery(['activity_page' => $activityPage + 1]) }}">Next</a>
                            @else
                                <span class="pt-page-btn disabled">Next</span>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="pt-weak-card">
            <div class="pt-card-head" style="border-bottom:1px solid #f0ede8;">
                <i class="fas fa-exclamation-circle" style="color:#7f77dd;font-size: 15px;"></i>
                <h3 class="pt-card-title">Weak Areas (Most Incorrect Answers)</h3>
            </div>
            <div>
                @if($weakAreas->isEmpty())
                    <div class="pt-empty">No weak areas identified yet. Keep answering quizzes to get topic-level insights.</div>
                @else
                    @foreach($weakPageItems as $weak)
                    <div class="pt-weak-item">
                        <p class="pt-weak-name">
                            {{ $weak->module->title ?? 'Topic not available' }}
                            · {{ $weak->module->class->name ?? 'Class not available' }}
                        </p>
                        <span class="pt-weak-count">
                            {{ $weak->wrong_count }} {{ (int) $weak->wrong_count === 1 ? 'incorrect answer' : 'incorrect answers' }}
                        </span>
                    </div>
                    @endforeach

                    @if($weakTotalPages > 1)
                        <div class="pt-card-body" style="padding-top:10px;">
                            <div class="pt-pager" style="margin-top:0;">
                                <span class="pt-pager-info">Page {{ $weakPage }} / {{ $weakTotalPages }}</span>
                                @if($weakPage > 1)
                                    <a class="pt-page-btn" href="{{ request()->fullUrlWithQuery(['weak_page' => $weakPage - 1]) }}">Previous</a>
                                @else
                                    <span class="pt-page-btn disabled">Previous</span>
                                @endif

                                @if($weakPage < $weakTotalPages)
                                    <a class="pt-page-btn" href="{{ request()->fullUrlWithQuery(['weak_page' => $weakPage + 1]) }}">Next</a>
                                @else
                                    <span class="pt-page-btn disabled">Next</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

