@extends('layouts.appTeach')

@section('title', 'My Mock Boards')

@section('page-heading')
    Mock Boards: <span style="color: #245E55;">{{ ucfirst($selectedProgram ?: 'My Program') }}</span>
@endsection

@section('header-actions')
    <button class="rv-btn rv-btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Create Mock Board
    </button>
@endsection

@section('content')
<div class="mock-boards-container">

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    @if(!isset($mockBoards) || $mockBoards->isEmpty())
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>No Mock Boards Yet</h3>
            <p>Create your first mock board for {{ $selectedProgram ?: 'your program' }}. It will be submitted for admin approval before students can see it.</p>
            <button class="rv-btn rv-btn-primary" onclick="openCreateModal()">Create Mock Board</button>
        </div>
    @else
        <div class="mock-boards-list">
            @foreach($mockBoards as $board)
                @php
                    $preTestPhase = $board->phases->firstWhere('phase_type', 'pre_test');
                    $preBoardsPhase = $board->phases->firstWhere('phase_type', 'pre_boards');
                @endphp
                <div class="mock-board-card">
                    <div class="board-header">
                        <div>
                            <h3>{{ $board->title ?? 'Untitled Mock Board' }}</h3>
                            <span class="board-program-label">Program: {{ ucfirst($board->program ?? 'N/A') }}</span>
                        </div>
                        <span class="status-badge status-{{ $board->status }}">
                            {{ ucfirst($board->status) }}
                        </span>
                    </div>

                    @if($board->status === 'rejected' && $board->rejection_reason)
                        <div class="rejection-note">
                            <i class="fas fa-exclamation-circle"></i>
                            <strong>Rejection reason:</strong> {{ $board->rejection_reason }}
                        </div>
                    @endif

                    <p class="board-description">{{ $board->description ?? 'No description provided.' }}</p>

                    <div class="board-meta">
                        @if($board->review_period_start && $board->review_period_end)
                            <span><i class="fas fa-calendar-alt"></i> {{ $board->review_period_start->format('M d') }} - {{ $board->review_period_end->format('M d, Y') }}</span>
                        @endif
                        <span><i class="fas fa-bullseye"></i> Passing: {{ $board->passing_percentage ?? 75 }}%</span>
                        <span><i class="fas fa-user-graduate"></i> {{ $board->attempts_count ?? 0 }} Students Participated</span>
                    </div>

                    <div class="board-actions">
                        <a href="{{ route('mock-boards.analysis', $board->id) }}" class="rv-btn rv-btn-secondary">
                            <i class="fas fa-chart-line"></i> Batch Analysis
                        </a>

                        @if($preTestPhase && $preTestPhase->module)
                            <a href="{{ route('quiz.create', $preTestPhase->module) }}" class="rv-btn rv-btn-primary-outline">
                                <i class="fas fa-edit"></i>
                                {{ $preTestPhase->module->quizQuestions->isNotEmpty() ? 'Edit Pre-Test' : 'Build Pre-Test' }}
                            </a>
                        @endif

                        @if($preBoardsPhase && $preBoardsPhase->module)
                            <a href="{{ route('quiz.create', $preBoardsPhase->module) }}" class="rv-btn rv-btn-primary-outline">
                                <i class="fas fa-pen-fancy"></i>
                                {{ $preBoardsPhase->module->quizQuestions->isNotEmpty() ? 'Edit Pre-Boards' : 'Build Pre-Boards' }}
                            </a>
                        @endif

                        @php
                            $boardAttempts = $board->phases->first()?->module?->max_attempts ?? 1;
                        @endphp
                        <button class="rv-btn rv-btn-secondary" onclick="editBoard(this)"
                            data-id="{{ $board->id }}"
                            data-title="{{ $board->title }}"
                            data-description="{{ $board->description ?? '' }}"
                            data-start="{{ optional($board->review_period_start)->toDateString() }}"
                            data-end="{{ optional($board->review_period_end)->toDateString() }}"
                            data-passing="{{ $board->passing_percentage }}"
                            data-attempts="{{ $boardAttempts }}">
                            <i class="fas fa-cog"></i> Settings
                        </button>

                        <form action="{{ route('mock-boards.destroy', $board) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Are you sure? This will delete all student attempts and statistics for this board.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rv-btn rv-btn-danger"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- CREATE MOCK BOARD MODAL --}}
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-layer-group" style="color:#245E55;"></i> Initialize Mock Board</h3>
            <span class="modal-close" onclick="closeCreateModal()">&times;</span>
        </div>

        <form action="{{ route('mock-boards.store') }}" method="POST">
            @csrf

            <div class="modal-form-group">
                <label class="modal-label">Mock Board Title</label>
                <input type="text" name="title" class="modal-input" placeholder="e.g., Comprehensive Accountancy Mock Exam" required>
            </div>

            <div class="modal-grid-2">
                <div class="modal-form-group">
                    <label class="modal-label">Passing Rate (%)</label>
                    <input type="number" name="passing_percentage" class="modal-input" min="0" max="100" value="75" required>
                </div>
                <div class="modal-form-group">
                    <label class="modal-label">Max Attempts for Students</label>
                    <input type="number" name="max_attempts" class="modal-input" min="1" max="20" value="1" placeholder="e.g. 1" required>
                </div>
            </div>

            <div class="modal-form-group">
                <label class="modal-label">Description (Optional)</label>
                <textarea name="description" class="modal-input" rows="2" placeholder="Brief instruction overview for student guidelines..."></textarea>
            </div>

            <div class="modal-grid-2">
                <div class="modal-form-group">
                    <label class="modal-label">Review Period Start</label>
                    <input type="date" name="review_period_start" class="modal-input" required>
                </div>
                <div class="modal-form-group">
                    <label class="modal-label">Review Period End</label>
                    <input type="date" name="review_period_end" class="modal-input" required>
                </div>
            </div>

            <div class="modal-form-group">
                <label class="modal-label">Initial Assessment Phase to Build</label>
                <div class="modal-radio-row">
                    <label><input type="radio" name="selected_phase" value="pre_test" checked onchange="toggleModalPhaseInputs()"> Pre-Test Quiz</label>
                    <label><input type="radio" name="selected_phase" value="pre_boards" onchange="toggleModalPhaseInputs()"> Pre-Boards Quiz</label>
                </div>
            </div>

            <div id="modal_pre_test_title_group" class="modal-form-group">
                <label class="modal-label">Custom Pre-Test Name (Optional)</label>
                <input type="text" name="pre_test_title" class="modal-input" placeholder="Defaults to: [Title] - Pre-Test">
            </div>

            <div id="modal_pre_boards_title_group" class="modal-form-group" style="display:none;">
                <label class="modal-label">Custom Pre-Boards Name (Optional)</label>
                <input type="text" name="pre_boards_title" class="modal-input" placeholder="Defaults to: [Title] - Pre-Boards">
            </div>

            <div class="modal-form-group">
                <label class="modal-label">Time Limit (Minutes)</label>
                <input type="number" name="time_limit" class="modal-input" min="0" value="0" placeholder="0 for unlimited">
            </div>

            <p class="modal-info-note">
                <i class="fas fa-info-circle"></i> This Mock Board will be submitted for admin approval before it becomes visible to students.
            </p>

            <div class="modal-footer">
                <button type="button" class="rv-btn rv-btn-secondary" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="rv-btn rv-btn-primary" style="background:#245E55;">
                    <i class="fas fa-rocket"></i> Initialize & Build Quiz
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Settings</h3>
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-form-group"><label class="modal-label">Title</label><input type="text" id="editTitle" name="title" class="modal-input" required></div>
            <div class="modal-form-group"><label class="modal-label">Description</label><textarea id="editDescription" name="description" class="modal-input"></textarea></div>
            <div class="modal-form-group"><label class="modal-label">Start Date</label><input type="date" id="editStart" name="review_period_start" class="modal-input" required></div>
            <div class="modal-form-group"><label class="modal-label">End Date</label><input type="date" id="editEnd" name="review_period_end" class="modal-input" required></div>
            <div class="modal-grid-2">
                <div class="modal-form-group"><label class="modal-label">Passing Rate (%)</label><input type="number" id="editPassing" name="passing_percentage" class="modal-input" required></div>
                <div class="modal-form-group"><label class="modal-label">Max Attempts for Students</label><input type="number" id="editMaxAttempts" name="max_attempts" class="modal-input" min="1" max="20" required></div>
            </div>
            <p class="modal-info-note">
                <i class="fas fa-info-circle"></i> Saving changes will resubmit this Mock Board for admin approval.
            </p>
            <div class="modal-footer">
                <button type="button" class="rv-btn rv-btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="rv-btn rv-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('head')
