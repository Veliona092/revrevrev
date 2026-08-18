@extends('layouts.appAdmin')

@section('title', 'Manage Admin Accounts')
@section('page-heading', 'Manage Admin Accounts')

@section('content')
<style>
    .ma-wrap { display: flex; flex-direction: column; gap: 20px; }
    .ma-sub  { font-size: 15px; color: #64748b; margin: -6px 0 8px; }

    .ma-card {
        background: #fff; border: 1px solid #ebebeb;
        border-radius: 14px; overflow: hidden;
    }

    .ma-card-head {
        padding: 16px 20px; border-bottom: 1px solid #f3f3f3;
        font-size: 20px; font-weight: 500; color: #111;
    }

    .ma-table { width: 100%; border-collapse: collapse; font-size: 16px; }
    .ma-table thead th {
        font-size: 14px; font-weight: 500; letter-spacing: 0.06em;
        text-transform: uppercase; color: #64748b;
        padding: 12px 16px; text-align: left; border-bottom: 1px solid #f0f0f0;
    }
    .ma-table tbody tr { border-bottom: 1px solid #f7f7f7; }
    .ma-table tbody tr:last-child { border-bottom: none; }
    .ma-table tbody tr:hover { background: #fafafa; }
    .ma-table tbody td { padding: 13px 16px; color: #333; vertical-align: middle; }

    .ma-name  { font-size: 17px; font-weight: 500; color: #111; margin: 0 0 3px; }
    .ma-email { font-size: 15px; color: #64748b; margin: 0; }

    .ma-role-badge {
        font-size: 14px; font-weight: 500; padding: 4px 11px;
        border-radius: 99px;
    }
    .ma-role-badge.superadmin { background: #faeeda; color: #854f0b; }
    .ma-role-badge.admin      { background: #f3f3f3; color: #555; }

    .ma-status { font-size: 14px; font-weight: 500; padding: 4px 10px; border-radius: 99px; }
    .ma-status.active   { background: #e1f5ee; color: #0f6e56; }
    .ma-status.rejected { background: #fcebeb; color: #a32d2d; }
    .ma-status.pending  { background: #faeeda; color: #854f0b; }

    .ma-btn {
        height: 36px; padding: 0 14px; border-radius: 8px;
        font-size: 14px; font-weight: 500; cursor: pointer;
        border: 1px solid #e4e4e4; background: #fff; color: #555;
        transition: background 0.15s;
    }
    .ma-btn:hover { border-color: #bbb; color: #111; }
    .ma-btn.danger  { color: #e24b4a; border-color: #f7c1c1; }
    .ma-btn.danger:hover  { background: #fcebeb; }
    .ma-btn.success { color: #0f6e56; border-color: #9fe1cb; }
    .ma-btn.success:hover { background: #e1f5ee; }

    .ma-alert { padding: 12px 17px; border-radius: 9px; font-size: 16px; display: flex; align-items: center; gap: 9px; }
    .ma-alert.success { background: #e1f5ee; color: #0f6e56; }
    .ma-alert.danger  { background: #fcebeb; color: #a32d2d; }

    .ma-sa-note { font-size: 15px; color: #64748b; font-style: italic; }

    .ma-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(12, 17, 29, 0.58);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 13000;
        padding: 14px;
    }

    .ma-modal-overlay.show { display: flex; }

    .ma-modal {
        width: min(460px, 96vw);
        background: #0f1f2b;
        color: #f4fbff;
        border: 1px solid rgba(130, 214, 255, 0.25);
        border-radius: 16px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
        padding: 18px;
    }

    .ma-modal-title {
        margin: 0 0 10px;
        font-size: 17px;
        font-weight: 500;
        color: #9de8ff;
    }

    .ma-modal-text {
        margin: 0;
        font-size: 15px;
        color: #e8f7ff;
        line-height: 1.45;
    }

    .ma-modal-input-wrap {
        margin-top: 14px;
        display: none;
    }

    .ma-modal-input {
        width: 100%;
        border: 2px solid #79e4ff;
        border-radius: 11px;
        height: 42px;
        background: #0e1a24;
        color: #f6fdff;
        padding: 0 13px;
        outline: none;
        font-size: 15px;
    }

    .ma-modal-actions {
        margin-top: 16px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .ma-modal-btn {
        border: none;
        border-radius: 999px;
        padding: 9px 20px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .ma-modal-btn.cancel {
        background: #006879;
        color: #dbf9ff;
    }

    .ma-modal-btn.confirm {
        background: #86ecff;
        color: #0b2b35;
    }

    .ma-modal-btn.confirm.danger { background: #ffb0ad; color: #5f1111; }
    .ma-modal-btn.confirm.success { background: #a7f2cb; color: #0d4a2f; }
</style>

<div class="ma-wrap">
    <p class="ma-sub">Only superadmin can view or modify admin accounts.</p>

    @if(session('status'))
        <div class="ma-alert success"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="ma-alert danger"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    <div class="ma-card">
        <div class="ma-card-head">Admin & Superadmin Accounts</div>
        <table class="ma-table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>ID Number</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                    <tr>
                        <td>
                            <p class="ma-name">{{ $admin->name ?? '—' }}</p>
                            <p class="ma-email">{{ $admin->email }}</p>
                        </td>
                        <td>{{ $admin->idnumber }}</td>
                        <td>
                            <span class="ma-role-badge {{ $admin->role }}">{{ ucfirst($admin->role) }}</span>
                        </td>
                        <td>
                            <span class="ma-status {{ $admin->status ?? 'pending' }}">
                                {{ ucfirst($admin->status ?? 'pending') }}
                            </span>
                        </td>
                        <td>
                            @if($admin->role === 'superadmin')
                                <span class="ma-sa-note">Cannot modify superadmin</span>
                            @else
                                <form method="POST" action="{{ route('superadmin.admins.toggle', $admin) }}"
                                                                            onsubmit="return submitAdminStatusForm(this, '{{ addslashes($admin->idnumber) }}', '{{ $admin->status ?? 'pending' }}')">
                                    @csrf
                                                                        <input type="hidden" name="reason" value="">
                                    @if(($admin->status ?? 'active') === 'active')
                                        <button type="submit" class="ma-btn danger">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </button>
                                    @else
                                        <button type="submit" class="ma-btn success">
                                            <i class="fas fa-check"></i> Reactivate
                                        </button>
                                    @endif
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="maConfirmOverlay" class="ma-modal-overlay" aria-hidden="true">
    <div class="ma-modal" role="dialog" aria-modal="true" aria-labelledby="maModalTitle">
        <p id="maModalTitle" class="ma-modal-title">Please Confirm</p>
        <p id="maModalText" class="ma-modal-text">Are you sure?</p>
        <div id="maModalInputWrap" class="ma-modal-input-wrap">
            <input id="maModalInput" class="ma-modal-input" type="text" maxlength="255" placeholder="Optional reason">
        </div>
        <div class="ma-modal-actions">
            <button type="button" class="ma-modal-btn cancel" id="maModalCancel">Cancel</button>
            <button type="button" class="ma-modal-btn confirm" id="maModalConfirm">Continue</button>
        </div>
    </div>
</div>

<script>
var maPendingAction = null;

function closeAdminStatusConfirm() {
    var overlay = document.getElementById('maConfirmOverlay');
    var input = document.getElementById('maModalInput');
    if (!overlay || !input) {
        return;
    }

    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
    input.value = '';
    maPendingAction = null;
}

function openAdminStatusConfirm(options) {
    var overlay = document.getElementById('maConfirmOverlay');
    var title = document.getElementById('maModalTitle');
    var text = document.getElementById('maModalText');
    var inputWrap = document.getElementById('maModalInputWrap');
    var input = document.getElementById('maModalInput');
    var confirmBtn = document.getElementById('maModalConfirm');
    if (!overlay || !title || !text || !inputWrap || !input || !confirmBtn) {
        return;
    }

    title.textContent = options.title || 'Please Confirm';
    text.textContent = options.message || 'Are you sure?';
    confirmBtn.textContent = options.confirmLabel || 'Continue';
    confirmBtn.classList.remove('danger', 'success');
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

    maPendingAction = options.onConfirm || null;
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

document.getElementById('maModalCancel')?.addEventListener('click', closeAdminStatusConfirm);
document.getElementById('maModalConfirm')?.addEventListener('click', function () {
    var input = document.getElementById('maModalInput');
    var action = maPendingAction;
    var value = input ? input.value.trim() : '';
    closeAdminStatusConfirm();
    if (typeof action === 'function') {
        action(value);
    }
});

function submitAdminStatusForm(form, idNumber, status) {
    var reasonInput = form.querySelector('input[name="reason"]');
    var isActive = String(status) === 'active';

    if (isActive) {
        openAdminStatusConfirm({
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

    openAdminStatusConfirm({
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
