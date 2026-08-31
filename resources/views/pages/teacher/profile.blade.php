@php
    $profileTrack = auth()->user()?->role === 'student'
        ? (auth()->user()?->program ?? 'accountancy')
        : auth()->user()?->role;
@endphp

@extends(match($profileTrack) {
    'teacher' => 'layouts.appTeach',
    'admin', 'superadmin' => 'layouts.appAdmin',
    'accountancy' => 'layouts.appAcc',
    'educ' => 'layouts.appEduc',
    'psych' => 'layouts.appPsych',
    default => 'layouts.app'
})

@section('title', 'Account Settings')
@section('page-heading', 'Account Settings')

@section('content')
<style>
    .pf-wrap { max-width: 780px; display: flex; flex-direction: column; gap: 14px; }
    .pf-sub  { font-size: 16px; color: #888; margin: -8px 0 6px; }

    .pf-info-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }

    .pf-info-card {
        background: #fff; border: 1px solid #ebebeb;
        border-radius: 12px; padding: 14px 18px;
    }

    .pf-info-label {
        font-size: 16px; font-weight: 500; letter-spacing: 0.06em;
        text-transform: uppercase; color: #bbb; margin: 0 0 5px;
    }

    .pf-info-value { font-size: 16px; font-weight: 500; color: #111; margin: 0; }

    .pf-card { background: #fff; border: 1px solid #ebebeb; border-radius: 12px; overflow: hidden; }
    .pf-card-head { padding: 14px 20px; border-bottom: 1px solid #f3f3f3; }
    .pf-card-title { font-size: 16px; font-weight: 500; color: #111; margin: 0 0 2px; }
    .pf-card-sub   { font-size: 16px; color: #aaa; margin: 0; }
    .pf-card-body  { padding: 20px; }

    .pf-form-row      { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .pf-form-row.two  { grid-template-columns: repeat(2, 1fr); }
    .pf-form-group    { display: flex; flex-direction: column; gap: 5px; }

    .pf-label {
        font-size: 16px; font-weight: 500;
        letter-spacing: 0.05em; text-transform: uppercase; color: #aaa;
    }

    .pf-input, .pf-select {
        height: 38px; padding: 0 12px;
        border: 1px solid #e4e4e4; border-radius: 8px;
        font-family: 'DM Sans', sans-serif; font-size: 16px; color: #111;
        outline: none; transition: border-color 0.15s, box-shadow 0.15s;
        background: #fff; width: 100%; appearance: none;
    }

    .pf-input:focus, .pf-select:focus { border-color: #111; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
    .pf-input::placeholder { color: #ccc; }
    .pf-input.is-invalid, .pf-select.is-invalid { border-color: #e24b4a; }

    .pf-input-icon { position: relative; }
    .pf-input-icon .pf-input { padding-left: 36px; }
    .pf-input-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #ccc; }

    .pf-error { font-size: 16px; color: #e24b4a; margin: 2px 0 0; }

    .pf-card-footer {
        padding: 14px 20px; border-top: 1px solid #f3f3f3;
        display: flex; justify-content: flex-end;
    }

    .pf-btn {
        height: 36px; padding: 0 18px; border-radius: 8px;
        font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 500;
        cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid transparent; transition: background 0.15s, transform 0.1s;
    }

    .pf-btn:active { transform: scale(0.98); }
    .pf-btn-primary { background: #0f0f0f; color: #fff; }
    .pf-btn-primary:hover { background: #333; }
    .pf-btn-success { background: #1d9e75; color: #fff; }
    .pf-btn-success:hover { background: #0f6e56; }

    .pf-alert { padding: 10px 16px; border-radius: 8px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .pf-alert.success { background: #e1f5ee; color: #0f6e56; }
    .pf-alert.danger  { background: #fcebeb; color: #a32d2d; }

    .pf-program-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #f3f3f3; border: 1px solid #ebebeb;
        border-radius: 8px; padding: 8px 14px;
        font-size: 16px; font-weight: 500; color: #111;
    }

    .pf-program-locked-note {
        font-size: 16px; color: #aaa; margin: 8px 0 0;
        display: flex; align-items: center; gap: 5px;
    }

    .pf-otp-section {
        background: #f9f9f9; border: 1px solid #ebebeb;
        border-radius: 10px; padding: 16px 20px; margin-top: 14px;
    }

    .pf-otp-label { font-size: 16px; color: #555; margin: 0 0 10px; }

    .pf-otp {
        height: 48px; font-size: 20px; font-weight: 500;
        letter-spacing: 0.3em; text-align: center; max-width: 200px;
    }

    @media (max-width: 680px) {
        .pf-info-row, .pf-form-row, .pf-form-row.two { grid-template-columns: 1fr; }
    }
</style>

<div class="pf-wrap">

    <p class="pf-sub">Manage your personal information and security settings.</p>

    @if(session('status'))
        <div class="pf-alert success"><i class="fas fa-check-circle"></i> {!! session('status') !!}</div>
    @endif

    @if(session('error'))
        <div class="pf-alert danger"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    {{-- Basic Info --}}
    <div class="pf-info-row">
        <div class="pf-info-card">
            <p class="pf-info-label">ID Number</p>
            <p class="pf-info-value" style="display:flex;align-items:center;gap:6px;">
                {{ $user->idnumber }}
                <i class="fas fa-lock" style="font-size:13px;color:#94a3b8;" title="Locked ID Number"></i>
            </p>
        </div>
        <div class="pf-info-card">
            <p class="pf-info-label">Email</p>
            <p class="pf-info-value">{{ $user->email }}</p>
        </div>
        <div class="pf-info-card">
            <p class="pf-info-label">Role</p>
            <p class="pf-info-value" style="text-transform:capitalize;">{{ $user->role }}</p>
        </div>
    </div>

    {{-- Program (everyone except admin) --}}
    @if($user->role !== 'admin')
    <div class="pf-card">
        <div class="pf-card-head">
            <p class="pf-card-title">Program / Course</p>
            <p class="pf-card-sub">
                @if($user->program_locked || !empty($user->program))
                    Your program is locked. Contact an admin if you need to change it.
                @else
                    Set your program once - it cannot be changed after saving without admin approval.
                @endif
            </p>
        </div>

        @if($user->program_locked || !empty($user->program))
            <div class="pf-card-body">
                <div class="pf-program-badge">
                    <i class="fas fa-graduation-cap" style="color:#2563eb;font-size: 16px;"></i>
                    {{ $programs[$user->program] ?? ucfirst($user->program) }}
                    <i class="fas fa-lock" style="font-size: 16px;color:#bbb;"></i>
                </div>
                <p class="pf-program-locked-note">
                    <i class="fas fa-info-circle" style="font-size: 16px;"></i>
                    Program locked. To request a change, contact your administrator.
                </p>
            </div>
        @else
            <form method="POST" action="{{ route('profile.program.update') }}">
                @csrf
                <div class="pf-card-body">
                    <div class="pf-form-row two">
                        <div class="pf-form-group">
                            <label class="pf-label">Select Program</label>
                            <select name="program" class="pf-select {{ $errors->has('program') ? 'is-invalid' : '' }}" required>
                                <option value="" disabled selected>Choose your program...</option>
                                @foreach($programs as $key => $label)
                                    <option value="{{ $key }}" {{ old('program') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('program') <p class="pf-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p style="font-size: 16px;color:#e24b4a;margin:10px 0 0;display:flex;align-items:center;gap:5px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 16px;"></i>
                        Once saved, this cannot be changed without admin approval.
                    </p>
                </div>
                <div class="pf-card-footer">
                    <button type="submit" class="pf-btn pf-btn-success">
                        <i class="fas fa-graduation-cap"></i> Save Program
                    </button>
                </div>
            </form>
        @endif
    </div>
    @endif

    {{-- Change Password --}}
    <div class="pf-card">
        <div class="pf-card-head">
            <p class="pf-card-title">Change Password</p>
            <p class="pf-card-sub">You'll need your current password to confirm.</p>
        </div>
        <form method="POST" action="{{ route('profile.password.update') }}">
            @csrf
            <div class="pf-card-body">
                <div class="pf-form-row">
                    <div class="pf-form-group">
                        <label class="pf-label">Current Password</label>
                        <div class="pf-input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="current_password"
                                   class="pf-input {{ $errors->has('current_password') ? 'is-invalid' : '' }}"
                                placeholder="••••••••" required>
                        </div>
                        @error('current_password') <p class="pf-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label">New Password</label>
                        <div class="pf-input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password"
                                   class="pf-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label">Confirm New Password</label>
                        <div class="pf-input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password_confirmation"
                                class="pf-input" placeholder="••••••••" required>
                        </div>
                        @error('password') <p class="pf-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
            <div class="pf-card-footer">
                <button type="submit" class="pf-btn pf-btn-primary">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </div>
        </form>
    </div>

    {{-- Change Email --}}
    <div class="pf-card">
        <div class="pf-card-head">
            <p class="pf-card-title">Change Email Address</p>
            <p class="pf-card-sub">For security, verify your current email first, then verify your new email.</p>
        </div>
        <form method="POST" action="{{ route('profile.email.update') }}">
            @csrf
            <div class="pf-card-body">
                <div class="pf-form-row two">
                    <div class="pf-form-group">
                        <label class="pf-label">Current Email Address</label>
                        <div class="pf-input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="pf-input" value="{{ auth()->user()->email }}" disabled>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pf-card-footer">
                <button type="submit" class="pf-btn pf-btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Code To Current Email
                </button>
            </div>
        </form>

        @if(session('email_change_stage') === 'verify_current')
            <div class="pf-card-body" style="padding-top:0;">
                <div class="pf-otp-section">
                    <p class="pf-otp-label">Enter the 6-digit code sent to your current email:</p>
                    <form method="POST" action="{{ route('profile.email.verify') }}">
                        @csrf
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <input type="text" name="otp"
                                   class="pf-input pf-otp {{ $errors->has('current_email_otp') ? 'is-invalid' : '' }}"
                                   placeholder="000000" maxlength="6" required>
                            <button type="submit" class="pf-btn pf-btn-success">
                                <i class="fas fa-check"></i> Verify Current Email
                            </button>
                        </div>
                        @error('current_email_otp') <p class="pf-error" style="margin-top:8px;">{{ $message }}</p> @enderror
                    </form>
                </div>
            </div>
        @endif

        @if(in_array(session('email_change_stage'), ['enter_new', 'verify_new'], true))
            <div class="pf-card-body" style="padding-top:0;">
                <div class="pf-otp-section">
                    <p class="pf-otp-label">Enter your new email address:</p>
                    <form method="POST" action="{{ route('profile.email.new') }}">
                        @csrf
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <input type="email" name="new_email"
                                   class="pf-input {{ $errors->has('new_email') ? 'is-invalid' : '' }}"
                                   placeholder="new.email@example.com"
                                   value="{{ old('new_email', session('pending_email')) }}" required>
                            <button type="submit" class="pf-btn pf-btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Code To New Email
                            </button>
                        </div>
                        @error('new_email') <p class="pf-error" style="margin-top:8px;">{{ $message }}</p> @enderror
                    </form>
                </div>
            </div>
        @endif

        @if(session('email_change_stage') === 'verify_new')
            <div class="pf-card-body" style="padding-top:0;">
                <div class="pf-otp-section">
                    <p class="pf-otp-label">Enter the 6-digit code sent to your new email:</p>
                    <form method="POST" action="{{ route('profile.email.verify') }}">
                        @csrf
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <input type="text" name="otp"
                                   class="pf-input pf-otp {{ $errors->has('new_email_otp') ? 'is-invalid' : '' }}"
                                   placeholder="000000" maxlength="6" required>
                            <button type="submit" class="pf-btn pf-btn-success">
                                <i class="fas fa-check"></i> Verify & Update Email
                            </button>
                        </div>
                        @error('new_email_otp') <p class="pf-error" style="margin-top:8px;">{{ $message }}</p> @enderror
                    </form>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection 

