@extends('layouts.appEduc')

@section('title', 'Classes')
@section('page-heading', 'Classes')

@section('content')
<style>
    .mc-wrap { display: flex; flex-direction: column; gap: 20px; }

    .mc-sub { font-size: 16px; color: #888; margin: -6px 0 8px; }

    /* ── Empty state ── */
    .mc-empty {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 3rem 2rem;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
    }

    .mc-empty-icon {
        width: 56px; height: 56px; border-radius: 13px;
        background: #eff6ff; color: #2563eb;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
    }

    .mc-empty-title { font-size: 18px; font-weight: 500; color: #111; margin: 0; }
    .mc-empty-sub   { font-size: 16px; color: #aaa; margin: 0; max-width: 380px; }

    .mc-empty-btn {
        height: 42px; padding: 0 22px; background: #0f0f0f; color: #fff;
        border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif;
        font-size: 15px; font-weight: 500; cursor: pointer;
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        transition: background 0.15s;
    }

    .mc-empty-btn:hover { background: #333; color: #fff; }

    /* ── Stats row ── */
    .mc-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }

    .mc-stat {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .mc-stat-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }

    .mc-stat-label { font-size: 14px; color: #aaa; margin: 0 0 2px; font-weight: 500; }
    .mc-stat-val   { font-family: 'DM Sans', sans-serif; font-size: 34px; color: #111; line-height: 1; margin: 0; }

    /* ── Class cards grid ── */
    .mc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 18px;
    }

    .mc-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 20px 22px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        transition: box-shadow 0.15s, border-color 0.15s;
        animation: mc-fadein 0.2s ease both;
    }

    @keyframes mc-fadein {
        from { opacity: 0; transform: translateY(5px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .mc-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); border-color: #d4d4d4; }

    .mc-card-top {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    }

    .mc-card-icon {
        width: 42px; height: 42px; border-radius: 11px;
        background: #eff6ff; color: #2563eb;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; flex-shrink: 0;
    }

    .mc-card-badge {
        font-size: 13px; font-weight: 500; padding: 3px 9px;
        border-radius: 99px; background: #f3f3f3; color: #666;
        white-space: nowrap;
    }

    .mc-card-name { font-size: 18px; font-weight: 500; color: #111; margin: 0 0 4px; }
    .mc-card-desc { font-size: 15px; color: #aaa; margin: 0; line-height: 1.5; }

    .mc-card-divider { height: 1px; background: #f3f3f3; }

    .mc-card-meta {
        display: flex; align-items: center; justify-content: space-between;
    }

    .mc-card-teacher { font-size: 15px; color: #888; display: flex; align-items: center; gap: 6px; }

    .mc-view-btn {
        height: 38px; padding: 0 15px;
        background: #0f0f0f; color: #fff; border: none;
        border-radius: 7px; font-family: 'DM Sans', sans-serif;
        font-size: 14px; font-weight: 500; cursor: pointer;
        text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
        transition: background 0.15s;
        white-space: nowrap;
    }

    .mc-view-btn:hover { background: #333; color: #fff; }

    @media (max-width: 580px) {
        .mc-stats { grid-template-columns: 1fr 1fr; }
        .mc-grid  { grid-template-columns: 1fr; }
    }
</style>

<div class="mc-wrap">

    <p class="mc-sub">Classes you're currently enrolled in.</p>

    @if($enrolledClasses->isEmpty())

        <div class="mc-empty">
            <div class="mc-empty-icon"><i class="fas fa-book-open"></i></div>
            <p class="mc-empty-title">No classes yet</p>
            <p class="mc-empty-sub">You're not enrolled in any classes. Ask your teacher to add you or check your email for an invite link.</p>
            <a href="{{ route('dashboard') }}" class="mc-empty-btn">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>

    @else

        {{-- Stats row --}}
        <div class="mc-stats">
            <div class="mc-stat">
                <div class="mc-stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-chalkboard"></i></div>
                <div>
                    <p class="mc-stat-label">Enrolled classes</p>
                    <p class="mc-stat-val">{{ $enrolledClasses->count() }}</p>
                </div>
            </div>
       
            <div class="mc-stat">
                <div class="mc-stat-icon" style="background:#faeeda;color:#854f0b;"><i class="fas fa-layer-group"></i></div>
                <div>
                    <p class="mc-stat-label">Total modules</p>
                    <p class="mc-stat-val">{{ $totalModules ?? 0 }}</p>
                </div>
            </div>
        </div>

        {{-- Class cards --}}
        <div class="mc-grid">
            @foreach($enrolledClasses as $i => $class)
                <div class="mc-card" style="animation-delay: {{ $i * 40 }}ms">
                    <div class="mc-card-top">
                        <div class="mc-card-icon"><i class="fas fa-book-open"></i></div>
                        @if($class->school_year)
                            <span class="mc-card-badge">{{ $class->school_year }}</span>
                        @endif
                    </div>

                    <div>
                        <p class="mc-card-name">{{ $class->name }}</p>
                        @if($class->description)
                            <p class="mc-card-desc">{{ Str::limit($class->description, 80) }}</p>
                        @else
                            <p class="mc-card-desc">No description provided.</p>
                        @endif
                    </div>

                    <div class="mc-card-divider"></div>

                    <div class="mc-card-meta">
                        <span class="mc-card-teacher">
                            <i class="fas fa-user" style="font-size:12px;"></i>
                            {{ $class->creator->name ?? $class->creator->idnumber ?? 'Teacher' }}
                        </span>
                        <a href="{{ route('student.modules', $class->id) }}" class="mc-view-btn">
                            <i class="fas fa-arrow-right" style="font-size:12px;"></i> View Modules
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    @endif

</div>
@endsection