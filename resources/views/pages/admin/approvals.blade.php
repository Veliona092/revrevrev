@extends('layouts.appAdmin')

@section('title', 'Account Approvals')
@section('page-heading', 'Account Approvals')

@section('content')
<style>
    .ap-wrap { display: flex; flex-direction: column; gap: 16px; }

    .ap-sub { font-size: 15px; color: #64748b; margin: -8px 0 8px; }

    /* ── Stats row ── */
    .ap-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }

    .ap-stat {
        background: #fff; border: 1px solid #ebebeb;
        border-radius: 12px; padding: 14px 18px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }

    .ap-stat-icon {
        font-size: 18px;
        flex-shrink: 0;
    }

    .ap-stat-icon.pending { color: #9a6700; }
    .ap-stat-icon.approved,
    .ap-stat-icon.rejected { color: #8f8574; }

    .ap-stat-main {
        display: flex;
        flex-direction: column;
    }

    .ap-stat-label { font-size: 14px; color: #64748b; margin: 0 0 3px; font-weight: 500; }
    .ap-stat-val   { font-family: 'DM Sans', sans-serif; font-size: 30px; color: #0f172a; line-height: 1; margin: 0; }

    /* ── Card ── */
    .ap-card {
        background: #fff; border: 1px solid #ebebeb;
        border-radius: 12px; overflow: hidden;
    }

    .ap-card-head {
        padding: 13px 18px; border-bottom: 1px solid #f3f3f3;
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        flex-wrap: wrap;
    }

    .ap-card-title { font-size: 20px; font-weight: 500; color: #0f172a; margin: 0; }

    /* ── Bulk action bar ── */
    .ap-bulk-bar {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }

    .ap-btn {
        height: 38px; padding: 0 16px; border-radius: 9px;
        font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500;
        cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid transparent; transition: background 0.15s, transform 0.1s;
        text-decoration: none;
    }

    .ap-btn:active { transform: scale(0.98); }
    .ap-btn-success { background: #1d9e75; color: #fff; border-color: #1d9e75; }
    .ap-btn-success:hover { background: #0f6e56; color: #fff; }
    .ap-btn-outline { background: #fff; color: #555; border-color: #e4e4e4; }
    .ap-btn-outline:hover { border-color: #111; color: #111; }
    .ap-btn-danger  { background: #fff; color: #e24b4a; border-color: #f7c1c1; }
    .ap-btn-danger:hover  { background: #fcebeb; }
    .ap-btn-sm { height: 34px; padding: 0 12px; font-size: 15px; }

    /* ── Table ── */
    .ap-table { width: 100%; border-collapse: collapse; font-size: 16px; }

    .ap-table thead th {
        font-size: 14px; font-weight: 500; letter-spacing: 0.05em;
        text-transform: uppercase; color: #64748b;
        padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .ap-table thead th:first-child { width: 36px; }

    .ap-role-select {
        font-size: 15px; padding: 4px 10px; border: 1px solid #e4e4e4;
        border-radius: 6px; color: #555; background: #fff;
        cursor: pointer; outline: none; height: 36px;
    }

    .ap-role-select:focus { border-color: #111; }

    /* ── Approve modal ── */
    .ap-approve-modal {
        background: #fff; border-radius: 14px;
        padding: 24px; width: 460px; max-width: 95vw;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .ap-role-options {
        display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px;
    }

    .ap-role-opt {
        flex: 1; min-width: 90px;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        padding: 9px 12px; border: 2px solid #e4e4e4; border-radius: 9px;
        font-size: 15px; font-weight: 500; color: #334155; cursor: pointer; 
        transition: border-color 0.15s, background 0.15s, color 0.15s;
        user-select: none;
    }

    .ap-role-opt:has(input:checked) {
        border-color: #111; background: #f7f7f7; color: #111;
    }

    .ap-role-opt input { display: none; }

    .ap-field-label { font-size: 14px; font-weight: 500; color: #475569; margin: 0 0 6px; }

    .ap-program-wrap { margin-bottom: 14px; }

    /* bulk inline selects shown per checked row */
    .row-bulk-selects { display: none; gap: 6px; align-items: center; flex-wrap: wrap; }
    .row-bulk-selects.visible { display: flex; }

    .ap-table tbody tr { border-bottom: 1px solid #f7f7f7; transition: background 0.1s; }
    .ap-table tbody tr:last-child { border-bottom: none; }
    .ap-table tbody tr:hover { background: #fafafa; }
    .ap-table tbody td { padding: 11px 14px; color: #333; vertical-align: middle; }

    .ap-checkbox { width: 15px; height: 15px; cursor: pointer; accent-color: #0f0f0f; }

    .ap-name  { font-weight: 500; color: #111; margin: 0 0 2px; }
    .ap-email { font-size: 16px; color: #64748b; margin: 0; }

    .ap-role-badge {
        font-size: 15px; font-weight: 500; padding: 4px 11px;
        border-radius: 99px; background: #f3f3f3; color: #555;
        white-space: nowrap;
    }

    .ap-date { font-size: 16px; color: #64748b; white-space: nowrap; }

    .ap-actions { display: flex; gap: 6px; align-items: center; }

    /* ── Reject modal ── */
    .ap-modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.4); z-index: 300;
        backdrop-filter: blur(2px);
        align-items: center; justify-content: center;
    }

    .ap-modal-overlay.open { display: flex; }

    .ap-modal {
        background: #fff; border-radius: 14px;
        padding: 24px; width: 420px; max-width: 95vw;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .ap-modal-title { font-size: 20px; font-weight: 500; color: #0f172a; margin: 0 0 8px; }
    .ap-modal-sub   { font-size: 16px; color: #64748b; margin: 0 0 16px; }

    .ap-textarea {
        width: 100%; padding: 10px 12px; border: 1px solid #e4e4e4;
        border-radius: 8px; font-family: 'DM Sans', sans-serif;
        font-size: 16px; color: #111; resize: vertical; min-height: 100px;
        outline: none; transition: border-color 0.15s;
    }

    .ap-textarea:focus { border-color: #111; }

    .ap-modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }

    /* ── Status badges ── */
    .ap-status {
        font-size: 15px; font-weight: 500; padding: 4px 11px;
        border-radius: 99px; white-space: nowrap;
    }

    .ap-status.active   { background: #e1f5ee; color: #0f6e56; }
    .ap-status.rejected { background: #fcebeb; color: #a32d2d; }
    .ap-status.pending  { background: #faeeda; color: #854f0b; }

    .ap-empty { text-align: center; padding: 3rem; font-size: 15px; color: #94a3b8; }

    .ap-alert { padding: 12px 16px; border-radius: 8px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .ap-alert.success { background: #e1f5ee; color: #0f6e56; }
    .ap-alert.danger  { background: #fcebeb; color: #a32d2d; }

    #selectAll { cursor: pointer; }
</style>

<div class="ap-wrap">
    @php
        $canAssignAdmin = auth()->user()->role === 'superadmin';
    @endphp

    <p class="ap-sub">Review and approve or reject pending account registrations.</p>

    @if(session('status'))
        <div class="ap-alert success"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="ap-alert danger"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="ap-alert danger"><i class="fas fa-exclamation-circle"></i> {{ $errors->first('error') ?: $errors->first() }}</div>
    @endif

    {{-- Stats --}}
    <div class="ap-stats">
        <div class="ap-stat">
            <div class="ap-stat-main">
                <p class="ap-stat-label">Pending</p>
                <p class="ap-stat-val">{{ $pending->count() }}</p>
            </div>
            <i class="fas fa-clock ap-stat-icon pending"></i>
        </div>
        <div class="ap-stat">
            <div class="ap-stat-main">
                <p class="ap-stat-label">Approved</p>
                <p class="ap-stat-val">{{ $recentlyActioned->where('status', 'active')->count() }}</p>
            </div>
            <i class="fas fa-check-circle ap-stat-icon approved"></i>
        </div>
        <div class="ap-stat">
            <div class="ap-stat-main">
                <p class="ap-stat-label">Rejected</p>
                <p class="ap-stat-val">{{ $recentlyActioned->where('status', 'rejected')->count() }}</p>
            </div>
            <i class="fas fa-times-circle ap-stat-icon rejected"></i>
        </div>
    </div>

    {{-- Pending queue --}}
    <div class="ap-card">
        <div class="ap-card-head">
            <p class="ap-card-title">Pending Approvals</p>
            @if($pending->count() > 0)
                <div class="ap-bulk-bar">
                    {{-- Approve selected --}}
                    <form id="approveManyForm" method="POST" action="{{ route('admin.approvals.approve-many') }}">
                        @csrf
                        <div id="selectedIdsContainer"></div>
                        <button type="submit" class="ap-btn ap-btn-outline" id="approveSelectedBtn" disabled>
                            <i class="fas fa-check"></i> Approve Selected
                        </button>
                    </form>


                </div>
            @endif
        </div>

        @if($pending->count() > 0)
            <table class="ap-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="ap-checkbox" id="selectAll"></th>
                        <th>User</th>
                        <th>ID Number</th>
                        <th>Bulk Role</th>
                        <th>Signed Up</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pending as $user)
                        <tr>
                            <td>
                                <input type="checkbox" class="ap-checkbox row-check" value="{{ $user->id }}">
                            </td>
                            <td>
                                <p class="ap-name">{{ $user->name ?? '—' }}</p>
                                <p class="ap-email">{{ $user->email }}</p>
                            </td>
                            <td>{{ $user->idnumber ?? '—' }}</td>
                            <td>
                                {{-- Compact selects shown only when row is checked (for bulk flow) --}}
                                <div class="row-bulk-selects" id="bulk-selects-{{ $user->id }}">
                                    <select class="ap-role-select row-role" data-user-id="{{ $user->id }}">
                                        <option value="" disabled selected>Role…</option>
                                        <option value="student">Student</option>
                                        <option value="teacher">Teacher</option>
                                        @if($canAssignAdmin)
                                            <option value="admin">Admin</option>
                                        @endif
                                    </select>
                                    <select class="ap-role-select row-program" data-user-id="{{ $user->id }}">
                                        <option value="" disabled selected>Program…</option>
                                        <option value="educ">Education</option>
                                        <option value="accountancy">Accountancy</option>
                                        <option value="psych">Psychology</option>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <span class="ap-date">{{ $user->created_at->format('M d, Y') }}</span>
                                <br>
                                <span style="font-size:14px;color:#94a3b8;">{{ $user->created_at->diffForHumans() }}</span>
                            </td>
                            <td>
                                <div class="ap-actions">
                                    {{-- Approve (opens role-assignment modal) --}}
                                    <button type="button" class="ap-btn ap-btn-success ap-btn-sm"
                                            onclick="openApproveModal({{ $user->id }}, '{{ addslashes($user->name ?? $user->email) }}', '{{ addslashes($user->email) }}')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>

                                    {{-- Reject --}}
                                    <button type="button" class="ap-btn ap-btn-danger ap-btn-sm"
                                            onclick="openRejectModal({{ $user->id }}, '{{ addslashes($user->name ?? $user->email) }}')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="ap-empty">
                <i class="fas fa-inbox" style="font-size:28px;opacity:0.2;display:block;margin-bottom:10px;"></i>
                No pending accounts right now.
            </div>
        @endif
    </div>

    {{-- Recently actioned --}}
    @if($recentlyActioned->count() > 0)
    <div class="ap-card">
        <div class="ap-card-head">
            <p class="ap-card-title">Recently Actioned</p>
        </div>
        <table class="ap-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>ID Number</th>
                    <th>Assigned Role</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Actioned</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentlyActioned as $user)
                    <tr>
                        <td>
                            <p class="ap-name">{{ $user->name ?? '—' }}</p>
                            <p class="ap-email">{{ $user->email }}</p>
                        </td>
                        <td>{{ $user->idnumber ?? '—' }}</td>
                        <td><span class="ap-role-badge">{{ $user->role ? ucfirst($user->role) : '—' }}</span></td>
                        <td><span class="ap-role-badge">{{ $user->program ? ucfirst($user->program) : '—' }}</span></td>
                        <td><span class="ap-status {{ $user->status }}">{{ ucfirst($user->status) }}</span></td>
                        <td>
                            <span class="ap-date">{{ $user->updated_at->format('M d, Y') }}</span>
                            @if($user->status === 'rejected' && $user->rejection_reason)
                                <br>
                                <span style="font-size:14px;color:#e24b4a;">{{ Str::limit($user->rejection_reason, 40) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

{{-- Approve modal --}}
<div class="ap-modal-overlay" id="approveModal">
    <div class="ap-approve-modal">
        <p class="ap-modal-title">Approve Account</p>
        <p class="ap-modal-sub" id="approveModalSub">Assign a role before approving this account.</p>

        <form method="POST" id="approveForm">
            @csrf
            <input type="hidden" name="role" id="approveRoleInput">
            <input type="hidden" name="program" id="approveProgramInput">

            <p class="ap-field-label">ASSIGN ROLE</p>
            <div class="ap-role-options">
                <label class="ap-role-opt">
                    <input type="radio" name="_role_ui" value="student">
                    <i class="fas fa-user-graduate"></i> Student
                </label>
                <label class="ap-role-opt">
                    <input type="radio" name="_role_ui" value="teacher">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher
                </label>
                @if($canAssignAdmin)
                <label class="ap-role-opt">
                    <input type="radio" name="_role_ui" value="admin">
                    <i class="fas fa-shield-alt"></i> Admin
                </label>
                @endif
            </div>

            <div class="ap-program-wrap" id="approveProgramWrap" style="display:none;">
                <p class="ap-field-label">PROGRAM</p>
                <select class="ap-role-select" id="approveProgramSelect" style="width:100%;height:34px;">
                    <option value="" disabled selected>Select program…</option>
                    <option value="educ">Education</option>
                    <option value="accountancy">Accountancy</option>
                    <option value="psych">Psychology</option>
                </select>
            </div>

            <div class="ap-modal-footer">
                <button type="button" class="ap-btn ap-btn-outline" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" class="ap-btn ap-btn-success" id="approveConfirmBtn">
                    <i class="fas fa-check"></i> Confirm Approve
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Reject modal --}}
<div class="ap-modal-overlay" id="rejectModal">
    <div class="ap-modal">
        <p class="ap-modal-title">Reject Account</p>
        <p class="ap-modal-sub" id="rejectModalSub">Provide a reason for rejecting this account.</p>
        <form method="POST" id="rejectForm">
            @csrf
            <textarea name="reason" class="ap-textarea" placeholder="e.g. ID number not found in school records…" required></textarea>
            <div class="ap-modal-footer">
                <button type="button" class="ap-btn ap-btn-outline" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="ap-btn ap-btn-danger">
                    <i class="fas fa-times"></i> Confirm Reject
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // ── Select all / deselect all ──
    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(cb => {
            cb.checked = this.checked;
            toggleBulkSelects(cb.value, this.checked);
        });
        updateApproveSelected();
    });

    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', function () {
            toggleBulkSelects(this.value, this.checked);
            updateApproveSelected();
        });
    });

    function toggleBulkSelects(userId, show) {
        const wrap = document.getElementById('bulk-selects-' + userId);
        if (wrap) {
            wrap.classList.toggle('visible', show);
        }
    }

    function updateApproveSelected() {
        const checked = [...document.querySelectorAll('.row-check:checked')];
        const btn = document.getElementById('approveSelectedBtn');
        btn.disabled = checked.length === 0;

        const container = document.getElementById('selectedIdsContainer');
        container.innerHTML = '';
        checked.forEach(cb => {
            const idInput = document.createElement('input');
            idInput.type  = 'hidden';
            idInput.name  = 'user_ids[]';
            idInput.value = cb.value;
            container.appendChild(idInput);

            const roleSelect = document.querySelector(`.row-role[data-user-id="${cb.value}"]`);
            const roleInput  = document.createElement('input');
            roleInput.type   = 'hidden';
            roleInput.name   = `roles[${cb.value}]`;
            roleInput.value  = roleSelect ? roleSelect.value : '';
            container.appendChild(roleInput);

            const programSelect = document.querySelector(`.row-program[data-user-id="${cb.value}"]`);
            const programInput  = document.createElement('input');
            programInput.type   = 'hidden';
            programInput.name   = `programs[${cb.value}]`;
            programInput.value  = programSelect ? programSelect.value : '';
            container.appendChild(programInput);
        });
    }

    // ── Bulk role inline selects ──
    document.querySelectorAll('.row-role').forEach(select => {
        select.addEventListener('change', function () {
            const userId = this.dataset.userId;
            const programSelect = document.querySelector(`.row-program[data-user-id="${userId}"]`);
            if (!programSelect) { return; }

            if (this.value === 'admin') {
                programSelect.value = '';
                programSelect.disabled = true;
            } else {
                programSelect.disabled = false;
            }

            updateApproveSelected();
        });
    });

    document.querySelectorAll('.row-program').forEach(select => {
        select.addEventListener('change', updateApproveSelected);
    });

    document.getElementById('approveManyForm')?.addEventListener('submit', function (e) {
        const checked = [...document.querySelectorAll('.row-check:checked')];
        if (checked.length === 0) {
            e.preventDefault();
            alert('Please select at least one user to approve.');
            return;
        }

        for (const cb of checked) {
            const userId = cb.value;
            const roleSelect = document.querySelector(`.row-role[data-user-id="${userId}"]`);
            const role = roleSelect ? roleSelect.value : '';
            if (!role) {
                e.preventDefault();
                alert('Please select a role for all checked users before clicking Approve Selected.');
                if (roleSelect) { roleSelect.focus(); }
                return;
            }

            if (role === 'student' || role === 'teacher') {
                const progSelect = document.querySelector(`.row-program[data-user-id="${userId}"]`);
                const prog = progSelect ? progSelect.value : '';
                if (!prog) {
                    e.preventDefault();
                    alert('Please select a program for all checked students and teachers before clicking Approve Selected.');
                    if (progSelect) { progSelect.focus(); }
                    return;
                }
            }
        }
    });

    // ── Approve modal ──
    let _approveUserId = null;

    function openApproveModal(userId, userName, userEmail) {
        _approveUserId = userId;
        document.getElementById('approveModalSub').textContent =
            'Assigning a role for ' + userName + ' (' + userEmail + ')';
        document.getElementById('approveForm').action =
            '/admin/approvals/' + userId + '/approve';

        // Reset state
        document.querySelectorAll('#approveModal input[name="_role_ui"]').forEach(r => r.checked = false);
        document.getElementById('approveRoleInput').value = '';
        document.getElementById('approveProgramInput').value = '';
        document.getElementById('approveProgramWrap').style.display = 'none';
        document.getElementById('approveProgramSelect').value = '';

        document.getElementById('approveModal').classList.add('open');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.remove('open');
        _approveUserId = null;
    }

    // Role radio change → show/hide program
    document.querySelectorAll('#approveModal input[name="_role_ui"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const role = this.value;
            document.getElementById('approveRoleInput').value = role;
            const programWrap = document.getElementById('approveProgramWrap');
            const programSelect = document.getElementById('approveProgramSelect');

            if (role === 'student' || role === 'teacher') {
                programWrap.style.display = 'block';
                programSelect.value = '';
                document.getElementById('approveProgramInput').value = '';
            } else {
                programWrap.style.display = 'none';
                programSelect.value = '';
                document.getElementById('approveProgramInput').value = '';
            }
        });
    });

    document.getElementById('approveProgramSelect').addEventListener('change', function () {
        document.getElementById('approveProgramInput').value = this.value;
    });

    document.getElementById('approveForm').addEventListener('submit', function (e) {
        const role = document.getElementById('approveRoleInput').value;
        if (!role) {
            e.preventDefault();
            alert('Please select a role before approving.');
            return;
        }
        if (role === 'student' || role === 'teacher') {
            const program = document.getElementById('approveProgramInput').value;
            if (!program) {
                e.preventDefault();
                alert('Please select a course for the selected role.');
                return;
            }
        }
    });

    document.getElementById('approveModal').addEventListener('click', function (e) {
        if (e.target === this) { closeApproveModal(); }
    });

    // ── Reject modal ──
    function openRejectModal(userId, userName) {
        document.getElementById('rejectModalSub').textContent =
            'Provide a reason for rejecting ' + userName + '\'s account.';
        document.getElementById('rejectForm').action =
            '/admin/approvals/' + userId + '/reject';
        document.getElementById('rejectModal').classList.add('open');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('open');
    }

    document.getElementById('rejectModal').addEventListener('click', function (e) {
        if (e.target === this) { closeRejectModal(); }
    });
</script>
@endsection