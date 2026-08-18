@extends('layouts.appAdmin')

@section('title', 'User Management')
@section('page-heading', 'User Management')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Users</li>
@endsection

@section('content')
<style>
    .um-wrap { display: flex; flex-direction: column; gap: 20px; }
    .um-sub  { font-size: 15px; color: #64748b; margin: -6px 0 8px; }

    .um-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        overflow: hidden;
    }

    .um-card-head {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f3f3;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .um-card-title { font-size: 20px; font-weight: 500; color: #111; }       

    .um-search {
        display: flex;
        gap: 9px;
    }

    .um-search input {
        border: 1px solid #e5e5e5;
        border-radius: 9px;
        padding: 9px 13px;
        font-size: 16px;
        outline: none;
        width: 260px;
    }

    .um-search input:focus { border-color: #002060; }

    .um-search button {
        background: #002060;
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: 9px 16px;
        font-size: 15px;
        cursor: pointer;
    }

    /* Table */
    .um-table { width: 100%; border-collapse: collapse; }

    .um-table th {
        font-size: 14px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 12px 18px;
        border-bottom: 1px solid #f3f3f3;
        text-align: left;
        background: #fafafa;
    }

    .um-table td {
        font-size: 16px;
        color: #333;
        padding: 14px 18px;
        border-bottom: 1px solid #f7f7f7;
        vertical-align: middle;
    }

    .um-table tr:last-child td { border-bottom: none; }
    .um-table tr:hover td { background: #fafafa; }

    /* Badges */
    .um-badge {
        display: inline-block;
        font-size: 15px;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 99px;
    }

    .um-badge-admin     { background: #fdecea; color: #c0392b; }
    .um-badge-teacher   { background: #e8f5e9; color: #2e7d32; }
    .um-badge-student   { background: #e3f2fd; color: #1565c0; }
    .um-badge-active    { background: #e8f5e9; color: #2e7d32; }
    .um-badge-pending   { background: #fff8e1; color: #f57f17; }
    .um-badge-rejected  { background: #fdecea; color: #c0392b; }

    /* Action buttons */
    .um-btn {
        font-size: 15px;
        font-weight: 500;
        padding: 7px 13px;
        border-radius: 7px;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }

    .um-btn-warn   { background: #fff8e1; color: #e65100; border: 1px solid #ffe082; }
    .um-btn-info   { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
    .um-btn-danger { background: #fdecea; color: #c0392b; border: 1px solid #f5c6cb; }
    .um-btn-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .um-btn-warn:hover { background: #fff3cd; }
    .um-btn-info:hover { background: #bbdefb; }
    .um-btn-danger:hover { background: #f8d7da; }
    .um-btn-success:hover { background: #d4edda; }

    .um-empty {
        text-align: center;
        padding: 52px 26px;
        color: #bbb;
        font-size: 16px;
    }

    .um-empty i { font-size: 36px; display: block; margin-bottom: 12px; }

    .um-pagination { padding: 14px 20px; border-top: 1px solid #f3f3f3; }

    .um-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(12, 17, 29, 0.58);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 13000;
        padding: 14px;
    }

    .um-modal-overlay.show { display: flex; }

    .um-modal {
        width: min(480px, 96vw);
        background: #0f1f2b;
        color: #f4fbff;
        border: 1px solid rgba(130, 214, 255, 0.25);
        border-radius: 16px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
        padding: 18px;
    }

    .um-modal-title {
        margin: 0 0 10px;
        font-size: 20px;
        font-weight: 500;
        color: #9de8ff;
    }

    .um-modal-text {
        margin: 0;
        font-size: 16px;
        color: #e8f7ff;
        line-height: 1.45;
    }

    .um-modal-input-wrap {
        margin-top: 14px;
        display: none;
    }

    .um-modal-input {
        width: 100%;
        border: 2px solid #79e4ff;
        border-radius: 11px;
        height: 42px;
        background: #0e1a24;
        color: #f6fdff;
        padding: 0 13px;
        outline: none;
        font-size: 16px;
    }

    .um-modal-actions {
        margin-top: 16px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .um-modal-btn {
        border: none;
        border-radius: 999px;
        padding: 9px 20px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
    }

    .um-modal-btn.cancel {
        background: #006879;
        color: #dbf9ff;
    }

    .um-modal-btn.confirm {
        background: #86ecff;
        color: #0b2b35;
    }

    .um-modal-btn.confirm.warn { background: #ffdca8; color: #5a3500; }
    .um-modal-btn.confirm.danger { background: #ffb0ad; color: #5f1111; }
    .um-modal-btn.confirm.success { background: #a7f2cb; color: #0d4a2f; }
</style>

<div class="um-wrap">
    <p class="um-sub">Manage user accounts, roles, status, and access permissions.</p>

    <div class="um-card">
        <div class="um-card-head">
            <span class="um-card-title">All Users</span>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <form method="GET" action="{{ route('admin.users') }}" class="um-search" style="display:flex;gap:8px;flex-wrap:wrap;">
                    <input type="text"
                           name="q"
                           placeholder="Search name, email, ID..."
                           value="{{ request('q') }}"
                              style="width:220px;">
                          <select name="role" style="border:1px solid #e5e5e5;border-radius:9px;padding:9px 12px;font-size:16px;outline:none;">
                        <option value="">All Roles</option>
                        <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                        <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                    <select name="status" style="border:1px solid #e5e5e5;border-radius:9px;padding:9px 12px;font-size:16px;outline:none;">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Filter</button>
                </form>
                <a href="{{ route('admin.users.export', request()->only('q', 'role', 'status')) }}"
                   class="rv-btn rv-btn-secondary" style="height:40px;font-size:15px;">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </div>
        </div>

        <table class="um-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td style="color:#bbb;">{{ $user->id }}</td>
                        <td style="font-weight:500;">{{ $user->idnumber }}</td>
                        <td>{{ $user->name ?? '—' }}</td>
                        <td>
                            @php
                                $roleClass = match(true) {
                                    in_array($user->role, ['admin','superadmin']) => 'um-badge-admin',
                                    $user->role === 'teacher' => 'um-badge-teacher',
                                    default => 'um-badge-student',
                                };
                            @endphp
                            <span class="um-badge {{ $roleClass }}">{{ ucfirst($user->role ?? '—') }}</span>
                        </td>
                        <td>
                            @if ($user->program)
                                {{ ucfirst($user->program) }}
                                @if ($user->program_locked)
                                    <span title="Locked" style="margin-left:4px;">🔒</span>
                                @endif
                            @else
                                <span style="color:#bbb;">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusClass = match($user->status ?? 'pending') {
                                    'active'   => 'um-badge-active',
                                    'rejected' => 'um-badge-rejected',
                                    default    => 'um-badge-pending',
                                };
                            @endphp
                            <span class="um-badge {{ $statusClass }}">{{ ucfirst($user->status ?? 'pending') }}</span>
                        </td>
                        <td style="color:#999;">{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <form method="POST" action="{{ route('admin.users.reset', $user->id) }}"
                                      onsubmit="return submitSimpleConfirm(this, 'Reset password for {{ addslashes($user->idnumber) }}?', 'Reset Password', 'warn')">
                                    @csrf
                                    <button type="submit" class="um-btn um-btn-warn">Reset Password</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.toggle-status', $user->id) }}"
                                      onsubmit="return submitStatusForm(this, '{{ addslashes($user->idnumber) }}', '{{ $user->status ?? 'pending' }}')">
                                    @csrf
                                    <input type="hidden" name="reason" value="">
                                    @if(($user->status ?? 'pending') === 'active')
                                        <button type="submit" class="um-btn um-btn-danger">Deactivate</button>
                                    @else
                                        <button type="submit" class="um-btn um-btn-success">Activate</button>
                                    @endif
                                </form>
                                @if ($user->program && $user->program_locked)
                                    <form method="POST" action="{{ route('admin.unlock.program', $user) }}"
                                                                                    onsubmit="return submitSimpleConfirm(this, 'Unlock program for {{ addslashes($user->idnumber) }}?', 'Unlock Program', 'success')">
                                        @csrf
                                        <button type="submit" class="um-btn um-btn-info">Unlock Program</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="um-empty">
                                <i class="fas fa-users-slash"></i>
                                No users found
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($users->hasPages())
            <div class="um-pagination">
                {{ $users->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
</div>

<div id="umConfirmOverlay" class="um-modal-overlay" aria-hidden="true">
    <div class="um-modal" role="dialog" aria-modal="true" aria-labelledby="umModalTitle">
        <p id="umModalTitle" class="um-modal-title">Please Confirm</p>
        <p id="umModalText" class="um-modal-text">Are you sure?</p>
        <div id="umModalInputWrap" class="um-modal-input-wrap">
            <input id="umModalInput" class="um-modal-input" type="text" maxlength="255" placeholder="Optional reason">
        </div>
        <div class="um-modal-actions">
            <button type="button" class="um-modal-btn cancel" id="umModalCancel">Cancel</button>
            <button type="button" class="um-modal-btn confirm" id="umModalConfirm">Continue</button>
        </div>
    </div>
</div>

<script>
var umPendingAction = null;

function closeUmConfirm() {
    var overlay = document.getElementById('umConfirmOverlay');
    var input = document.getElementById('umModalInput');
    if (!overlay || !input) {
        return;
    }

    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
    input.value = '';
    umPendingAction = null;
}

function openUmConfirm(options) {
    var overlay = document.getElementById('umConfirmOverlay');
    var title = document.getElementById('umModalTitle');
    var text = document.getElementById('umModalText');
    var inputWrap = document.getElementById('umModalInputWrap');
    var input = document.getElementById('umModalInput');
    var confirmBtn = document.getElementById('umModalConfirm');
    if (!overlay || !title || !text || !inputWrap || !input || !confirmBtn) {
        return;
    }

    title.textContent = options.title || 'Please Confirm';
    text.textContent = options.message || 'Are you sure?';
    confirmBtn.textContent = options.confirmLabel || 'Continue';
    confirmBtn.classList.remove('warn', 'danger', 'success');
    if (options.confirmKind) {
        confirmBtn.classList.add(options.confirmKind);
    }

    if (options.showInput) {
        inputWrap.style.display = 'block';
        input.placeholder = options.inputPlaceholder || 'Optional reason';
    } else {
        inputWrap.style.display = 'none';
        input.value = '';
    }

    umPendingAction = options.onConfirm || null;
    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');

    setTimeout(function () {
        if (options.showInput) {
            input.focus();
        } else {
            confirmBtn.focus();
        }
    }, 0);
}

document.getElementById('umModalCancel')?.addEventListener('click', closeUmConfirm);
document.getElementById('umModalConfirm')?.addEventListener('click', function () {
    var input = document.getElementById('umModalInput');
    var action = umPendingAction;
    var value = input ? input.value.trim() : '';
    closeUmConfirm();
    if (typeof action === 'function') {
        action(value);
    }
});

function submitSimpleConfirm(form, message, confirmLabel, confirmKind) {
    openUmConfirm({
        title: 'Please Confirm',
        message: message,
        confirmLabel: confirmLabel || 'Continue',
        confirmKind: confirmKind || 'warn',
        showInput: false,
        onConfirm: function () {
            form.submit();
        }
    });

    return false;
}

function submitStatusForm(form, idNumber, status) {
    var reasonInput = form.querySelector('input[name="reason"]');
    var isActive = String(status) === 'active';

    if (isActive) {
        openUmConfirm({
            title: 'Deactivate Account',
            message: 'Optional reason for deactivation. Leave blank for no reason.',
            confirmLabel: 'Deactivate',
            confirmKind: 'danger',
            showInput: true,
            inputPlaceholder: 'Reason (optional)',
            onConfirm: function (reason) {
                if (reasonInput) {
                    reasonInput.value = reason;
                }
                form.submit();
            }
        });

        return false;
    }

    if (reasonInput) {
        reasonInput.value = '';
    }

    openUmConfirm({
        title: 'Activate Account',
        message: 'Activate account ' + idNumber + '?',
        confirmLabel: 'Activate',
        confirmKind: 'success',
        showInput: false,
        onConfirm: function () {
            form.submit();
        }
    });

    return false;
}
</script>

@endsection
