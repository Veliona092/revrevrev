@extends('layouts.appAcc')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<style>
    .db-wrap { max-width: 100%; display: flex; flex-direction: column; gap: 16px; }

    .db-wrap::before {
        content: '';
        position: fixed;
        top: 72px;
        right: 4%;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(243, 200, 109, 0.35) 0%, rgba(243, 200, 109, 0) 72%);
        pointer-events: none;
        z-index: 0;
    }

    .db-wrap > * { position: relative; z-index: 1; }

    .db-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .db-stat {
        background: linear-gradient(180deg, #fffdfa 0%, #fcf5e9 100%);
        border: 1px solid #eadcc6;
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 10px 28px rgba(74, 52, 21, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .db-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 34px rgba(74, 52, 21, 0.12);
    }

    .db-stat-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; flex-shrink: 0;
    }

    .db-stat-label { font-size: 11px; color: #806f5c; margin: 0 0 3px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
    .db-stat-val   { font-family: 'DM Sans', sans-serif; font-size: 30px; color: #2D2D2B; line-height: 1; margin: 0; }

    .db-card {
        background: linear-gradient(180deg, #fffdf9 0%, #fcf6eb 100%);
        border: 1px solid #eadcc6;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(74, 52, 21, 0.08);
    }

    .db-card-head {
        padding: 12px 18px;
        border-bottom: 1px solid #efe2ce;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(90deg, rgba(255,255,255,0.7), rgba(252, 242, 225, 0.9));
    }

    .db-card-title { font-size: 12px; font-weight: 700; color: #2D2D2B; margin: 0; letter-spacing: 0.06em; text-transform: uppercase; }
    .db-card-link  { font-size: 11px; color: #9d8768; text-decoration: none; transition: color 0.15s; }
    .db-card-link:hover { color: #b47210; }
    .db-card-body  { padding: 12px 18px; }

    .db-bottom {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 14px;
        align-items: start;
    }

    .db-sched {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px;
        padding: 11px 12px; border-radius: 10px; margin-bottom: 8px; transition: opacity 0.15s, transform 0.2s ease;
        border: 1px solid transparent;
    }
    .db-sched:last-child { margin-bottom: 0; }
    .db-sched:hover { opacity: 1; transform: translateX(2px); }
    .db-sched.blue  { background: #fdf4e8; border-color: #f3dcc0; }
    .db-sched.amber { background: #fff6e6; border-color: #f7e4bf; }
    .db-sched-left  { display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1; }
    .db-sched-icon  { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
    .db-sched-icon.blue  { background: #F5E6CC; color: #C17F24; }
    .db-sched-icon.amber { background: #FAD89A; color: #8a5a10; }
    .db-sched-name  { font-size: 13px; font-weight: 500; color: #2D2D2B; margin: 0 0 2px; }
    .db-sched-sub   { font-size: 11px; color: #9a8f80; margin: 0; }
    .db-sched-sub.urgent { color: #C63F3E; font-weight: 500; }
    .db-join-btn    { display: inline-flex; align-items: center; justify-content: center; min-width: 164px; height: 30px; padding: 0 11px; background: linear-gradient(135deg, #c8871b, #af6f0f); color: #fff; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 11px; font-weight: 600; cursor: pointer; white-space: nowrap; text-decoration: none; flex-shrink: 0; transition: transform 0.15s ease, filter 0.15s ease; }
    .db-join-btn:hover { filter: brightness(1.05); transform: translateY(-1px); }

    .db-assign { display: flex; align-items: center; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid #f0e3d0; }
    .db-assign:last-child { border-bottom: none; }
    .db-assign-left { display: flex; align-items: center; gap: 10px; }
    .db-assign-icon { width: 30px; height: 30px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
    .db-assign-name { font-size: 13px; font-weight: 500; color: #2D2D2B; margin: 0; }
    .db-badge { font-size: 11px; font-weight: 600; padding: 4px 9px; border-radius: 99px; white-space: nowrap; }
    .db-badge.warning { background: #F5E6CC; color: #7a4f10; }
    .db-badge.success { background: #d4e8e5; color: #1a4840; }
    .db-badge.danger  { background: #f5e3e3; color: #9e2f2e; }

    .db-prog { margin-bottom: 14px; }
    .db-prog:last-of-type { margin-bottom: 0; }
    .db-prog-label { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 7px; }
    .db-prog-subject { color: #5a5040; font-weight: 500; }
    .db-prog-val { font-weight: 500; }
    .db-prog-val.green { color: #245E55; }
    .db-prog-val.red   { color: #C63F3E; }
    .db-bar-track { height: 7px; background: #e8ddcc; border-radius: 99px; overflow: hidden; }
    .db-bar-fill  { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
    .db-detail-link { display: block; text-align: right; margin-top: 12px; font-size: 11px; color: #9d8768; text-decoration: none; transition: color 0.15s; }
    .db-detail-link:hover { color: #b47210; }

    .db-announce { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f0e3d0; }
    .db-announce:last-child { border-bottom: none; }
    .db-announce-icon { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
    .db-announce-label { font-size: 11px; color: #C8BBA8; margin: 0 0 2px; }
    .db-announce-text  { font-size: 13px; color: #3d3023; font-weight: 600; margin: 0; }

    .db-msg { display: flex; align-items: flex-start; gap: 10px; }
    .db-msg-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #c8871b, #8a5a10); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; flex-shrink: 0; }
    .db-msg-name { font-size: 13px; font-weight: 500; color: #2D2D2B; margin: 0 0 3px; }
    .db-msg-body { font-size: 12px; color: #9a8f80; margin: 0; font-style: italic; }

    .db-right { display: flex; flex-direction: column; gap: 14px; }

    @media (max-width: 860px) {
        .db-stats  { grid-template-columns: 1fr 1fr; }
        .db-bottom { grid-template-columns: 1fr; }
        .db-wrap::before { display: none; }
    }

    @media (max-width: 480px) {
        .db-stats { grid-template-columns: 1fr; }
        .db-sched { align-items: flex-start; }
        .db-join-btn { align-self: center; }
        .db-stat-val { font-size: 27px; }
    }
</style>

<div class="db-wrap">

    <div class="db-stats">
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#F5E6CC;color:#C17F24;">
                <i class="fas fa-book-open"></i>
            </div>
            <div>
                <p class="db-stat-label">Enrolled classes</p>
                <p class="db-stat-val">{{ $enrolledClasses }}</p>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#FAD89A;color:#8a5a10;">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <p class="db-stat-label">Pending assignments</p>
                <p class="db-stat-val">{{ $pendingAssignments }}</p>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#d4e8e5;color:#1a4840;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <p class="db-stat-label">Overall avg</p>
                <p class="db-stat-val">{{ $overallAvg }}%</p>
            </div>
        </div>
    </div>

    <div class="db-bottom">

        <div style="display:flex;flex-direction:column;gap:14px;">

            <div class="db-card">
                <div class="db-card-head">
                    <p class="db-card-title">Pre-Assessments</p>
                </div>
                <div class="db-card-body">
                    @forelse($upcomingQuizzes as $quiz)
                        <div class="db-sched blue">
                            <div class="db-sched-left">
                                <div class="db-sched-icon blue"><i class="fas fa-question-circle"></i></div>
                                <div>
                                    <p class="db-sched-name">{{ $quiz->title }}</p>
                                    <p class="db-sched-sub">{{ $quiz->class->name }}</p>
                                </div>
                            </div>
                            <a href="{{ route('student.modules', ['class' => $quiz->class_id, 'focus' => 'lecture']) }}" class="db-join-btn">Open Pre-Assessment →</a>
                        </div>
                    @empty
                        <p style="font-size:13px;color:#9a8f80;text-align:center;padding:16px 0;">No pre-assessments yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="db-card">
                <div class="db-card-head">
                    <p class="db-card-title">Assessments</p>
                    <a href="{{ route('assessment') }}" class="db-card-link">View all →</a>
                </div>
                <div class="db-card-body">
                    @forelse($pendingQuizModules as $module)
                        <div class="db-assign">
                            <div class="db-assign-left">
                                <div class="db-assign-icon" style="background:#FDF6EC;color:#8a5a10;"><i class="fas fa-file-alt"></i></div>
                                <p class="db-assign-name">{{ $module->title }}</p>
                            </div>
                            <span class="db-badge warning">Pending</span>
                        </div>
                    @empty
                    @endforelse
                    @forelse($gradedAttempts as $attempt)
                        <div class="db-assign">
                            <div class="db-assign-left">
                                <div class="db-assign-icon" style="background:#d4e8e5;color:#1a4840;"><i class="fas fa-check"></i></div>
                                <p class="db-assign-name">{{ $attempt->module->title }}</p>
                            </div>
                            <span class="db-badge success">{{ $attempt->percentage }}% · Passed</span>
                        </div>
                    @empty
                    @endforelse
                    @forelse($submittedAttempts as $attempt)
                        <div class="db-assign">
                            <div class="db-assign-left">
                                <div class="db-assign-icon" style="background:#f5e3e3;color:#9e2f2e;"><i class="fas fa-paper-plane"></i></div>
                                <p class="db-assign-name">{{ $attempt->module->title }}</p>
                            </div>
                            <span class="db-badge danger">{{ $attempt->percentage }}% · Failed</span>
                        </div>
                    @empty
                    @endforelse
                    @if($pendingQuizModules->isEmpty() && $gradedAttempts->isEmpty() && $submittedAttempts->isEmpty())
                        <p style="font-size:13px;color:#9a8f80;text-align:center;padding:8px 0;">No assessment activity yet.</p>
                    @endif
                </div>
            </div>

        </div>

        <div class="db-right">

            <div class="db-card">
                <div class="db-card-head"><p class="db-card-title">My Progress</p></div>
                <div class="db-card-body">
                    @forelse($classProgress as $item)
                        <div class="db-prog">
                            <div class="db-prog-label">
                                <span class="db-prog-subject">{{ $item['class']->name }}</span>
                                <span class="db-prog-val {{ $item['avg'] >= 75 ? 'green' : ($item['avg'] >= 50 ? '' : 'red') }}">{{ $item['avg'] }}% · {{ $item['label'] }}</span>
                            </div>
                            <div class="db-bar-track"><div class="db-bar-fill" style="width:{{ $item['avg'] }}%;background:{{ $item['color'] }};"></div></div>
                        </div>
                    @empty
                        <p style="font-size:13px;color:#9a8f80;text-align:center;padding:8px 0;">No quiz data yet.</p>
                    @endforelse
                    <a href="{{ route('student.classes') }}" class="db-detail-link">View all classes →</a>
                </div>
            </div>

            <div class="db-card">
                <div class="db-card-head"><p class="db-card-title">Announcements</p></div>
                <div class="db-card-body">
                    @forelse($announcements as $announcement)
                        <div class="db-announce">
                            <div class="db-announce-icon" style="background:#F5E6CC;color:#C17F24;"><i class="fas fa-bell"></i></div>
                            <div>
                                <p class="db-announce-label">{{ $announcement->class->name }} · {{ $announcement->created_at->diffForHumans() }}</p>
                                <p class="db-announce-text">{{ Str::limit($announcement->message, 80) }}</p>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:13px;color:#9a8f80;text-align:center;padding:8px 0;">No announcements yet.</p>
                    @endforelse
                </div>
            </div>

        

        </div>

    </div>

</div>
@endsection
