@extends('layouts.appAdmin')

@section('title', 'Mock Boards')

@section('page-heading', 'Mock Boards')

@section('header-actions')
    <button class="rv-btn rv-btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Create Mock Board
    </button>
@endsection

@section('content')
<div class="mock-boards-container">

    @if(session('success'))
        <div class="alert alert-success" style="background: #dcfce7; color: #15803d; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(($mockBoards ?? collect())->isEmpty())
        <div class="empty-state">
            <i class="fas fa-clipboard-list" style="font-size: 64px; color: #cbd5e0; margin-bottom: 20px;"></i>
            <h3 style="color: #4a5568;">No Mock Boards Yet</h3>
            <p style="color: #718096;">Create your first mock board to start tracking student progress across programs.</p>
            <button class="rv-btn rv-btn-primary" style="margin-top: 20px;" onclick="openCreateModal()">
                Create Mock Board
            </button>
        </div>
    @else
        <div class="mock-boards-list">
            @foreach($mockBoards as $board)
                <div class="mock-board-card">
                    <div class="board-header">
                        <div>
                            <h3 style="margin:0; font-size: 1.25rem; color: #1a202c;">{{ $board->title }}</h3>
                            <span style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: bold;">
                                Program: {{ $board->program }} @if($board->class) &bull; Class: {{ $board->class->name }} @endif
                            </span>
                        </div>
                        <span class="status-badge {{ $board->isActive() ? 'active' : 'ended' }}">
                            {{ $board->isActive() ? 'Active' : 'Ended' }}
                        </span>
                    </div>

                    <p class="board-description">{{ $board->description ?? 'No description provided.' }}</p>

                    <div class="board-meta">
                        <span><i class="fas fa-calendar-alt"></i> {{ $board->review_period_start->format('M d') }} - {{ $board->review_period_end->format('M d, Y') }}</span>
                        <span><i class="fas fa-bullseye"></i> Passing: {{ $board->passing_percentage }}%</span>
                        <span><i class="fas fa-user-graduate"></i> {{ $board->attempts_count ?? 0 }} Students Participated</span>
                    </div>

                    <div class="board-actions">
                        <a href="{{ route('mock-boards.analysis', $board) }}" class="rv-btn rv-btn-secondary">
                            <i class="fas fa-chart-line"></i> Batch Analysis
                        </a>

                        @php
                            $preTestPhase = $board->phases->firstWhere('phase_type', 'pre_test');
                            $preBoardsPhase = $board->phases->firstWhere('phase_type', 'pre_boards');
                        @endphp

                        @if($preTestPhase && $preTestPhase->module)
                            <a href="{{ route('quiz.create', $preTestPhase->module) }}" class="rv-btn rv-btn-primary-outline">
                                <i class="fas fa-edit"></i>
                                {{ $preTestPhase->module->quizQuestions->isNotEmpty() ? 'Edit Pre-Test' : 'Build Pre-Test' }}
                            </a>
                        @else
                            <form action="{{ route('mock-boards.phases.add', $board) }}" method="POST" class="inline-form">
                                @csrf
                                <input type="hidden" name="phase_type" value="pre_test">
                                <button type="submit" class="rv-btn rv-btn-secondary">
                                    <i class="fas fa-plus"></i> Add Pre-Test Phase
                                </button>
                            </form>
                        @endif

                        @if($preBoardsPhase && $preBoardsPhase->module)
                            <a href="{{ route('quiz.create', $preBoardsPhase->module) }}" class="rv-btn rv-btn-primary-outline">
                                <i class="fas fa-pen-fancy"></i>
                                {{ $preBoardsPhase->module->quizQuestions->isNotEmpty() ? 'Edit Pre-Boards' : 'Build Pre-Boards' }}
                            </a>
                        @else
                            <form action="{{ route('mock-boards.phases.add', $board) }}" method="POST" class="inline-form">
                                @csrf
                                <input type="hidden" name="phase_type" value="pre_boards">
                                <button type="submit" class="rv-btn rv-btn-secondary">
                                    <i class="fas fa-plus"></i> Add Pre-Boards Phase
                                </button>
                            </form>
                        @endif

                        <button class="rv-btn rv-btn-secondary" onclick="editBoard(this)"
                            data-id="{{ $board->id }}"
                            data-title="{{ $board->title }}"
                            data-description="{{ $board->description ?? '' }}"
                            data-program="{{ $board->program }}"
                            data-start="{{ $board->review_period_start->toDateString() }}"
                            data-end="{{ $board->review_period_end->toDateString() }}"
                            data-passing="{{ $board->passing_percentage }}">
                            <i class="fas fa-cog"></i> Settings
                        </button>

                        <form action="{{ route('mock-boards.destroy', $board) }}" method="POST" class="inline-form" onsubmit="return confirm('Are you sure? This will delete all student attempts and statistics for this board.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rv-btn rv-btn-danger">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ========================================================================= --}}
{{-- CREATE MOCK BOARD MODAL --}}
{{-- ========================================================================= --}}
<div id="createModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
            <h3 style="margin: 0; color: #1a202c; font-size: 1.5rem;"><i class="fas fa-layer-group" style="color: #245E55;"></i> Initialize Mock Board</h3>
            <span style="font-size: 24px; cursor: pointer; color: #a0aec0;" onclick="closeCreateModal()">&times;</span>
        </div>

      <form action="{{ route('admin.mock-boards.store') }}" method="POST" id="createBoardForm">
    @csrf

    <div class="modal-form-group">
        <label class="modal-label">Mock Board Title</label>
        <input type="text" name="title" class="modal-input" placeholder="e.g., Comprehensive Accountancy Mock Exam" required>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div class="modal-form-group">
            <label class="modal-label">Program Track</label>
            <select name="program" class="modal-input" required>
                <option value="">-- Choose Program --</option>
                <option value="education">Education</option>
                <option value="accountancy">Accountancy</option>
                <option value="psychology">Psychology</option>
            </select>
        </div>
        <div class="modal-form-group">
            <label class="modal-label">Passing Rate (%)</label>
            <input type="number" name="passing_percentage" class="modal-input" min="0" max="100" value="75" required>
        </div>
    </div>

    <div class="modal-form-group">
        <label class="modal-label">Description (Optional)</label>
        <textarea name="description" class="modal-input" rows="2" placeholder="Brief instruction overview for student guidelines..."></textarea>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
        <div style="display: flex; gap: 20px; margin-top: 5px;">
            <label style="font-size: 14px; cursor: pointer;">
                <input type="radio" name="selected_phase" value="pre_test" checked onchange="togglePhaseInputs()"> Pre-Test Quiz
            </label>
            <label style="font-size: 14px; cursor: pointer;">
                <input type="radio" name="selected_phase" value="pre_boards" onchange="togglePhaseInputs()"> Pre-Boards Quiz
            </label>
        </div>
    </div>

    <div id="pre_test_title_group" class="modal-form-group">
        <label class="modal-label">Custom Pre-Test Name (Optional)</label>
        <input type="text" name="pre_test_title" class="modal-input" placeholder="Defaults to: [Title] - Pre-Test">
    </div>

    <div id="pre_boards_title_group" class="modal-form-group" style="display: none;">
        <label class="modal-label">Custom Pre-Boards Name (Optional)</label>
        <input type="text" name="pre_boards_title" class="modal-input" placeholder="Defaults to: [Title] - Pre-Boards">
    </div>

    <div class="modal-form-group">
        <label class="modal-label">Time Limit (Minutes)</label>
        <input type="number" name="time_limit" class="modal-input" min="0" value="0" placeholder="0 for unlimited">
    </div>

    <p style="font-size: 13px; color: #718096; background: #f8fafc; padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
        <i class="fas fa-info-circle"></i> This Mock Board will be visible to all students under the selected program.
    </p>

    <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="rv-btn rv-btn-secondary" onclick="closeCreateModal()">Cancel</button>
        <button type="submit" class="rv-btn rv-btn-primary" style="background: #245E55;">
            <i class="fas fa-rocket"></i> Initialize & Build Quiz
        </button>
    </div>
