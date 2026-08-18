@extends($layout)

@section('title', 'Announcements')
@section('page-heading', 'Announcements')

@section('content')
<style>
    .an-wrap { display: grid; grid-template-columns: 300px 1fr; gap: 18px; }

    .an-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        overflow: hidden;
    }

    .an-head {
        padding: 15px 18px;
        border-bottom: 1px solid #f3f3f3;
        font-size: 15px;
        font-weight: 500;
        color: #222;
    }

    .an-body { padding: 16px 18px; }

    .an-class-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        color: #444;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 15px;
        margin-bottom: 6px;
    }

    .an-class-link:hover { background: #f7f7f7; color: #111; }
    .an-class-link.active { background: #111; color: #fff; }

    .an-badge {
        min-width: 26px;
        height: 26px;
        padding: 0 8px;
        border-radius: 99px;
        background: #f1f1f1;
        color: #666;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .an-class-link.active .an-badge { background: rgba(255,255,255,0.2); color: #fff; }

    .an-tools {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }

    .an-search {
        width: 100%;
        height: 42px;
        border: 1px solid #e4e4e4;
        border-radius: 10px;
        padding: 0 12px;
        font-size: 15px;
        outline: none;
    }

    .an-search:focus { border-color: #111; box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }

    .an-item {
        border: 1px solid #efefef;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        background: #fff;
    }

    .an-item.pinned {
        border-color: #f2d17f;
        background: #fffaf0;
    }

    .an-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .an-meta {
        font-size: 13px;
        color: #777;
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .an-pill {
        background: #f2f2f2;
        color: #444;
        font-size: 12px;
        font-weight: 500;
        border-radius: 99px;
        padding: 3px 9px;
    }

    .an-pill.pin { background: #f8df9f; color: #825b00; }

    .an-message { font-size: 15px; color: #222; line-height: 1.55; white-space: pre-wrap; }

    .an-del {
        border: 1px solid #f4c5c5;
        color: #b03d3d;
        background: #fff;
        border-radius: 9px;
        font-size: 13px;
        height: 32px;
        padding: 0 12px;
        cursor: pointer;
    }

    .an-del:hover { background: #fff4f4; }

    .an-edit {
        border: 1px solid #d6dbe2;
        color: #374151;
        background: #fff;
        border-radius: 9px;
        font-size: 13px;
        height: 32px;
        padding: 0 12px;
        cursor: pointer;
    }

    .an-edit:hover { background: #f8fafc; }

    .an-empty { font-size: 15px; color: #999; text-align: center; padding: 22px; }

    .an-edit-overlay {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.4);
        z-index: 9999;
        padding: 16px;
    }

    .an-edit-overlay.show {
        display: flex;
    }

    .an-edit-modal {
        width: min(560px, 96vw);
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
        padding: 16px;
    }

    .an-edit-title {
        margin: 0 0 12px;
        font-size: 17px;
        font-weight: 500;
        color: #222;
    }

    .an-edit-input {
        width: 100%;
        min-height: 140px;
        border: 1px solid #e4e4e4;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 15px;
        font-family: inherit;
        line-height: 1.45;
        color: #222;
        resize: vertical;
        outline: none;
        box-sizing: border-box;
    }

    .an-edit-input:focus {
        border-color: #111;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.06);
    }

    .an-edit-error {
        min-height: 20px;
        margin-top: 10px;
        color: #b03d3d;
        font-size: 13px;
    }

    .an-edit-actions {
        margin-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    @media (max-width: 900px) {
        .an-wrap { grid-template-columns: 1fr; }
    }
</style>

<div class="an-wrap">
    <div class="an-card">
        <div class="an-head">Classes</div>
        <div class="an-body">
            <a class="an-class-link {{ $selectedClassId === null ? 'active' : '' }}" href="{{ route('announcements.index', array_filter(['search' => $search])) }}">
                <span>All Classes</span>
                <span class="an-badge">{{ $totalAnnouncements }}</span>
            </a>

            @foreach($classes as $class)
                @php $count = (int) ($classAnnouncementCounts[$class->id] ?? 0); @endphp
                <a class="an-class-link {{ (int) $selectedClassId === (int) $class->id ? 'active' : '' }}"
                   href="{{ route('announcements.index', array_filter(['class_id' => $class->id, 'search' => $search])) }}">
                    <span>{{ $class->name }}</span>
                    <span class="an-badge">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="an-card">
        <div class="an-head">Feed</div>
        <div class="an-body">
            <form method="GET" action="{{ route('announcements.index') }}" class="an-tools">
                @if($selectedClassId)
                    <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                @endif
                <input class="an-search" type="text" name="search" value="{{ $search }}" placeholder="Search announcements...">
                <button class="rv-btn rv-btn-secondary" type="submit" style="height:42px;">Search</button>
            </form>

            @forelse($announcements as $announcement)
                @php
                    $isAdmin = in_array(auth()->user()->role, ['admin', 'superadmin'], true);
                    $isPoster = (int) $announcement->user_id === (int) auth()->id();
                    $isClassTeacher = (int) ($announcement->class->created_by ?? 0) === (int) auth()->id();
                    $canDelete = $isAdmin || $isPoster || $isClassTeacher;
                    $canEdit = $canDelete;
                @endphp
                <div class="an-item {{ $announcement->is_pinned ? 'pinned' : '' }}">
                    <div class="an-top">
                        <div class="an-meta">
                            <span class="an-pill">{{ $announcement->class->name ?? 'Class' }}</span>
                            @if($announcement->is_pinned)
                                <span class="an-pill pin"><i class="fas fa-thumbtack" style="font-size:9px;"></i> Pinned</span>
                            @endif
                            <span>{{ $announcement->user->name ?? 'Unknown' }}</span>
                            <span>{{ $announcement->created_at?->diffForHumans() }}</span>
                        </div>

                        <div style="display:flex;gap:6px;align-items:center;">
                            @if($canEdit)
                                <button
                                    type="button"
                                    class="an-edit"
                                    onclick='editAnnouncement({{ $announcement->id }}, @json($announcement->message))'
                                >
                                    Edit
                                </button>
                            @endif
                            @if($canDelete)
                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="an-del" type="submit">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="an-message">{{ $announcement->message }}</div>
                </div>
            @empty
                <div class="an-empty">No announcements found.</div>
            @endforelse

            {{ $announcements->links() }}
        </div>
    </div>
</div>

<form id="announcementEditForm" method="POST" style="display:none;">
    @csrf
    @method('PATCH')
    <input type="hidden" name="message" id="announcementEditMessage">
</form>

<div id="announcementEditOverlay" class="an-edit-overlay" aria-hidden="true">
    <div class="an-edit-modal" role="dialog" aria-modal="true" aria-labelledby="announcementEditTitle">
        <p class="an-edit-title" id="announcementEditTitle">Edit Announcement</p>
        <textarea id="announcementEditInput" class="an-edit-input" maxlength="1000" placeholder="Update announcement..."></textarea>
        <div id="announcementEditError" class="an-edit-error"></div>
        <div class="an-edit-actions">
            <button type="button" class="an-edit" onclick="closeAnnouncementEditModal()">Cancel</button>
            <button type="button" class="rv-btn rv-btn-primary" onclick="submitAnnouncementEdit()">Save Changes</button>
        </div>
    </div>
</div>

<script>
    let editingAnnouncementId = null;

    function closeAnnouncementEditModal() {
        const overlay = document.getElementById('announcementEditOverlay');
        const input = document.getElementById('announcementEditInput');
        const error = document.getElementById('announcementEditError');

        editingAnnouncementId = null;
        input.value = '';
        error.textContent = '';
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
    }

    function editAnnouncement(announcementId, currentMessage) {
        editingAnnouncementId = announcementId;

        const overlay = document.getElementById('announcementEditOverlay');
        const input = document.getElementById('announcementEditInput');
        const error = document.getElementById('announcementEditError');

        error.textContent = '';
        input.value = currentMessage || '';
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');

        setTimeout(function () {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }, 0);
    }

    function submitAnnouncementEdit() {
        const input = document.getElementById('announcementEditInput');
        const error = document.getElementById('announcementEditError');
        const trimmedMessage = input.value.trim();

        if (!editingAnnouncementId) {
            return;
        }

        if (trimmedMessage === '') {
            error.textContent = 'Announcement message is required.';
            input.focus();
            return;
        }

        error.textContent = '';

        const form = document.getElementById('announcementEditForm');
        form.action = "{{ url('/announcements') }}/" + editingAnnouncementId;
        document.getElementById('announcementEditMessage').value = trimmedMessage;
        closeAnnouncementEditModal();
        form.submit();
    }

    document.getElementById('announcementEditOverlay').addEventListener('click', function (e) {
        if (e.target === this) {
            closeAnnouncementEditModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('announcementEditOverlay');
            if (overlay.classList.contains('show')) {
                closeAnnouncementEditModal();
            }
        }
    });
</script>
@endsection
