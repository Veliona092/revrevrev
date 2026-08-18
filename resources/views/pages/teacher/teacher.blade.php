@extends('layouts.appTeach')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<style>
    .db-wrap { max-width: 100%; display: flex; flex-direction: column; gap: 16px; }

    /*  Stat cards  */
    .db-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .db-stat {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .db-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .db-stat-label { font-size: 17px; color: #aaa; margin: 0 0 3px; font-weight: 500; letter-spacing: 0.03em; }
    .db-stat-val   { font-family: 'DM Sans', sans-serif; font-size: 30px; color: #111; line-height: 1; margin: 0; }

    /*  Card base  */
    .db-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 12px;
        overflow: hidden;
    }

    .db-card-head {
        padding: 12px 18px;
        border-bottom: 1px solid #f3f3f3;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .db-card-title { font-size: 18px; font-weight: 500; color: #111; margin: 0; }
    .db-card-link  { font-size: 17px; color: #bbb; text-decoration: none; transition: color 0.15s; }
    .db-card-link:hover { color: #111; }
    .db-card-body  { padding: 12px 18px; }

    /*  Bottom row  */
    .db-bottom {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 14px;
        align-items: start;
    }

    /*  Class cards grid  */
    .db-classes {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .db-class-card {
        border: 1px solid #ebebeb;
        border-radius: 10px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: box-shadow 0.15s, border-color 0.15s;
    }

    .db-class-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-color: #d4d4d4; }

    .db-class-top { display: flex; align-items: flex-start; justify-content: space-between; }

    .db-class-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #f3f3f3;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #888;
        flex-shrink: 0;
    }

    .db-class-students {
        font-size: 17px;
        color: #aaa;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .db-class-name { font-size: 18px; font-weight: 500; color: #111; margin: 0 0 2px; }
    .db-class-meta { font-size: 17px; color: #bbb; margin: 0; }

    .db-class-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid #f3f3f3;
    }

    .db-class-score { font-size: 17px; color: #aaa; }
    .db-class-score span { font-weight: 500; }

    .db-class-btn {
        height: 30px;
        padding: 0 10px;
        border-radius: 6px;
        font-family: 'DM Sans', sans-serif;
        font-size: 17px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid #e4e4e4;
        background: #fff;
        color: #555;
        transition: border-color 0.15s, color 0.15s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .db-class-btn:hover { border-color: #0f0f0f; color: #0f0f0f; }

    /*  Activity feed  */
    .db-activity-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 9px 0;
        border-bottom: 1px solid #f7f7f7;
    }

    .db-activity-item:last-child { border-bottom: none; }

    .db-activity-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 5px;
    }

    .db-activity-text { font-size: 17px; color: #444; line-height: 1.5; margin: 0; flex: 1; }
    .db-activity-time { font-size: 15px; color: #6b7280; white-space: nowrap; margin-top: 1px; }

    /*  Right panel  */
    .db-right { display: flex; flex-direction: column; gap: 14px; }

    /*  Messages  */
    .db-msg-item { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f7f7f7; }
    .db-msg-item:last-child { border-bottom: none; }
    .db-msg-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #0f0f0f;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        font-weight: 500;
        flex-shrink: 0;
    }
    .db-msg-name { font-size: 18px; font-weight: 500; color: #111; margin: 0 0 2px; }
    .db-msg-body { font-size: 17px; color: #888; margin: 0; font-style: italic; }

    /*  Announcements  */
    .db-announce { display: flex; align-items: flex-start; gap: 9px; padding: 9px 0; border-bottom: 1px solid #f7f7f7; }
    .db-announce:last-child { border-bottom: none; }
    .db-announce-icon {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
        background: #e1f5ee;
        color: #0f6e56;
    }
    .db-announce-label { font-size: 15px; color: #bbb; margin: 0 0 1px; }
    .db-announce-text  { font-size: 17px; color: #333; font-weight: 500; margin: 0; }

    .db-empty { font-size: 17px; color: #b3b3b3; padding: 8px 0; text-align: center; }

    @media (max-width: 860px) {
        .db-stats  { grid-template-columns: 1fr 1fr; }
        .db-bottom { grid-template-columns: 1fr; }
        .db-classes { grid-template-columns: 1fr; }
    }

    @media (max-width: 480px) {
        .db-stats { grid-template-columns: 1fr; }
    }
</style>

<div class="db-wrap">

    {{--  Stat cards  --}}
    <div class="db-stats">
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#eff6ff;color:#2563eb;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <p class="db-stat-label">Total students</p>
                <p class="db-stat-val">{{ $totalStudents }}</p>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#faeeda;color:#854f0b;">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <p class="db-stat-label">Quizzes pending</p>
                <p class="db-stat-val">{{ $quizzesPending }}</p>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#e1f5ee;color:#0f6e56;">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div>
                <p class="db-stat-label">Avg class score</p>
                <p class="db-stat-val">{{ number_format($avgClassScore, 1) }}%</p>
            </div>
        </div>
    </div>

    {{--  Bottom row  --}}
    <div class="db-bottom">

        {{-- Left  Classes + Activity --}}
        <div style="display:flex;flex-direction:column;gap:14px;">

            {{-- Classes --}}
            <div class="db-card">
                <div class="db-card-head">
                    <p class="db-card-title">My Classes</p>
                    <a href="{{ route('manageclass') }}" class="db-card-link">Manage </a>
                </div>
                <div class="db-card-body">
                    <div class="db-classes">
                        @forelse($classes as $class)
                            @php
                                $avgScore = round((float) ($class->avg_score ?? 0), 1);
                                $scoreColor = $avgScore >= 75 ? '#1d9e75' : ($avgScore >= 50 ? '#a16207' : '#dc2626');
                            @endphp
                            <div class="db-class-card">
                                <div class="db-class-top">
                                    <div class="db-class-icon"><i class="fas fa-chalkboard"></i></div>
                                    <span class="db-class-students"><i class="fas fa-user" style="font-size:9px;"></i> {{ $class->students_count }}</span>
                                </div>
                                <div>
                                    <p class="db-class-name">{{ $class->name }}</p>
                                    <p class="db-class-meta">{{ $class->code ? 'Code: '.$class->code : 'No code' }}</p>
                                </div>
                                <div class="db-class-footer">
                                    <span class="db-class-score">Avg <span style="color:{{ $scoreColor }};">{{ number_format($avgScore, 1) }}%</span></span>
                                    <a href="{{ route('student.performance', $class) }}" class="db-class-btn">View performance</a>
                                </div>
                            </div>
                        @empty
                            <p class="db-empty">No classes yet. Create one from class management.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="db-card">
                <div class="db-card-head">
                    <p class="db-card-title">Recent Activity</p>
                </div>
                <div class="db-card-body">
                    @forelse($recentActivity as $event)
                        @php
                            $dotColor = match ($event['type']) {
                                'student_joined' => '#2563eb',
                                'quiz_submitted' => '#854f0b',
                                'module_completed' => '#1d9e75',
                                default => '#9ca3af',
                            };
                        @endphp
                        <div class="db-activity-item">
                            <div class="db-activity-dot" style="background:{{ $dotColor }};"></div>
                            <p class="db-activity-text">{{ $event['label'] }}</p>
                            <span class="db-activity-time">{{ $event['occurred_at']?->diffForHumans() ?? 'N/A' }}</span>
                        </div>
                    @empty
                        <p class="db-empty">No recent activity yet.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right panel --}}
        <div class="db-right">

            {{-- Messages --}}
            <div class="db-card">
                <div class="db-card-head">
                    <p class="db-card-title">Messages</p>
                    <a href="{{ route('chat.index') }}" class="db-card-link">Go to inbox </a>
                </div>
                <div class="db-card-body">
                    @forelse($messages as $message)
                        <div class="db-msg-item">
                            <div class="db-msg-avatar">{{ $message['initials'] ?: 'NA' }}</div>
                            <div>
                                <p class="db-msg-name">{{ $message['name'] }}</p>
                                <p class="db-msg-body">"{{ $message['preview'] ?: 'No messages yet.' }}"</p>
                            </div>
                        </div>
                    @empty
                        <p class="db-empty">No conversations yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Announcements --}}
            <div class="db-card">
                <div class="db-card-head">
                    <p class="db-card-title">Announcements</p>
                </div>
                <div class="db-card-body">
                    @forelse($announcements as $announcement)
                        <div class="db-announce">
                            <div class="db-announce-icon"><i class="fas fa-bell"></i></div>
                            <div>
                                <p class="db-announce-label">{{ $announcement->class?->name ?? 'Class update' }}  {{ $announcement->created_at?->diffForHumans() }}</p>
                                <p class="db-announce-text">{{ \Illuminate\Support\Str::limit($announcement->message, 100) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="db-empty">No announcements yet.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection


