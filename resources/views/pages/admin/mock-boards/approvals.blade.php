@extends('layouts.appAdmin')

@section('title', 'Mock Board Approvals')

@section('page-heading')
    Mock Board <span style="color: #245E55;">Approvals</span>
@endsection

@section('content')
<div class="mock-boards-container">

    {{-- SUCCESS ALERT --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- STATUS FILTER TABS --}}
    <div class="status-tabs">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.mock-boards.approvals', ['status' => $key]) }}"
               class="status-tab {{ $selectedStatus === $key ? 'active' : '' }} tab-{{ $key }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- MOCK BOARDS LIST --}}
    @if($mockBoards->isEmpty())
        <div class="empty-state">
            <i class="fas fa-clipboard-check" style="font-size: 56px; color: #8a8580; margin-bottom: 16px;"></i>
            <h3>No {{ ucfirst($selectedStatus) }} Mock Boards</h3>
            <p>There are currently no mock boards with this status.</p>
        </div>
    @else
        <div class="mock-boards-list">
            @foreach($mockBoards as $board)
                <div class="mock-board-card">
                    <div class="board-header">
                        <div>
                            <h3 style="margin:0; font-size: 1.25rem; color: #1a202c;">{{ $board->title ?? 'Untitled Mock Board' }}</h3>
                            <span style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: bold;">
                                Program: {{ $board->program ?? 'N/A' }} &middot; Teacher: {{ $board->teacher->name ?? 'Unknown' }}
                            </span>
                        </div>
                        <span class="status-badge {{ $board->status }}">
                            {{ ucfirst($board->status) }}
                        </span>
                    </div>

                    @if($board->status === 'rejected' && $board->rejection_reason)
                        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:14px;">
                            <strong>Rejection Reason:</strong> {{ $board->rejection_reason }}
                        </div>
                    @endif

                    <p class="board-description">{{ $board->description ?? 'No description provided.' }}</p>

                    <div class="board-meta">
                        @if($board->review_period_start && $board->review_period_end)
                            <span><i class="fas fa-calendar-alt"></i> {{ $board->review_period_start->format('M d') }} - {{ $board->review_period_end->format('M d, Y') }}</span>
                        @endif
                        <span><i class="fas fa-bullseye"></i> Passing: {{ $board->passing_percentage ?? 75 }}%</span>
                        <span>
                            <i class="fas fa-layer-group"></i>
                            Phases:
                            @forelse($board->phases as $phase)
                                {{ ucfirst(str_replace('_', ' ', $phase->phase_type)) }}{{ !$loop->last ? ',' : '' }}
                            @empty
                                None yet
                            @endforelse
                        </span>
                    </div>

                    @if($selectedStatus === 'pending')
                        <div class="board-actions">
                            <form action="{{ route('admin.mock-boards.approve', $board) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="rv-btn rv-btn-primary">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>

                            <button class="rv-btn rv-btn-danger-outline" onclick="openRejectModal({{ $board->id }})">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
            <h3 style="margin: 0; color: #1a202c; font-size: 1.5rem;">Reject Mock Board</h3>
            <span style="font-size: 24px; cursor: pointer; color: #a0aec0;" onclick="closeRejectModal()">&times;</span>
        </div>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="modal-form-group">
                <label class="modal-label">Reason (Optional)</label>
                <textarea name="rejection_reason" class="modal-input" rows="3" placeholder="Let the teacher know why this was rejected..."></textarea>
            </div>
            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="rv-btn rv-btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="rv-btn rv-btn-danger">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('head')
<style>
    .mock-boards-container { max-width: 1100px; padding: 20px; margin: 0 auto; }

    .status-tabs { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; }
    .status-tab {
        padding: 10px 18px; font-size: 14px; font-weight: 600; color: #718096;
        text-decoration: none; border-bottom: 3px solid transparent; margin-bottom: -1px;
    }
    .status-tab.active.tab-pending { color: #92400e; border-color: #f59e0b; }
    .status-tab.active.tab-approved { color: #15803d; border-color: #22c55e; }
    .status-tab.active.tab-rejected { color: #b91c1c; border-color: #ef4444; }

    .empty-state { text-align: center; padding: 60px 20px; color: #5a5550; background: #fff; border-radius: 12px; border: 2px dashed #e2e8f0; }

    .mock-boards-list { display: flex; flex-direction: column; gap: 20px; }
    .mock-board-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .board-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .status-badge.approved { background: #dcfce7; color: #15803d; }
    .status-badge.pending { background: #fef3c7; color: #92400e; }
    .status-badge.rejected { background: #fee2e2; color: #b91c1c; }
    .board-description { color: #4a5568; margin-bottom: 16px; line-height: 1.5; font-size: 14px; }

    .board-meta { display: flex; gap: 25px; font-size: 13px; color: #718096; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
    .board-meta i { color: #245E55; margin-right: 6px; }
    .board-actions { display: flex; gap: 10px; align-items: center; }

    .rv-btn-primary { background: #245E55; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; border: none; cursor: pointer; }
    .rv-btn-secondary { background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; font-weight: 600; padding: 8px 16px; border-radius: 8px; font-size: 14px; cursor: pointer; }
    .rv-btn-danger { background: #c53030; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; }
    .rv-btn-danger-outline { background: #fff5f5; border: 1px solid #feb2b2; color: #c53030; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; }
    .rv-btn-danger-outline:hover { background: #c53030; color: white; }
    .alert-success { background: #dcfce7; color: #15803d; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .alert-error { background: #fee2e2; color: #b91c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }

    .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
    .modal-content { background: #fff; border-radius: 15px; padding: 30px; max-width: 500px; width: 95%; }
    .modal-form-group { margin-bottom: 14px; }
    .modal-label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #475569; margin-bottom: 5px; letter-spacing: 0.5px; }
    .modal-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
</style>
@endsection

@section('scripts')
<script>
    function openRejectModal(boardId) {
        const form = document.getElementById('rejectForm');
        form.action = `/admin/mock-boards/${boardId}/reject`;
        document.getElementById('rejectModal').style.display = 'flex';
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
</script>
@endsection