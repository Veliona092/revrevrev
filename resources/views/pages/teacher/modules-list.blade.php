@extends('layouts.appTeach')

@section('title', 'Modules - ' . $class->name)
@section('page-heading', 'Modules')

@section('header-actions')
    <a href="{{ route('manageclass') }}" class="rv-btn rv-btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Classes
    </a>
@endsection

@section('content')
<style>
    .ml-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .ml-class-icon {
        width: 42px; height: 42px; border-radius: 10px;
        background: #245E55; display: flex; align-items: center;
        justify-content: center; font-size: 16px; color: #fff;
        flex-shrink: 0;
    }

    .ml-class-name { font-size: 18px; font-weight: 500; color: #111; }
    .ml-class-sub  { font-size: 16px; color: #aaa; margin-top: 2px; }

    .ml-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        overflow: hidden;
    }

    .ml-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        border-bottom: 1px solid #f3f3f3;
        transition: background 0.12s;
    }

    .ml-item:last-child { border-bottom: none; }
    .ml-item:hover { background: #fafaf9; }

    .ml-item-icon {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; flex-shrink: 0;
    }

    .ml-item-icon.quiz       { background: rgba(76,175,80,0.12);  color: #3a9a3e; }
    .ml-item-icon.assignment { background: rgba(255,193,7,0.15);  color: #b38600; }
    .ml-item-icon.document   { background: rgba(33,150,243,0.12); color: #1976d2; }
    .ml-item-icon.assessment { background: rgba(99,130,255,0.15); color: #5c7cfa; }
    .ml-item-icon.video      { background: rgba(233,69,96,0.12);  color: #e94560; }

    .ml-item-body { flex: 1; min-width: 0; }
    .ml-item-title { font-size: 16px; font-weight: 500; color: #111; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ml-item-desc  { font-size: 16px; color: #aaa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .ml-item-meta { display: flex; align-items: center; gap: 8px; margin-top: 5px; flex-wrap: wrap; }

    .ml-badge {
        font-size: 16px; font-weight: 600; padding: 2px 8px;
        border-radius: 99px; text-transform: uppercase; letter-spacing: 0.05em;
    }

    .ml-badge-quiz       { background: rgba(76,175,80,0.12);  color: #3a9a3e; }
    .ml-badge-assignment { background: rgba(255,193,7,0.15);  color: #b38600; }
    .ml-badge-document   { background: rgba(33,150,243,0.12); color: #1976d2; }
    .ml-badge-assessment { background: rgba(99,130,255,0.15); color: #5c7cfa; }
    .ml-badge-video      { background: rgba(233,69,96,0.12);  color: #e94560; }

    .ml-item-time { font-size: 16px; color: #ccc; }

    .ml-item-actions { display: flex; gap: 6px; flex-shrink: 0; }

    .ml-empty {
        padding: 56px 24px;
        text-align: center;
        color: #bbb;
    }

    .ml-empty-icon { font-size: 32px; margin-bottom: 10px; }
    .ml-empty-text { font-size: 16px; }

    .ml-toast {
        position: fixed;
        top: 22px;
        right: 22px;
        z-index: 12000;
        min-width: 240px;
        max-width: min(92vw, 360px);
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #f4b3b2;
        background: #fff4f3;
        color: #7b2220;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        font-size: 16px;
        opacity: 0;
        transform: translateY(-6px);
        transition: opacity 0.2s ease, transform 0.2s ease;
        pointer-events: none;
    }

    .ml-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .ml-toast.success {
        border-color: #c9e8d4;
        background: #e9f8ef;
        color: #17653f;
    }

    .ml-confirm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.42);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 12000;
        padding: 16px;
    }

    .ml-confirm-overlay.show { display: flex; }

    .ml-confirm {
        width: min(420px, 96vw);
        background: #fff;
        border: 1px solid #ececec;
        border-radius: 12px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.22);
        padding: 14px;
    }

    .ml-confirm h4 {
        margin: 0 0 8px;
        font-size: 16px;
        color: #111;
    }

    .ml-confirm p {
        margin: 0;
        font-size: 16px;
        color: #555;
        line-height: 1.45;
    }

    .ml-confirm-actions {
        margin-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
</style>

<div class="ml-header">
    <div class="ml-class-icon"><i class="fas fa-chalkboard-teacher"></i></div>
    <div>
        <div class="ml-class-name">{{ $class->name }}</div>
        <div class="ml-class-sub">{{ $modules->count() }} module{{ $modules->count() !== 1 ? 's' : '' }}</div>
    </div>
</div>

<div class="ml-card">
    @forelse ($modules as $module)
        @php
            if ($module->is_formal_assessment) {
                $iconClass = 'assessment'; $icon = 'fa-star'; $badgeClass = 'ml-badge-assessment'; $badgeLabel = 'Assessment';
            } elseif ($module->is_quiz) {
                $iconClass = 'quiz'; $icon = 'fa-clipboard-list'; $badgeClass = 'ml-badge-quiz'; $badgeLabel = 'Practice Quiz';
            } elseif ($module->is_assignment) {
                $iconClass = 'assignment'; $icon = 'fa-tasks'; $badgeClass = 'ml-badge-assignment'; $badgeLabel = 'Assignment';
            } elseif ($module->file_type === 'mov') {
                $iconClass = 'video'; $icon = 'fa-video'; $badgeClass = 'ml-badge-video'; $badgeLabel = 'Video';
            } else {
                $iconClass = 'document'; $icon = 'fa-file-alt'; $badgeClass = 'ml-badge-document'; $badgeLabel = 'Document';
            }
        @endphp
        <div class="ml-item">
            <div class="ml-item-icon {{ $iconClass }}">
                <i class="fas {{ $icon }}"></i>
            </div>
            <div class="ml-item-body">
                <div class="ml-item-title">{{ $module->title }}</div>
                @if ($module->description)
                    <div class="ml-item-desc">{{ $module->description }}</div>
                @endif
                <div class="ml-item-meta">
                    <span class="ml-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    <span class="ml-item-time">{{ $module->created_at->diffForHumans() }}</span>
                </div>
            </div>
            <div class="ml-item-actions">
                @if ($module->file_path)
                    <a href="{{ asset('storage/' . $module->file_path) }}" target="_blank"
                       class="rv-btn rv-btn-secondary" style="height:32px;padding:0 12px;font-size: 16px;">
                        <i class="fas fa-eye"></i>
                    </a>
                @endif
                @if ($module->is_quiz || $module->is_formal_assessment)
                    <a href="{{ route('quiz.create', $module) }}"
                       class="rv-btn rv-btn-secondary" style="height:32px;padding:0 12px;font-size: 16px;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endif
                <button type="button" class="rv-btn rv-btn-danger delete-module"
                        data-id="{{ $module->id }}"
                        style="height:32px;padding:0 12px;font-size: 16px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    @empty
        <div class="ml-empty">
            <div class="ml-empty-icon"><i class="fas fa-folder-open"></i></div>
            <div class="ml-empty-text">No modules in this class yet.</div>
        </div>
    @endforelse
</div>

<div id="mlConfirmOverlay" class="ml-confirm-overlay" aria-hidden="true">
    <div class="ml-confirm" role="dialog" aria-modal="true" aria-labelledby="mlConfirmTitle">
        <h4 id="mlConfirmTitle">Please Confirm</h4>
        <p id="mlConfirmMessage">Are you sure?</p>
        <div class="ml-confirm-actions">
            <button type="button" class="rv-btn rv-btn-secondary" id="mlConfirmCancel">Cancel</button>
            <button type="button" class="rv-btn rv-btn-danger" id="mlConfirmProceed">Delete</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let mlToastTimer = null;
let mlConfirmAction = null;

function showModulesListToast(message, type) {
    type = type || 'error';
    let toast = document.getElementById('mlToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'mlToast';
        toast.className = 'ml-toast';
        document.body.appendChild(toast);
    }

    toast.classList.remove('success');
    if (type === 'success') {
        toast.classList.add('success');
    }
    toast.textContent = message;
    toast.classList.add('show');

    if (mlToastTimer) {
        clearTimeout(mlToastTimer);
    }
    mlToastTimer = setTimeout(function () {
        toast.classList.remove('show');
    }, 2600);
}

function openModulesListConfirm(message, onConfirm) {
    const overlay = document.getElementById('mlConfirmOverlay');
    const messageEl = document.getElementById('mlConfirmMessage');
    if (!overlay || !messageEl) {
        return;
    }

    mlConfirmAction = onConfirm;
    messageEl.textContent = message;
    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');
}

function closeModulesListConfirm() {
    const overlay = document.getElementById('mlConfirmOverlay');
    if (!overlay) {
        return;
    }

    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
    mlConfirmAction = null;
}

document.getElementById('mlConfirmCancel')?.addEventListener('click', closeModulesListConfirm);
document.getElementById('mlConfirmProceed')?.addEventListener('click', function () {
    const action = mlConfirmAction;
    closeModulesListConfirm();
    if (typeof action === 'function') {
        action();
    }
});

document.querySelectorAll('.delete-module').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        openModulesListConfirm('Delete this module? This cannot be undone.', function () {
            fetch('/modules/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(() => {
                showModulesListToast('Module deleted.', 'success');
                setTimeout(() => location.reload(), 400);
            })
            .catch(() => showModulesListToast('Failed to delete module.', 'error'));
        });
    });
});
</script>
@endsection