<style>
    .mock-boards-container { max-width: 1100px; padding: 20px; font-family: var(--font, 'DM Sans', sans-serif); color: #2D2D2B; }
    .alert-success { background:#dcfce7; color:#15803d; padding:15px; border-radius:8px; margin-bottom:20px; font-family: var(--font, 'DM Sans', sans-serif); font-size: 14px; font-weight: 500; }
    .alert-error { background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; font-family: var(--font, 'DM Sans', sans-serif); font-size: 14px; font-weight: 500; }

    .empty-state { text-align:center; padding:60px 20px; color:#5a5550; background:#fff; border-radius:12px; border:2px dashed #e2e8f0; font-family: var(--font, 'DM Sans', sans-serif); }
    .empty-state i { font-size:56px; color:#8a8580; margin-bottom:16px; }
    .empty-state h3 { font-size: 20px; font-weight: 500; color: #2D2D2B; margin: 12px 0 6px; font-family: var(--font, 'DM Sans', sans-serif); }
    .empty-state p { font-size: 15px; font-weight: 400; color: #718096; margin: 0 0 16px 0; font-family: var(--font, 'DM Sans', sans-serif); }

    .mock-boards-list { display:flex; flex-direction:column; gap:20px; font-family: var(--font, 'DM Sans', sans-serif); }
    .mock-board-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:24px; box-shadow:0 2px 4px rgba(0,0,0,0.02); font-family: var(--font, 'DM Sans', sans-serif); }
    .mock-board-card:hover { box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); }
    .board-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px; }
    .board-header h3 { margin:0; font-size:20px; font-weight:500; color:#2D2D2B; font-family: var(--font, 'DM Sans', sans-serif); }
    .board-program-label { font-size:12px; color:#718096; text-transform:uppercase; font-weight:500; letter-spacing:0.04em; font-family: var(--font, 'DM Sans', sans-serif); }

    .status-badge { padding:4px 12px; border-radius:20px; font-size:12px; font-weight:500; text-transform:uppercase; white-space:nowrap; font-family: var(--font, 'DM Sans', sans-serif); letter-spacing:0.04em; }
    .status-pending { background:#fef3c7; color:#92400e; }
    .status-approved { background:#dcfce7; color:#15803d; }
    .status-rejected { background:#fee2e2; color:#b91c1c; }

    .rejection-note { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:14px; margin-bottom:16px; font-family: var(--font, 'DM Sans', sans-serif); font-weight: 400; }

    .board-description { color:#5a5550; margin-bottom:20px; line-height:1.5; font-size:14px; font-weight: 400; font-family: var(--font, 'DM Sans', sans-serif); }
    .board-meta { display:flex; gap:25px; font-size:13px; color:#718096; margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid #f1f5f9; font-family: var(--font, 'DM Sans', sans-serif); font-weight: 400; }
    .board-meta i { color:#245E55; margin-right:8px; }
    .board-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; font-family: var(--font, 'DM Sans', sans-serif); }

    .rv-btn-primary-outline { background:transparent; border:1.5px solid #245E55; color:#245E55; font-weight:500; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:14px; font-family: var(--font, 'DM Sans', sans-serif); }
    .rv-btn-primary-outline:hover { background:#245E55; color:white; }
    .rv-btn-secondary { background:#f8fafc; border:1px solid #cbd5e1; color:#475569; font-weight:500; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:14px; font-family: var(--font, 'DM Sans', sans-serif); }
    .rv-btn-danger { background:#fff5f5; border:1px solid #feb2b2; color:#c53030; padding:8px 12px; border-radius:8px; font-family: var(--font, 'DM Sans', sans-serif); font-weight: 500; }
    .rv-btn-danger:hover { background:#c53030; color:white; }

    .modal { position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); font-family: var(--font, 'DM Sans', sans-serif); }
    .modal-content { background:#fff; border-radius:15px; padding:30px; max-width:600px; width:95%; max-height:90vh; overflow-y:auto; font-family: var(--font, 'DM Sans', sans-serif); }
    .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #e2e8f0; padding-bottom:10px; font-family: var(--font, 'DM Sans', sans-serif); }
    .modal-header h3 { margin:0; color:#2D2D2B; font-size:20px; font-weight:500; font-family: var(--font, 'DM Sans', sans-serif); }
    .modal-close { font-size:24px; cursor:pointer; color:#a0aec0; }
    .modal-form-group { margin-bottom:14px; }
    .modal-label { display:block; font-size:12px; font-weight:500; text-transform:uppercase; color:#475569; margin-bottom:5px; letter-spacing:0.5px; font-family: var(--font, 'DM Sans', sans-serif); }
    .modal-input { width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; font-family: var(--font, 'DM Sans', sans-serif); font-weight: 400; box-sizing:border-box; }
    .modal-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
    .modal-radio-row { display:flex; gap:20px; margin-top:5px; font-size:14px; font-family: var(--font, 'DM Sans', sans-serif); }
    .modal-footer { margin-top:25px; padding-top:15px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; font-family: var(--font, 'DM Sans', sans-serif); }
    .modal-info-note { font-size:13px; color:#718096; background:#f8fafc; padding:10px 12px; border-radius:8px; border:1px solid #e2e8f0; font-family: var(--font, 'DM Sans', sans-serif); font-weight: 400; }
</style>
@endsection

@section('scripts')
<script>
    function openCreateModal() { document.getElementById('createModal')?.style.setProperty('display', 'flex'); }
    function closeCreateModal() { document.getElementById('createModal')?.style.setProperty('display', 'none'); }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

    function toggleModalPhaseInputs() {
        const selected = document.querySelector('input[name="selected_phase"]:checked');
        if (!selected) return;
        document.getElementById('modal_pre_test_title_group').style.display = selected.value === 'pre_test' ? 'block' : 'none';
        document.getElementById('modal_pre_boards_title_group').style.display = selected.value === 'pre_boards' ? 'block' : 'none';
    }

    function editBoard(button) {
        const form = document.getElementById('editForm');
        form.action = '{{ url("mock-boards") }}/' + button.dataset.id;
        document.getElementById('editTitle').value = button.dataset.title || '';
        document.getElementById('editDescription').value = button.dataset.description || '';
        document.getElementById('editStart').value = button.dataset.start || '';
        document.getElementById('editEnd').value = button.dataset.end || '';
        document.getElementById('editPassing').value = button.dataset.passing || '';
        document.getElementById('editMaxAttempts').value = button.dataset.attempts || '1';
        document.getElementById('editModal').style.display = 'flex';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) event.target.style.display = 'none';
    }
</script>
@endsection