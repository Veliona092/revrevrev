@extends('layouts.appAdmin')

@section('title', 'Admin Dashboard')
@section('page-heading', 'Admin Dashboard')

@section('content')
<style>
    .ad-wrap {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .ad-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .ad-stat {
        background: #fff;
        border: 1px solid #ece7dc;
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .ad-stat.pending-warn {
        background: #fff5e4;
        border-color: #f8ce88;
    }

    .ad-stat.pending-critical {
        background: #fff0f0;
        border-color: #f2b9b9;
    }

    .ad-stat-label {
        margin: 0;
        font-size: 13px;
        color: #8e8678;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .ad-stat-value {
        margin: 6px 0 0;
        font-size: 36px;
        color: #1f2937;
        line-height: 1;
        font-weight: 500;
    }

    .ad-stat-link {
        margin-top: 8px;
        display: inline-block;
        font-size: 15px;
        color: #9a6700;
        text-decoration: none;
        font-weight: 500;
    }

    .ad-stat-link:hover {
        text-decoration: underline;
    }

    .ad-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 16px;
    }

    .ad-grid-equal {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .ad-card {
        background: #fff;
        border: 1px solid #ece7dc;
        border-radius: 14px;
        overflow: hidden;
    }

    .ad-card-head {
        padding: 16px 20px;
        border-bottom: 1px solid #f2ede3;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .ad-card-title {
        margin: 0;
        font-size: 20px;
        font-weight: 500;
        color: #1f2937;
    }

    .ad-card-link {
        font-size: 15px;
        color: #9a6700;
        text-decoration: none;
        font-weight: 500;
    }

    .ad-card-link:hover {
        text-decoration: underline;
    }

    .ad-card-body {
        padding: 18px 20px;
    }

    .ad-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 16px;
    }

    .ad-table thead th {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #ada391;
        text-align: left;
        padding: 11px 12px;
        border-bottom: 1px solid #f2ede3;
    }

    .ad-table tbody td {
        padding: 13px 12px;
        border-bottom: 1px solid #f7f4ed;
        color: #3f3a33;
        vertical-align: middle;
    }

    .ad-table tbody tr:last-child td {
        border-bottom: none;
    }

    .ad-status-dot {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 6px;
    }

    .dot-green { background: #1d9e75; }
    .dot-blue { background: #2563eb; }
    .dot-amber { background: #ef9f27; }
    .dot-purple { background: #7f77dd; }

    .ad-feed {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ad-feed-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        border-bottom: 1px solid #f7f4ed;
        padding-bottom: 12px;
    }

    .ad-feed-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .ad-feed-label {
        margin: 0;
        font-size: 18px;
        color: #2f2a22;
    }

    .ad-feed-time {
        margin: 3px 0 0;
        font-size: 15px;
        color: #a09583;
    }

    .ad-breakdown {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .ad-breakdown-row {
        border: 1px solid #eee7d7;
        border-radius: 12px;
        padding: 10px 12px;
        background: #faf8f3;
    }

    .ad-breakdown-role {
        margin: 0;
        font-size: 18px;
        font-weight: 500;
        color: #2f2a22;
    }

    .ad-breakdown-meta {
        margin: 5px 0 0;
        font-size: 15px;
        color: #8f8574;
    }

    .ad-empty-success {
        border: 1px solid #c9e8d4;
        background: #e9f8ef;
        color: #17653f;
        border-radius: 12px;
        padding: 14px;
        font-size: 16px;
        text-align: center;
    }

    .badge-fail-count {
        background: #f5e3e3;
        color: #9e2f2e;
        padding: 4px 12px;
        border-radius: 99px;
        font-weight: 700;
        font-size: 14px;
    }

    @media (max-width: 1100px) {
        .ad-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ad-grid, .ad-grid-equal {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .ad-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    $pendingTone = $pendingApprovals > 10 ? 'pending-critical' : ($pendingApprovals > 0 ? 'pending-warn' : '');
@endphp

<div class="ad-wrap">
    <section class="ad-stats">
        <article class="ad-stat">
            <div>
                <p class="ad-stat-label">Total Students</p>
                <p class="ad-stat-value">{{ number_format($totalStudents) }}</p>
            </div>
            <i class="fas fa-user-graduate" style="color:#8f8574;"></i>
        </article>

        <article class="ad-stat">
            <div>
                <p class="ad-stat-label">Total Teachers</p>
                <p class="ad-stat-value">{{ number_format($totalTeachers) }}</p>
            </div>
            <i class="fas fa-chalkboard-teacher" style="color:#8f8574;"></i>
        </article>

        <article class="ad-stat {{ $pendingTone }}">
            <div>
                <p class="ad-stat-label">Pending Approvals</p>
                <p class="ad-stat-value">{{ number_format($pendingApprovals) }}</p>
                <a href="{{ route('admin.approvals') }}" class="ad-stat-link">Review now →</a>
            </div>
            <i class="fas fa-user-clock" style="color:#9a6700;"></i>
        </article>

        <article class="ad-stat">
            <div>
                <p class="ad-stat-label">Total Active Classes</p>
                <p class="ad-stat-value">{{ number_format($totalActiveClasses) }}</p>
            </div>
            <i class="fas fa-school" style="color:#8f8574;"></i>
        </article>
    </section>

    <section class="ad-card">
        <header class="ad-card-head">
            <h3 class="ad-card-title">Pending Approvals</h3>
            <a href="{{ route('admin.approvals') }}" class="ad-card-link">View all pending →</a>
        </header>
        <div class="ad-card-body">
            @if($pendingUsers->isEmpty())
                <div class="ad-empty-success">All caught up. No pending approvals right now.</div>
            @else
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID Number</th>
                            <th>Role</th>
                            <th>Signed Up</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingUsers as $pendingUser)
                            @continue(strtolower($pendingUser->role ?? '') === 'superadmin')
                            <tr>
                                <td>{{ $pendingUser->name ?? '—' }}</td>
                                <td>{{ $pendingUser->idnumber ?? '—' }}</td>
                                <td>{{ ucfirst($pendingUser->role ?? 'student') }}</td>
                                <td>{{ $pendingUser->created_at?->diffForHumans() }}</td>
                                <td>
                                    <a href="{{ route('admin.approvals', ['q' => $pendingUser->idnumber]) }}" class="ad-card-link">Open in approvals</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="ad-grid">
        <article class="ad-card">
            <header class="ad-card-head">
                <h3 class="ad-card-title">Platform Activity</h3>
            </header>
            <div class="ad-card-body">
                <div class="ad-feed">
                    @forelse($platformActivity as $event)
                        <div class="ad-feed-item">
                            <span class="ad-status-dot {{ 'dot-'.$event['dot'] }}"></span>
                            <div>
                                <p class="ad-feed-label">{{ $event['label'] }}</p>
                                <p class="ad-feed-time">{{ $event['occurred_at']?->diffForHumans() ?? 'Just now' }}</p>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:16px;color:#a09583;">No recent platform activity.</p>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="ad-card">
            <header class="ad-card-head">
                <h3 class="ad-card-title">User Breakdown</h3>
            </header>
            <div class="ad-card-body" style="display:grid;grid-template-columns: 1fr;gap:16px;">
                <div style="min-height:280px;display:flex;align-items:center;justify-content:center;">
                    <canvas id="adminRoleChart"></canvas>
                </div>

                <div class="ad-breakdown">
                    @foreach($roleBreakdown as $entry)
                        @continue(strtolower($entry['role'] ?? '') === 'superadmin')
                        <div class="ad-breakdown-row">
                            <p class="ad-breakdown-role">{{ ucfirst($entry['role']) }}</p>
                            <p class="ad-breakdown-meta">
                                Active: {{ $entry['active'] }} · Pending: {{ $entry['pending'] }} · Rejected: {{ $entry['rejected'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </section>

    {{-- SIDE-BY-SIDE: Failed Students per Class & Failed Students per Program --}}
    <section class="ad-grid-equal">
        <article class="ad-card">
            <header class="ad-card-head">
                <h3 class="ad-card-title">Mock Board Failures per Class</h3>
            </header>
            <div class="ad-card-body">
                @if(!isset($failedStudentsByClassSection) || collect($failedStudentsByClassSection)->isEmpty())
                    <p style="font-size:16px;color:#a09583;">No failed student records available.</p>
                @else
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Failed Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedStudentsByClassSection as $row)
                                <tr>
                                    <td style="font-weight: 500;">{{ $row->class_name ?? $row['class_name'] ?? '—' }}</td>
                                    <td>
                                        <span class="badge-fail-count">
                                            {{ $row->failed_count ?? $row['failed_count'] ?? 0 }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </article>

        <article class="ad-card">
            <header class="ad-card-head">
                <h3 class="ad-card-title">Mock Board Failures per Program</h3>
            </header>
            <div class="ad-card-body">
                @if(!isset($failedStudentsByProgram) || collect($failedStudentsByProgram)->isEmpty())
                    <p style="font-size:16px;color:#a09583;">No failed student records available.</p>
                @else
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Failed Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedStudentsByProgram as $row)
                                <tr>
                                    <td style="font-weight: 500;">{{ ucfirst($row->program ?? $row['program'] ?? '—') }}</td>
                                    <td>
                                        <span class="badge-fail-count">
                                            {{ $row->failed_count ?? $row['failed_count'] ?? 0 }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </article>
    </section>

    <section class="ad-grid">
        <article class="ad-card">
            <header class="ad-card-head">
                <h3 class="ad-card-title">Students per Class</h3>
            </header>
            <div class="ad-card-body">
                @if($classesBreakdown->isEmpty())
                    <p style="font-size:16px;color:#a09583;">No class data available.</p>
                @else
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Year Level</th>
                                <th>School Year</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classesBreakdown as $row)
                                @php
                                    $yr = $row['year_level'] ?? null;
                                    $yrLabel = match((string)$yr) {
                                        '1' => '1st Year',
                                        '2' => '2nd Year',
                                        '3' => '3rd Year',
                                        '4' => '4th Year',
                                        default => $yr ? $yr . ' Year' : '—'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $yrLabel }}</td>
                                    <td>{{ $row['school_year'] ?? '—' }}</td>
                                    <td>{{ $row['student_count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </article>

        <article class="ad-card">
            <header class="ad-card-head">
                <h3 class="ad-card-title">Enrollment per Program</h3>
            </header>
            <div class="ad-card-body">
                @if($programEnrollment->isEmpty())
                    <p style="font-size:16px;color:#a09583;">No enrollment data available.</p>
                @else
                    <div class="ad-breakdown">
                        @foreach($programEnrollment as $row)
                            <div class="ad-breakdown-row">
                                <p class="ad-breakdown-role">{{ ucfirst($row['program']) }}</p>
                                <p class="ad-breakdown-meta">{{ $row['total'] }} students enrolled</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </article>
    </section>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const ctx = document.getElementById('adminRoleChart');
        if (!ctx) {
            return;
        }

        const roleRows = @json($roleDistribution).filter(row => (row.role || '').toLowerCase() !== 'superadmin');
        const labels = roleRows.map(row => (row.role || 'unknown').replace(/\b\w/g, c => c.toUpperCase()));
        const values = roleRows.map(row => Number(row.total || 0));

        if (!values.some(v => v > 0)) {
            return;
        }

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#1d9e75', '#f59e0b', '#2563eb', '#7f77dd', '#ef9f27', '#e24b4a'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { family: 'DM Sans', size: 18 },
                            color: '#6f6658',
                        },
                    },
                },
            },
        });
    })();
</script>
@endsection