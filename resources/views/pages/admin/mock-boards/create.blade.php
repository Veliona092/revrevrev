@extends('layouts.appAdmin')

@section('title', 'Create Mock Board')

@section('page-heading', 'Create Mock Board')

@section('content')
<div class="mock-boards-container">

    @if($errors->any())
        <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="create-form-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h3 style="margin: 0; color: #1a202c; font-size: 1.5rem;">
                <i class="fas fa-layer-group" style="color: #245E55;"></i> Initialize Mock Board
            </h3>
        </div>

        <form action="{{ route('admin.mock-boards.store') }}" method="POST" id="createBoardForm">
            @csrf

            <div class="modal-form-group">
                <label class="modal-label">Class</label>
                <select name="class_id" id="classSelect" class="modal-input" required>
                    <option value="" disabled selected>Select a class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" data-program="{{ $class->program ?? '' }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="modal-form-group">
                <label class="modal-label">Mock Board Title</label>
                <input type="text" name="title" class="modal-input" placeholder="e.g., Comprehensive Accountancy Mock Exam" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="modal-form-group">
                    <label class="modal-label">Program Track</label>
                    <select name="program" id="programSelect" class="modal-input" required>
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
                        <input type="radio" name="selected_phase" value="pre_test" checked onchange="togglePhaseInputs()"> Pre-Test
                    </label>
                    <label style="font-size: 14px; cursor: pointer;">
                        <input type="radio" name="selected_phase" value="pre_boards" onchange="togglePhaseInputs()"> Pre-Boards
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

            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                <a href="{{ route('admin.mock-boards.index') }}" class="rv-btn rv-btn-secondary">Cancel</a>
                <button type="submit" class="rv-btn rv-btn-primary" style="background: #245E55;">
                    <i class="fas fa-rocket"></i> Initialize & Build Quiz
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('head')
<style>
    .mock-boards-container { max-width: 700px; padding: 20px; }
    .create-form-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .modal-form-group { margin-bottom: 14px; }
    .modal-label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #475569; margin-bottom: 5px; letter-spacing: 0.5px; }
    .modal-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
    .rv-btn-secondary { background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; font-weight: 600; padding: 8px 16px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; }
    .rv-btn-primary { border: none; color: white; font-weight: 600; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
</style>
@endsection

@section('scripts')
<script>
    function togglePhaseInputs() {
        const val = document.querySelector('input[name="selected_phase"]:checked').value;
        document.getElementById('pre_test_title_group').style.display = val === 'pre_test' ? 'block' : 'none';
        document.getElementById('pre_boards_title_group').style.display = val === 'pre_boards' ? 'block' : 'none';
    }
</script>
@endsection