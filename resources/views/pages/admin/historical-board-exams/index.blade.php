@extends('layouts.appAdmin')

@section('title', 'Historical Board Exam Results')

@section('page-heading')
    Historical <span style="color: #245E55;">Board Exam Results</span>
@endsection

@section('content')
<div class="mock-boards-container">

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <p style="color:#64748b; margin-bottom:20px;">
        Manually-entered real-world board/licensure exam results, used as a comparison
        benchmark against a mock board's own overall post-test passing rate. This data
        does not come from students taking a quiz in Reviso — type it in from a physical
        results bulletin.
    </p>

    @if(auth()->user() && in_array(auth()->user()->role, ['admin', 'superadmin'], true))
        {{-- ADD NEW RECORD --}}
        <div class="mock-board-card" style="margin-bottom: 24px;">
            <h3 style="margin-top:0;">Add Historical Exam Result</h3>
            <form action="{{ route('historical-board-exams.store') }}" method="POST" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                @csrf
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569;">Program</label>
                    <select name="program" class="modal-input" required>
                        <option value="psychology">Psychology</option>
                        <option value="education">Education</option>
                        <option value="accountancy">Accountancy</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569;">Exam Label</label>
                    <input type="text" name="exam_label" class="modal-input" placeholder="e.g. October 2024 CPA Licensure Exam" required>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569;">Period/Year</label>
                    <input type="text" name="exam_period_or_year" class="modal-input" placeholder="2024" required>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569;">Total Examinees</label>
                    <input type="number" name="total_examinees" class="modal-input" min="1" required>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569;">Passed Count</label>
                    <input type="number" name="passed_count" class="modal-input" min="0" required>
                </div>
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569;">Source Note</label>
                    <input type="text" name="source_note" class="modal-input" placeholder="e.g. Typed from PRC results bulletin, page 4">
                </div>
                <button type="submit" class="rv-btn rv-btn-primary">
                    <i class="fas fa-plus"></i> Add Record
                </button>
            </form>
        </div>
    @endif

    {{-- RESULTS LIST --}}
    @if($results->isEmpty())
        <div class="empty-state">
            <i class="fas fa-file-alt" style="font-size: 56px; color: #8a8580; margin-bottom: 16px;"></i>
            <h3>No Historical Exam Results Yet</h3>
        </div>
    @else
        <div class="mock-boards-list">
            @foreach($results as $result)
                <div class="mock-board-card">
                    <div class="board-header">
                        <div>
                            <h3 style="margin:0; font-size: 1.15rem; color: #1a202c;">{{ $result['exam_label'] }}</h3>
                            <span style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: bold;">
                                {{ ucfirst($result['program']) }} &bull; {{ $result['exam_period_or_year'] }}
                            </span>
                        </div>
                        <span class="status-badge approved">{{ $result['passing_rate'] }}% Pass Rate</span>
                    </div>
                    <p style="margin: 10px 0; color:#475569;">
                        {{ $result['passed_count'] }} / {{ $result['total_examinees'] }} passed
                        @if($result['source_note'])
                            &bull; <em>{{ $result['source_note'] }}</em>
                        @endif
                    </p>
                    @if(auth()->user() && in_array(auth()->user()->role, ['admin', 'superadmin'], true))
                        <form action="{{ route('historical-board-exams.destroy', $result['id']) }}" method="POST" onsubmit="return confirm('Delete this historical exam record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rv-btn rv-btn-danger">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