</form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="editModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
            <h3 style="margin: 0; color: #1a202c; font-size: 1.5rem;">Edit Settings</h3>
            <span style="font-size: 24px; cursor: pointer; color: #a0aec0;" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-form-group"><label class="modal-label">Title</label><input type="text" id="editTitle" name="title" class="modal-input" required></div>
            <div class="modal-form-group"><label class="modal-label">Description</label><textarea id="editDescription" name="description" class="modal-input"></textarea></div>
            <div class="modal-form-group"><label class="modal-label">Program</label><input type="text" id="editProgram" name="program" class="modal-input" required></div>
            <div class="modal-form-group"><label class="modal-label">Start Date</label><input type="date" id="editStart" name="review_period_start" class="modal-input" required></div>
            <div class="modal-form-group"><label class="modal-label">End Date</label><input type="date" id="editEnd" name="review_period_end" class="modal-input" required></div>
            <div class="modal-form-group"><label class="modal-label">Passing Rate</label><input type="number" id="editPassing" name="passing_percentage" class="modal-input" required></div>
            <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="rv-btn rv-btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="rv-btn rv-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('head')
<style>
    .mock-boards-container { max-width: 1000px; padding: 20px; }
    .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 15px; border: 2px dashed #e2e8f0; }
    .mock-boards-list { display: flex; flex-direction: column; gap: 20px; }

    .mock-board-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: box-shadow 0.3s ease;
    }
    .mock-board-card:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .board-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
    .status-badge.active { background: #dcfce7; color: #15803d; }
    .status-badge.ended { background: #fee2e2; color: #b91c1c; }
    .board-description { color: #4a5568; margin-bottom: 20px; line-height: 1.5; }
    .board-meta { display: flex; gap: 25px; font-size: 14px; color: #718096; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; }
    .board-meta i { color: #245E55; margin-right: 8px; }
    .board-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

    .rv-btn-primary-outline { background: transparent; border: 1.5px solid #245E55; color: #245E55; font-weight: 600; padding: 8px 16px; border-radius: 8px; }
    .rv-btn-primary-outline:hover { background: #245E55; color: white; }
    .rv-btn-secondary { background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; font-weight: 600; }
    .rv-btn-danger { background: #fff5f5; border: 1px solid #feb2b2; color: #c53030; }
    .rv-btn-danger:hover { background: #c53030; color: white; }

    .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
    .modal-content { background: #fff; border-radius: 15px; padding: 30px; max-width: 600px; width: 95%; max-height: 90vh; overflow-y: auto; }
    .modal-form-group { margin-bottom: 14px; }
    .modal-label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #475569; margin-bottom: 5px; letter-spacing: 0.5px; }
    .modal-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
</style>
@endsection

@section('scripts')
<script>
    function openCreateModal() { document.getElementById('createModal').style.display = 'flex'; }
    function closeCreateModal() { document.getElementById('createModal').style.display = 'none'; }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

    function togglePhaseInputs() {
        const val = document.querySelector('input[name="selected_phase"]:checked').value;
        document.getElementById('pre_test_title_group').style.display = val === 'pre_test' ? 'block' : 'none';
        document.getElementById('pre_boards_title_group').style.display = val === 'pre_boards' ? 'block' : 'none';
    }

    function editBoard(button) {
        const form = document.getElementById('editForm');
        const baseUrl = '{{ url("mock-boards") }}';
        const id = button.dataset.id;

        form.action = baseUrl + '/' + id;
        document.getElementById('editTitle').value = button.dataset.title;
        document.getElementById('editDescription').value = button.dataset.description;
        document.getElementById('editProgram').value = button.dataset.program;
        document.getElementById('editStart').value = button.dataset.start;
        document.getElementById('editEnd').value = button.dataset.end;
        document.getElementById('editPassing').value = button.dataset.passing;

        document.getElementById('editModal').style.display = 'flex';
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = "none";
        }
    }
</script>
@endsection