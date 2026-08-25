@php
    // I-map ang program value (galing sa users table) papuntang tamang layout
    $programLayouts = [
        'acc'    => 'layouts.appAcc',
        'psych'  => 'layouts.appPsych',
        'educ'   => 'layouts.appEduc',
    ];

    $userProgram = strtolower(auth()->user()->program);
    $activeLayout = $programLayouts[$userProgram] ?? 'layouts.appAcc'; // fallback kung walang match
@endphp

@extends($activeLayout)

@section('content')
<div class="rv-content-container" style="padding: 20px;">
    <div class="rv-page-header">
        <h2 class="rv-page-title" style="color: #245E55; font-weight: bold;">Available Mock Boards</h2>
        <p class="rv-page-subtitle" style="color: #64748b;">Program: {{ strtoupper(auth()->user()->program) }}</p>
    </div>

    <div class="mock-boards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">
        @forelse($availableBoards as $board)
            @php
                // 1. Check kung umiiral ba talaga ang bawat phase sa BOARD (hindi lang sa attempts)
                $hasPreTestPhase = $board->phases->contains('phase_type', 'pre_test');
                $hasPreBoardPhase = $board->phases->contains('phase_type', 'pre_boards');

                // 2. Identify existing attempts for this specific student
                $preTest = $board->attempts->firstWhere('phase_type', 'pre_test');
                $preBoard = $board->attempts->firstWhere('phase_type', 'pre_boards');

                // 3. Overall completion status (base sa mga phase na umiiral lang)
                $isFullyComplete = (!$hasPreTestPhase || $preTest) && (!$hasPreBoardPhase || $preBoard);
            @endphp

            <div class="rv-card" style="padding: 24px; border-radius: 12px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between; min-height: 320px; transition: transform 0.2s;">
                
                <div class="card-top">
                    <h3 style="margin-bottom: 20px; color: #1a202c; font-size: 1.3rem; font-weight: 800; border-left: 4px solid #245E55; padding-left: 12px;">
                        {{ $board->title }}
                    </h3>

                    <div class="phase-container" style="margin-bottom: 25px;">
                        @if($hasPreTestPhase && !$preTest)
                            {{-- CASE 1: May Pre-Test phase, hindi pa kinukuha --}}
                            <div style="padding: 18px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 800; color: #166534; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Phase 1: Pre-Test</span>
                                    <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Pending</span>
                                </div>
                                <p style="margin: 10px 0 0 0; font-size: 0.85rem; color: #3f6212; line-height: 1.4;">Complete this assessment to unlock your performance analytics.</p>
                            </div>

                        @elseif($hasPreBoardPhase && !$preBoard)
                            {{-- CASE 2: May Pre-Boards phase, hindi pa kinukuha --}}
                            <div style="padding: 18px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 800; color: #9a3412; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Phase: Pre-Board</span>
                                    <span style="font-weight: 900; font-size: 1.1rem; color: #f97316;">Unlocked</span>
                                </div>
                                @if($preTest)
                                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #fed7aa; font-size: 0.85rem; color: #7c2d12; display: flex; justify-content: space-between;">
                                        <span>Pre-Test Score:</span>
                                        <strong>{{ $preTest->percentage }}%</strong>
                                    </div>
                                @endif
                            </div>

                        @elseif($isFullyComplete)
                            {{-- CASE 3: Tapos na ang lahat ng available phases --}}
                            <div style="padding: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 800; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Final Assessment</span>
                                    <span style="font-weight: 900; font-size: 1.1rem; color: #10b981;">
                                        {{ $preBoard->percentage ?? $preTest->percentage ?? 0 }}%
                                    </span>
                                </div>
                                <p style="margin: 10px 0 0 0; font-size: 0.85rem; color: #64748b;">Board completion reached. View your results below.</p>
                            </div>

                        @else
                            {{-- CASE 4: Walang available na phase (bagong board, hindi pa naka-build) --}}
                            <div style="padding: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                                <span style="font-weight: 800; color: #94a3b8; font-size: 0.85rem; text-transform: uppercase;">No assessment phase available yet.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-actions">
                    @if($hasPreTestPhase && !$preTest)
                        {{-- START ACTION --}}
                        <a href="{{ route('student.mock-boards.take', [$board->id, 'pre_test']) }}" 
                           style="display: block; text-align: center; background: #245E55; color: white; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: 700; transition: background 0.2s;"
                           onmouseover="this.style.background='#1b4740'" onmouseout="this.style.background='#245E55'">
                            START PRE-TEST ASSESSMENT
                        </a>
                    @elseif($hasPreBoardPhase && !$preBoard)
                        {{-- PRE-BOARD AVAILABLE, HINDI PA KINUKUHA --}}
                        <div style="display: flex; gap: 12px;">
                            @if($preTest)
                                <a href="{{ route('student.mock-boards.results', $board->id) }}" 
                                   style="flex: 1; text-align: center; border: 2px solid #245E55; color: #245E55; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 700; transition: background 0.2s;"
                                   onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                                    REVIEWS
                                </a>
                            @endif
                            <a href="{{ route('student.mock-boards.take', [$board->id, 'pre_boards']) }}" 
                               style="flex: 1; text-align: center; background: #245E55; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 700; transition: background 0.2s;"
                               onmouseover="this.style.background='#1b4740'" onmouseout="this.style.background='#245E55'">
                                PRE-BOARD
                            </a>
                        </div>
                    @elseif($isFullyComplete)
                        {{-- TAPOS NA LAHAT — dito nawawala dati yung REVIEWS button --}}
                        <a href="{{ route('student.mock-boards.results', $board->id) }}" 
                           style="display: block; text-align: center; border: 2px solid #245E55; color: #245E55; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: 700; transition: background 0.2s;"
                           onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                            VIEW RESULTS
                        </a>
                    @else
                        {{-- WALANG PHASE PA, WALANG BUTTON --}}
                        <div style="text-align: center; color: #94a3b8; padding: 14px; font-size: 0.9rem; background: #f8fafc; border-radius: 8px;">
                            Not yet available.
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column: 1/-1; text-align: center; padding: 100px 20px; background: white; border-radius: 12px; border: 2px dashed #cbd5e0;">
                <div style="font-size: 3rem; color: #cbd5e0; margin-bottom: 10px;">📋</div>
                <h3 style="color: #475569; margin-bottom: 5px;">No Boards Available</h3>
                <p style="color: #94a3b8; font-size: 1rem;">Check back later for newly assigned mock boards for {{ strtoupper(auth()->user()->program) }}.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection