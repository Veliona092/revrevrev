@extends('layouts.appTeach')

@section('title', 'Class Progress Tracker')
@section('page-heading', 'Class Progress Tracker')

@section('content')
<style>
    .cpt-wrap {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .cpt-main-grid {
        display: grid;
        grid-template-columns: minmax(300px, 0.95fr) minmax(420px, 1.35fr);
        gap: 18px;
        align-items: start;
    }

    /* -”€-”€ Back nav -”€-”€ */
    .cpt-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 19px;
        font-weight: 500;
        color: #aaa;
        text-decoration: none;
        transition: color 0.15s;
    }

    .cpt-back:hover { color: #111; }

    /* -”€-”€ Class switcher -”€-”€ */
    .cpt-switcher {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .cpt-switcher-label {
        font-size: 19px;
        font-weight: 500;
        color: #888;
        white-space: nowrap;
    }

    .cpt-class-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .cpt-class-tab {
        height: 38px; padding: 0 16px; border-radius: 10px;
        font-size: 18px; font-weight: 500;
        border: 1px solid #e4e4e4; background: #fff; color: #555;
        cursor: pointer; text-decoration: none;
        display: inline-flex; align-items: center;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .cpt-class-tab:hover  { border-color: #bbb; color: #111; }
    .cpt-class-tab.active { background: #0f0f0f; color: #fff; border-color: #0f0f0f; }

    /* Pagination */
    .cpt-pagination { margin-top: 12px; }
    .cpt-pagination .pagination { display: flex; gap: 6px; list-style: none; padding: 0; margin: 0; justify-content: center; }
    .cpt-pagination .page-item .page-link { padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px; color: #333; text-decoration: none; font-size: 14px; }
    .cpt-pagination .page-item.active .page-link { background: #1f7aec; color: #fff; border-color: #1f7aec; }
    .cpt-pagination .page-item.disabled .page-link { color: #999; cursor: not-allowed; }

    /* -”€-”€ Summary cards -”€-”€ */
    .cpt-summary-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }

    .cpt-stat-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .cpt-stat-label {
        font-size: 19px;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #999;
        margin: 0;
    }

    .cpt-stat-value {
        font-size: 38px;
        font-weight: 500;
        color: #111;
        line-height: 1.1;
        margin: 0;
    }

    .cpt-stat-sub {
        font-size: 19px;
        color: #aaa;
        margin: 0;
    }

    /* Pagination */
    .cpt-pagination { margin-top: 12px; }
    .cpt-pagination .pagination { display: flex; gap: 6px; list-style: none; padding: 0; margin: 0; justify-content: center; }
    .cpt-pagination .page-item .page-link { padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px; color: #333; text-decoration: none; font-size: 14px; }
    .cpt-pagination .page-item.active .page-link { background: #1f7aec; color: #fff; border-color: #1f7aec; }
    .cpt-pagination .page-item.disabled .page-link { color: #999; cursor: not-allowed; }

    /* -”€-”€ Card shared -”€-”€ */
    .cpt-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 1.35rem 1.5rem;
    }

    .cpt-section-title {
        font-size: 22px;
        font-weight: 500;
        color: #111;
        margin: 0 0 16px;
    }

    .cpt-empty {
        font-size: 19px;
        color: #aaa;
        padding: 14px 0;
    }

    /* -”€-”€ Module list -”€-”€ */
    .cpt-module-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 350px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .cpt-module-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .cpt-module-meta {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
    }

    .cpt-module-main {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .cpt-module-title {
        font-size: 19px;
        font-weight: 500;
        color: #111;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    .cpt-module-counts {
        font-size: 18px;
        color: #777;
        white-space: nowrap;
    }

    .cpt-bar-track {
        height: 8px;
        background: #f0f0f0;
        border-radius: 99px;
        overflow: hidden;
    }

    .cpt-bar-fill {
        height: 100%;
        border-radius: 99px;
        transition: width 0.4s ease;
    }

    .cpt-type-badge {
        display: inline-block;
        font-size: 16px;
        font-weight: 500;
        padding: 3px 9px;
        border-radius: 99px;
        background: #f3f3f3;
        color: #888;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .cpt-type-badge.assessment { background: #eeebff; color: #5b52c2; }

    /* -”€-”€ Student table -”€-”€ */
    .cpt-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 18px;
    }

    .cpt-table th {
        text-align: left;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #999;
        padding: 0 10px 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .cpt-table th:last-child { text-align: right; padding-right: 0; }

    .cpt-table td {
        padding: 12px 10px 12px 0;
        border-bottom: 1px solid #f7f7f7;
        vertical-align: middle;
    }

    .cpt-table td:last-child { text-align: right; padding-right: 0; }

    .cpt-table tbody tr:last-child td { border-bottom: none; }

    .cpt-student-name {
        font-size: 19px;
        font-weight: 500;
        color: #111;
    }

    .cpt-student-program {
        font-size: 18px;
        color: #aaa;
    }

    .cpt-pill {
        display: inline-block;
        font-size: 18px;
        font-weight: 500;
        padding: 4px 11px;
        border-radius: 99px;
    }

    .cpt-pill.high { background: #e1f5ee; color: #0f6e56; }
    .cpt-pill.mid  { background: #faeeda; color: #854f0b; }
    .cpt-pill.low  { background: #fcebeb; color: #a32d2d; }

    .cpt-mini-bar-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: flex-end;
    }

    .cpt-mini-bar-track {
        width: 96px;
        height: 7px;
        background: #f0f0f0;
        border-radius: 99px;
        overflow: hidden;
    }

    .cpt-mini-bar-fill {
        height: 100%;
        border-radius: 99px;
    }

    .cpt-mini-pct {
        font-size: 18px;
        font-weight: 500;
        color: #555;
        min-width: 46px;
        text-align: right;
    }

    /* -”€-”€ Responsive -”€-”€ */
    @media (max-width: 640px) {
        .cpt-summary-row { grid-template-columns: 1fr; }
    }

    @media (max-width: 1100px) {
        .cpt-main-grid { grid-template-columns: 1fr; }
        .cpt-module-list {
            max-height: none;
            overflow: visible;
            padding-right: 0;
        }
    }
</style>

<div class="cpt-wrap">

    {{-- Class switcher --}}
    @if($teacherClasses->count() > 1)
    <div class="cpt-switcher">
        <span class="cpt-switcher-label">Class:</span>
        <div class="cpt-class-tabs">
            @foreach($teacherClasses as $tc)
                <a href="{{ route('student.progress.tracker', $tc) }}"
                   class="cpt-class-tab {{ $tc->id === $class->id ? 'active' : '' }}">
                    {{ $tc->name }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

 {{-- Summary cards --}}
    @php
        $totalModules   = $modules->count();
        $totalStudents  = $students->count();

        $overallAvg = 0;
        if ($totalModules > 0) {
            $overallAvg = collect($moduleStats)->avg('avg_progress');
        }

        $overallAvgClass = $overallAvg >= 70 ? 'high' : ($overallAvg >= 40 ? 'mid' : 'low');
        $overallAvgColor = $overallAvg >= 70 ? '#1d9e75' : ($overallAvg >= 40 ? '#ef9f27' : '#e24b4a');
    @endphp

    <div class="cpt-summary-row">
        <div class="cpt-stat-card">
            <p class="cpt-stat-label">Total Modules</p>
            <p class="cpt-stat-value">{{ $totalModules }}</p>
            <p class="cpt-stat-sub">in this class</p>
        </div>
        <div class="cpt-stat-card">
            <p class="cpt-stat-label">Enrolled Students</p>
            <p class="cpt-stat-value">{{ $totalStudents }}</p>
            <p class="cpt-stat-sub">active members</p>
        </div>
        <div class="cpt-stat-card">
            <p class="cpt-stat-label">Avg. Progress</p>
            <p class="cpt-stat-value" style="color:{{ $overallAvgColor }}">{{ number_format($overallAvg, 1) }}%</p>
            <p class="cpt-stat-sub">across all modules</p>
        </div>
    </div>

    <div class="cpt-main-grid">
        {{-- Module completion overview --}}
        <div class="cpt-card">
            <p class="cpt-section-title">Module Completion Overview</p>
            @if($modules->isEmpty())
                <p class="cpt-empty">No modules in this class yet.</p>
            @else
                <div class="cpt-module-list">
                    @foreach($modules as $module)
                        @php
                            $stats    = $moduleStats[$module->id] ?? ['completed_count' => 0, 'total' => 0, 'avg_progress' => 0];
                            $avgPct   = (float) $stats['avg_progress'];
                            $barColor = $avgPct >= 70 ? '#1d9e75' : ($avgPct >= 40 ? '#ef9f27' : '#e24b4a');
                        @endphp
                        <div class="cpt-module-row">
                            <div class="cpt-module-meta">
                                <div class="cpt-module-main">
                                    <span class="cpt-module-title" title="{{ $module->title }}">{{ $module->title }}</span>
                                    @if($module->is_formal_assessment)
                                        <span class="cpt-type-badge assessment">Assessment</span>
                                    @else
                                        <span class="cpt-type-badge">Module</span>
                                    @endif
                                </div>
                                <span class="cpt-module-counts">
                                    {{ $stats['completed_count'] }} / {{ $stats['total'] }} completed &middot; avg {{ $avgPct }}%
                                </span>
                            </div>
                            <div class="cpt-bar-track">
                                <div class="cpt-bar-fill"
                                     style="width:{{ $avgPct }}%; background:{{ $barColor }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Student progress table --}}
        <div class="cpt-card">
            <p class="cpt-section-title">Student Progress</p>
            @if($students->isEmpty())
                <p class="cpt-empty">No students enrolled in this class yet.</p>
            @else
                <table class="cpt-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Program</th>
                            <th>Modules Done</th>
                            <th style="text-align:right">Avg Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $i => $student)
                            @php
                                $completedCount = 0;
                                $totalPct = 0;

                                foreach ($modules as $module) {
                                    $prog = $progressMap[$module->id][$student->id] ?? ['pct' => 0, 'completed' => false];
                                    if ($prog['completed']) {
                                        $completedCount++;
                                    }
                                    $totalPct += $prog['pct'];
                                }

                                $avgStudentPct = $totalModules > 0 ? round($totalPct / $totalModules, 1) : 0;
                                $pillClass = $avgStudentPct >= 70 ? 'high' : ($avgStudentPct >= 40 ? 'mid' : 'low');
                                $barColor  = $avgStudentPct >= 70 ? '#1d9e75' : ($avgStudentPct >= 40 ? '#ef9f27' : '#e24b4a');
                            @endphp
                            <tr>
                                <td style="color:#bbb; width:28px;">{{ $i + 1 }}</td>
                                <td>
                                    <div class="cpt-student-name">{{ $student->name }}</div>
                                </td>
                                <td>
                                    <span class="cpt-student-program">{{ $student->program ?? '-€”' }}</span>
                                </td>
                                <td>
                                    <span class="cpt-pill {{ $completedCount === $totalModules && $totalModules > 0 ? 'high' : ($completedCount > 0 ? 'mid' : 'low') }}">
                                        {{ $completedCount }} / {{ $totalModules }}
                                    </span>
                                </td>
                                <td>
                                    <div class="cpt-mini-bar-wrap">
                                        <div class="cpt-mini-bar-track">
                                            <div class="cpt-mini-bar-fill"
                                                 style="width:{{ $avgStudentPct }}%; background:{{ $barColor }};"></div>
                                        </div>
                                        <span class="cpt-mini-pct">{{ $avgStudentPct }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>
@endsection





