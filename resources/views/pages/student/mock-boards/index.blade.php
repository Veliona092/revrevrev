@php
    // I-map ang program value (galing sa users table) papuntang tamang layout
    $programLayouts = [
        'acc'    => 'layouts.appAcc',
        'psych'  => 'layouts.appPsych',
        'educ'   => 'layouts.appEduc',
    ];

    $userProgram = strtolower(auth()->user()->program ?? '');
    $activeLayout = $programLayouts[$userProgram] ?? 'layouts.appAcc';
@endphp

@extends($activeLayout)

@section('content')
<div class="rv-content-container" style="padding: 20px;">
    <div class="rv-page-header">
        <h2 class="rv-page-title" style="color: #245E55; font-weight: bold;">Available Mock Boards</h2>
        <p class="rv-page-subtitle" style="color: #64748b;">Program: {{ strtoupper(auth()->user()->program ?? 'ALL') }}</p>
    </div>

    <div class="mock-boards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; margin-top: 20px;">
        @forelse($availableBoards as $board)
            @php
                // Map student attempts by phase ID for exact per-phase matching
                $attemptsByPhaseId = $board->attempts->keyBy('mock_board_phase_id');

                $preTestPhase = $board->phases->firstWhere('phase_type', 'pre_test');
                $preTestAttempt = $preTestPhase ? ($attemptsByPhaseId->get($preTestPhase->id) ?? $board->attempts->firstWhere('phase_type', 'pre_test')) : null;
                $isPreTestDone = !$preTestPhase || ($preTestAttempt !== null);

                $postTestPhases = $board->phases->where('phase_type', 'pre_boards')->sortBy('sequence_number')->values();
                $totalPhasesCount = $board->phases->count();

                // Check completion status for each phase
                $completedPhasesCount = $board->phases->filter(function ($phase) use ($attemptsByPhaseId, $board) {
                    return $attemptsByPhaseId->has($phase->id) || $board->attempts->where('phase_type', $phase->phase_type)->isNotEmpty();
                })->count();

                $isFullyComplete = ($totalPhasesCount > 0) && ($completedPhasesCount >= $totalPhasesCount);
                $hasAnyAttempt = $board->attempts->isNotEmpty();

                $pendingPostTests = $postTestPhases->filter(function ($phase) use ($attemptsByPhaseId) {
                    return !$attemptsByPhaseId->has($phase->id);
                })->values();

                $firstPendingPostTest = $pendingPostTests->first();

                $phaseModule = $preTestPhase?->module ?? $board->phases->first()?->module;
                $maxAttempts = $phaseModule?->max_attempts ?? 1;
            @endphp

            <div class="rv-card" style="padding: 24px; border-radius: 12px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between; min-height: 340px; transition: transform 0.2s;">
                
                <div class="card-top">
                    <h3 style="margin-bottom: 8px; color: #1a202c; font-size: 1.25rem; font-weight: 800; border-left: 4px solid #245E55; padding-left: 12px;">
                        {{ $board->title }}
                    </h3>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; padding-left: 16px; font-size: 0.85rem; color: #64748b;">
                        <span><i class="fas fa-bullseye" style="color: #245E55;"></i> Passing: <strong>{{ $board->passing_percentage ?? 75 }}%</strong></span>
                        <span><i class="fas fa-redo" style="color: #245E55;"></i> Max Attempts: <strong>{{ $maxAttempts }}</strong></span>
                        @if($board->review_period_start && $board->review_period_end)
                            <span><i class="fas fa-calendar-alt" style="color: #245E55;"></i> {{ $board->review_period_start->format('M d') }} - {{ $board->review_period_end->format('M d, Y') }}</span>
                        @endif
                    </div>

                    {{-- PHASES BREAKDOWN LIST --}}
                    <div class="phase-container" style="margin-bottom: 20px; display: flex; flex-direction: column; gap: 10px;">
                        @if($board->phases->isEmpty())
                            <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center;">
                                <span style="font-weight: 600; color: #94a3b8; font-size: 0.85rem;">No assessment phases available yet.</span>
                            </div>
                        @else
                            {{-- 1. PRE-TEST PHASE (if configured) --}}
                            @if($preTestPhase)
                                <div style="padding: 12px 14px; background: {{ $preTestAttempt ? '#f8fafc' : '#f0fdf4' }}; border: 1px solid {{ $preTestAttempt ? '#e2e8f0' : '#dcfce7' }}; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <span style="font-weight: 700; color: #1e293b; font-size: 0.88rem;">Phase 1: Pre-Test</span>
                                        @if(!$preTestAttempt)
                                            <p style="margin: 3px 0 0 0; font-size: 0.78rem; color: #166534;">Required before taking post-tests.</p>
                                        @endif
                                    </div>
                                    <div>
                                        @if($preTestAttempt)
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 700; background: {{ $preTestAttempt->passed ? '#dcfce7' : '#fee2e2' }}; color: {{ $preTestAttempt->passed ? '#15803d' : '#b91c1c' }};">
                                                {{ $preTestAttempt->percentage }}% ({{ $preTestAttempt->passed ? 'Passed' : 'Failed' }})
                                            </span>
                                        @else
                                            <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 700;">Pending</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- 2. POST-TEST PHASES (Pre-Boards, Post-Test 1, Post-Test 2, ...) --}}
                            @foreach($postTestPhases as $postPhase)
                                @php
                                    $postAttempt = $attemptsByPhaseId->get($postPhase->id);
                                    $phaseLabel = $postPhase->phase_label;
                                    $isLocked = !$isPreTestDone;
                                @endphp
                                <div style="padding: 12px 14px; background: {{ $postAttempt ? '#f8fafc' : ($isLocked ? '#f8fafc' : '#fff7ed') }}; border: 1px solid {{ $postAttempt ? '#e2e8f0' : ($isLocked ? '#e2e8f0' : '#ffedd5') }}; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <span style="font-weight: 700; color: {{ $isLocked ? '#94a3b8' : '#1e293b' }}; font-size: 0.88rem;">
                                            {{ $phaseLabel }}
                                        </span>
                                        @if($isLocked)
                                            <p style="margin: 3px 0 0 0; font-size: 0.78rem; color: #94a3b8;"><i class="fas fa-lock" style="font-size: 0.75rem;"></i> Locked (Complete Pre-Test first)</p>
                                        @elseif(!$postAttempt)
                                            <p style="margin: 3px 0 0 0; font-size: 0.78rem; color: #9a3412;">Unlocked &bull; Ready to take</p>
                                        @endif
                                    </div>
                                    <div>
                                        @if($postAttempt)
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 700; background: {{ $postAttempt->passed ? '#dcfce7' : '#fee2e2' }}; color: {{ $postAttempt->passed ? '#15803d' : '#b91c1c' }};">
                                                {{ $postAttempt->percentage }}% ({{ $postAttempt->passed ? 'Passed' : 'Failed' }})
                                            </span>
                                        @elseif($isLocked)
                                            <span style="background: #f1f5f9; color: #94a3b8; padding: 3px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 600;">Locked</span>
                                        @else
                                            <span style="background: #ffedd5; color: #9a3412; padding: 3px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 700;">Ready</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- CARD ACTION BUTTONS --}}
                <div class="card-actions" style="margin-top: 12px;">
                    @if($preTestPhase && !$preTestAttempt)
                        {{-- MUST START PRE-TEST FIRST --}}
                        <a href="{{ route('student.mock-boards.take', [$board->id, $preTestPhase->id]) }}" 
                           style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-align: center; background: #245E55; color: white; padding: 11px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
                           onmouseover="this.style.background='#1b4740'" onmouseout="this.style.background='#245E55'">
                            <i class="fas fa-play" style="font-size: 0.85rem;"></i> Start Pre-Test
                        </a>

                    @elseif($pendingPostTests->isNotEmpty() && $isPreTestDone)
                        {{-- POST-TESTS AVAILABLE TO TAKE --}}
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                @if($hasAnyAttempt)
                                    <a href="{{ route('student.mock-boards.results', $board->id) }}" 
                                       style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-align: center; border: 1.5px solid #245E55; color: #245E55; padding: 10px 12px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.86rem; transition: all 0.2s; white-space: nowrap;"
                                       onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                                        <i class="fas fa-chart-line" style="font-size: 0.82rem;"></i> Reviews
                                    </a>
                                @endif

                                @if($firstPendingPostTest)
                                    <a href="{{ route('student.mock-boards.take', [$board->id, $firstPendingPostTest->id]) }}" 
                                       style="flex: 1.4; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-align: center; background: #245E55; color: white; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.86rem; transition: background 0.2s; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
                                       onmouseover="this.style.background='#1b4740'" onmouseout="this.style.background='#245E55'">
                                        <i class="fas fa-pen" style="font-size: 0.82rem;"></i> Take {{ $firstPendingPostTest->phase_label }}
                                    </a>
                                @endif
                            </div>

                            @if($pendingPostTests->count() > 1)
                                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 2px;">
                                    @foreach($pendingPostTests->slice(1) as $otherPending)
                                        <a href="{{ route('student.mock-boards.take', [$board->id, $otherPending->id]) }}" 
                                           style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 4px; text-align: center; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; padding: 7px 10px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600; transition: background 0.15s;"
                                           onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">
                                            Take {{ $otherPending->phase_label }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    @elseif($isFullyComplete)
                        {{-- ALL PHASES COMPLETED --}}
                        <a href="{{ route('student.mock-boards.results', $board->id) }}" 
                           style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-align: center; border: 1.5px solid #245E55; color: #245E55; padding: 11px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
                           onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                            <i class="fas fa-poll-h" style="font-size: 0.85rem;"></i> View Performance Results
                        </a>

                    @else
                        <div style="text-align: center; color: #94a3b8; padding: 12px; font-size: 0.88rem; background: #f8fafc; border-radius: 8px;">
                            Not yet available.
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column: 1/-1; text-align: center; padding: 80px 20px; background: white; border-radius: 12px; border: 2px dashed #cbd5e0;">
                <div style="font-size: 3rem; color: #cbd5e0; margin-bottom: 10px;">📋</div>
                <h3 style="color: #475569; margin-bottom: 5px;">No Mock Boards Available</h3>
                <p style="color: #94a3b8; font-size: 1rem;">Check back later for newly assigned mock boards for {{ strtoupper(auth()->user()->program ?? 'your program') }}.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection