@php
$user = Auth::user();
$track = $user->role === 'student' ? ($user->program ?? 'accountancy') : $user->role;
$layout = match($track) {
    'psych' => 'layouts.appPsych',
    'educ' => 'layouts.appEduc',
    'accountancy' => 'layouts.appAcc',
    default => 'layouts.app',
};
@endphp
@extends($layout)

@section('title', 'Join Class')

@section('content')
<style>
    .jc-wrap {
        max-width: 480px;
        margin: 60px auto;
        padding: 0 20px;
    }
    .jc-card {
        background: #fff;
        border: 1px solid #e4e4e4;
        border-radius: 16px;
        padding: 32px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .jc-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #f3f3f3;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin: 0 auto 20px;
    }
    .jc-icon.success { background: #dcfce7; color: #16a34a; }
    .jc-icon.info { background: #dbeafe; color: #2563eb; }
    .jc-title {
        font-family: 'DM Sans', sans-serif;
        font-size: 22px;
        font-weight: 600;
        color: #111;
        margin: 0 0 8px;
    }
    .jc-subtitle {
        font-size: 15px;
        color: #888;
        margin: 0 0 24px;
        line-height: 1.5;
    }
    .jc-class-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        text-align: left;
    }
    .jc-class-name {
        font-size: 18px;
        font-weight: 600;
        color: #111;
        margin: 0 0 6px;
    }
    .jc-class-meta {
        font-size: 14px;
        color: #888;
        margin: 0;
    }
    .jc-class-teacher {
        font-size: 14px;
        color: #666;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .jc-buttons {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    .jc-btn {
        height: 44px;
        padding: 0 24px;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s;
        border: none;
    }
    .jc-btn-primary {
        background: #0f0f0f;
        color: #fff;
    }
    .jc-btn-primary:hover {
        background: #333;
    }
    .jc-btn-secondary {
        background: #f3f3f3;
        color: #555;
    }
    .jc-btn-secondary:hover {
        background: #e4e4e4;
    }
    .jc-btn-success {
        background: #16a34a;
        color: #fff;
    }
    .jc-btn-success:hover {
        background: #15803d;
    }
    .jc-already {
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: center;
    }
</style>

<div class="jc-wrap">
    <div class="jc-card">
        @if($isAlreadyJoined)
            <div class="jc-icon success">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="jc-title">Already Joined</h1>
            <p class="jc-subtitle">You're already enrolled in this class.</p>

            <div class="jc-class-box">
                <p class="jc-class-name">{{ $class->name }}</p>
                @if($class->code)
                    <p class="jc-class-meta">Code: {{ $class->code }}</p>
                @endif
                <p class="jc-class-teacher">
                    <i class="fas fa-user"></i>
                    Teacher: {{ $class->creator->name ?? 'Unknown' }}
                </p>
            </div>

            <div class="jc-buttons">
                <a href="{{ route('student.classes') }}" class="jc-btn jc-btn-primary">
                    <i class="fas fa-arrow-right"></i> Go to My Classes
                </a>
            </div>
        @else
            <div class="jc-icon info">
                <i class="fas fa-door-open"></i>
            </div>
            <h1 class="jc-title">Join Class</h1>
            <p class="jc-subtitle">You've been invited to join a class. Confirm to enroll.</p>

            <div class="jc-class-box">
                <p class="jc-class-name">{{ $class->name }}</p>
                @if($class->code)
                    <p class="jc-class-meta">Code: {{ $class->code }}</p>
                @endif
                @if($class->description)
                    <p class="jc-class-meta" style="margin-top: 8px;">{{ $class->description }}</p>
                @endif
                <p class="jc-class-teacher">
                    <i class="fas fa-user"></i>
                    Teacher: {{ $class->creator->name ?? 'Unknown' }}
                </p>
            </div>

            <form method="POST" action="{{ URL::signedRoute('class.join.confirm', ['class' => $class->id]) }}">
                @csrf
                <div class="jc-buttons">
                    <a href="{{ route('dashboard') }}" class="jc-btn jc-btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="jc-btn jc-btn-success">
                        <i class="fas fa-check"></i> Join Class
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
