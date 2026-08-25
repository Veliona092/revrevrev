@extends('layouts.appEduc')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<style>
    .db-wrap { max-width: 100%; display: flex; flex-direction: column; gap: 22px; }

    /* ── Stat cards ── */
    .db-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .db-stat {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 22px 26px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .db-stat-icon {
        width: 50px; height: 50px; border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 19px; flex-shrink: 0;
    }

    .db-stat-label { font-size: 14px; color: #aaa; margin: 0 0 4px; font-weight: 500; letter-spacing: 0.03em; }
    .db-stat-val   { font-family: 'DM Sans', sans-serif; font-size: 34px; color: #111; line-height: 1; margin: 0; }

    /* ── Card base ── */
    .db-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        overflow: hidden;
    }

    .db-card-head {
        padding: 16px 24px;
        border-bottom: 1px solid #f3f3f3;
        display: flex; align-items: center; justify-content: space-between;
    }

    .db-card-title { font-size: 16px; font-weight: 500; color: #111; margin: 0; }
    .db-card-link  { font-size: 14px; color: #bbb; text-decoration: none; transition: color 0.15s; }
    .db-card-link:hover { color: #111; }
    .db-card-body  { padding: 18px 24px; }

    /* ── Bottom row ── */
    .db-bottom {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 20px;
        align-items: start;
    }

    /* ── Schedule ── */
    .db-sched {
        display: flex; align-items: center; justify-content: space-between;
        padding: 13px 16px; border-radius: 11px; margin-bottom: 10px; transition: opacity 0.15s;
    }
    .db-sched:last-child { margin-bottom: 0; }
    .db-sched:hover { opacity: 0.85; }
    .db-sched.blue  { background: #eff6ff; }
    .db-sched.amber { background: #fff7ed; }
    .db-sched-left  { display: flex; align-items: center; gap: 12px; }
    .db-sched-icon  { width: 38px; height: 38px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .db-sched-icon.blue  { background: #dbeafe; color: #2563eb; }
    .db-sched-icon.amber { background: #fed7aa; color: #c2410c; }
    .db-sched-name  { font-size: 16px; font-weight: 500; color: #111; margin: 0 0 2px; }
    .db-sched-sub   { font-size: 14px; color: #aaa; margin: 0; }
    .db-sched-sub.urgent { color: #e24b4a; font-weight: 500; }
    .db-join-btn    { display: inline-flex; align-items: center; justify-content: center; min-width: 190px; height: 36px; padding: 0 15px; background: #0f0f0f; color: #fff; border: none; border-radius: 9px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer; white-space: nowrap; text-decoration: none; transition: background 0.15s; }
    .db-join-btn:hover { background: #333; }

    /* ── Assignments ── */
    .db-assign { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f7f7f7; }
    .db-assign:last-child { border-bottom: none; }
    .db-assign-left { display: flex; align-items: center; gap: 12px; }
    .db-assign-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .db-assign-name { font-size: 16px; font-weight: 500; color: #111; margin: 0; }
    .db-badge { font-size: 14px; font-weight: 500; padding: 4px 11px; border-radius: 99px; white-space: nowrap; }
    .db-badge.warning { background: #faeeda; color: #854f0b; }
    .db-badge.success { background: #e1f5ee; color: #0f6e56; }
    .db-badge.danger  { background: #fcebeb; color: #a32d2d; }

    /* ── Progress ── */
    .db-prog { margin-bottom: 16px; }
    .db-prog:last-of-type { margin-bottom: 0; }
    .db-prog-label { display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 8px; }
    .db-prog-subject { color: #555; font-weight: 500; }
    .db-prog-val { font-weight: 500; }
    .db-prog-val.green { color: #1d9e75; }
    .db-prog-val.red   { color: #e24b4a; }
    .db-bar-track { height: 8px; background: #f3f3f3; border-radius: 99px; overflow: hidden; }
    .db-bar-fill  { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
    .db-detail-link { display: block; text-align: right; margin-top: 14px; font-size: 14px; color: #bbb; text-decoration: none; transition: color 0.15s; }
    .db-detail-link:hover { color: #111; }

    /* ── Announcements ── */
    .db-announce { display: flex; align-items: flex-start; gap: 12px; padding: 11px 0; border-bottom: 1px solid #f7f7f7; }
    .db-announce:last-child { border-bottom: none; }
    .db-announce-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .db-announce-label { font-size: 14px; color: #bbb; margin: 0 0 2px; }
    .db-announce-text  { font-size: 16px; color: #333; font-weight: 500; margin: 0; }

    /* ── Messages ── */
    .db-msg { display: flex; align-items: flex-start; gap: 12px; }
    .db-msg-avatar { width: 40px; height: 40px; border-radius: 50%; background: #0f0f0f; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; flex-shrink: 0; }
    .db-msg-name { font-size: 16px; font-weight: 500; color: #111; margin: 0 0 3px; }
    .db-msg-body { font-size: 15px; color: #888; margin: 0; font-style: italic; }

    /* ── Right panel ── */
    .db-right { display: flex; flex-direction: column; gap: 20px; }

    /* ── Pagination (matches progress tracker) ── */
    .pt-pager {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        margin-top: 12px;
    }
    .pt-pager-info {
        font-size: 14px;
        color: #9a9185;
    }
    .pt-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 72px;
        height: 34px;
        padding: 0 14px;
        background: #fff;
        border: 1px solid #e8e4dc;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        color: #4b5563;
        cursor: pointer;
        text-decoration: none;
    }
    .pt-page-btn:hover {
        background: #f8f6f2;
        color: #1f2937;
    }
    .pt-page-btn.disabled {
        opacity: 0.45;
        pointer-events: none;
    }

    @media (max-width: 860px) {
        .db-stats  { grid-template-columns: 1fr 1fr; }
        .db-bottom { grid-template-columns: 1fr; }
    }

    @media (max-width: 480px) {
        .db-stats { grid-template-columns: 1fr; }
    }
</style>

<div class="db-wrap">

    {{-- ── Stat cards ── --}}
    <div class="db-stats">
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#eff6ff;color:#2563eb;">
                <i class="fas fa-book-open"></i>
            </div>
            <div>
                <p class="db-stat-label">Enrolled classes</p>
                <p class="db-stat-val">{{ $enrolledClasses }}</p>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#faeeda;color:#854f0b;">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <p class="db-stat-label">Pending assignments</p>
                <p class="db-stat-val">{{ $pendingAssignments }}</p>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-icon" style="background:#e1f5ee;color:#0f6e56;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <p class="db-stat-label">Overall avg</p>
                <p class="db-stat-val">{{ $overallAvg }}%</p>
            </div>
        </div>
    </div>

    {{-- ── Bottom row ── --}}
    <div class="db-bottom">

        {{-- Left — Schedule + Assignments ── --}}
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
                        <p style="font-size:13px;color:#aaa;text-align:center;padding:16px 0;">No pre-assessments yet.</p>
                    @endforelse
                    @if($upcomingQuizzesCount > 3)
                        <div style="text-align:right;margin-top:12px;">
                            <a href="{{ route('student.preassessments') }}" style="font-size:14px;color:#9a8098;text-decoration:none;">View All ({{ $upcomingQuizzesCount }} items) →</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="db-card">
                <div class="db-card-head">
                    <p class="db-card-title">Assessments</p>
                    <a href="{{ route('assessment') }}" class="db-card-link">View All ({{ $totalAssessments }} items) →</a>
                </div>
                <div class="db-card-body">
                    @forelse($pendingQuizModules as $module)
                        <div class="db-assign">
                            <div class="db-assign-left">
                                <div class="db-assign-icon" style="background:#fff7ed;color:#c2410c;"><i class="fas fa-file-alt"></i></div>
                                <p class="db-assign-name">{{ $module->title }}</p>
                            </div>
                            <span class="db-badge warning">Pending</span>
                        </div>
                    @empty
                    @endforelse
                    @forelse($gradedAttempts as $attempt)
                        <div class="db-assign">
                            <div class="db-assign-left">
                                <div class="db-assign-icon" style="background:#e1f5ee;color:#0f6e56;"><i class="fas fa-check"></i></div>
                                <p class="db-assign-name">{{ $attempt->module->title }}</p>
                            </div>
                            <span class="db-badge success">{{ $attempt->percentage }}% · Passed</span>
                        </div>
                    @empty
                    @endforelse
                    @forelse($submittedAttempts as $attempt)
                        <div class="db-assign">
                            <div class="db-assign-left">
                                <div class="db-assign-icon" style="background:#fcebeb;color:#a32d2d;"><i class="fas fa-paper-plane"></i></div>
                                <p class="db-assign-name">{{ $attempt->module->title }}</p>
                            </div>
                            <span class="db-badge danger">{{ $attempt->percentage }}% · Failed</span>
                        </div>
                    @empty
                    @endforelse
                    @if($pendingQuizModules->isEmpty() && $gradedAttempts->isEmpty() && $submittedAttempts->isEmpty())
                        <p style="font-size:13px;color:#aaa;text-align:center;padding:8px 0;">No assessment activity yet.</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right panel ── --}}
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
                        <p style="font-size:13px;color:#aaa;text-align:center;padding:8px 0;">No quiz data yet.</p>
                    @endforelse
                    <a href="{{ route('student.classes') }}" class="db-detail-link">View all classes →</a>
                </div>
            </div>

<div class="db-card">
                <div class="db-card-head"><p class="db-card-title">Announcements</p></div>
                <div class="db-card-body">
                    @forelse($announcements as $announcement)
                        <div class="db-announce db-announce-clickable" data-announcement-id="{{ $announcement->id }}" onclick="toggleAnnounceThread({{ $announcement->id }})" style="cursor:pointer;">
                            <div class="db-announce-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-bell"></i></div>
                            <div style="flex:1;">
                                <p class="db-announce-label">{{ $announcement->class->name }} · {{ $announcement->created_at->diffForHumans() }}</p>
                                <p class="db-announce-text">{{ Str::limit($announcement->message, 80) }}</p>
                            </div>
                            <i class="fas fa-chevron-down" style="color:#ccc;font-size:12px;margin-top:4px;"></i>
                        </div>
                        <div id="thread-{{ $announcement->id }}" class="db-thread" style="display:none;padding:10px 0 14px 46px;"></div>
                    @empty
                        <p style="font-size:13px;color:#aaa;text-align:center;padding:8px 0;">No announcements yet.</p>
                    @endforelse
                </div>
            </div>

           

        </div>

    </div>

</div>
@endsection
@section('scripts')
<style>
    .db-comment { margin-bottom: 10px; }
    .db-comment-body { font-size: 14px; color: #333; background:#f7f7f7; padding:8px 12px; border-radius:10px; display:inline-block; }
    .db-comment-meta { font-size: 12px; color: #aaa; margin: 3px 0 4px; }
    .db-comment-reply-btn { font-size: 12px; color: #2563eb; cursor: pointer; border: none; background: none; padding: 0; }
    .db-comment-children { margin-left: 24px; margin-top: 8px; border-left: 2px solid #f0f0f0; padding-left: 12px; }
    .db-comment-form { display: flex; gap: 8px; margin-top: 8px; }
    .db-comment-form input { flex: 1; border: 1px solid #e2e2e2; border-radius: 8px; padding: 6px 10px; font-size: 13px; }
    .db-comment-form button { border: none; background: #0f0f0f; color: #fff; border-radius: 8px; padding: 6px 14px; font-size: 13px; cursor: pointer; }
</style>
<script>
    const loadedThreads = new Set();

    function toggleAnnounceThread(id) {
        const panel = document.getElementById('thread-' + id);
        const isOpen = panel.style.display !== 'none';
        panel.style.display = isOpen ? 'none' : 'block';

        if (!isOpen && !loadedThreads.has(id)) {
            loadThread(id);
        }
    }

    function loadThread(id) {
        const panel = document.getElementById('thread-' + id);
        panel.innerHTML = '<p style="font-size:12px;color:#bbb;">Loading...</p>';

        fetch(`/announcements/${id}/comments`)
            .then(r => r.json())
            .then(data => {
                loadedThreads.add(id);
                renderThread(id, data.comments);
            })
            .catch(() => {
                panel.innerHTML = '<p style="font-size:12px;color:#e24b4a;">Failed to load comments.</p>';
            });
    }

    function renderThread(announcementId, comments) {
        const panel = document.getElementById('thread-' + announcementId);
        panel.innerHTML = '';
        panel.appendChild(buildCommentList(comments));
        panel.appendChild(buildReplyForm(announcementId, null));
    }

    function buildCommentList(comments) {
        const wrap = document.createElement('div');
        comments.forEach(c => wrap.appendChild(buildCommentNode(c)));
        return wrap;
    }

    function buildCommentNode(comment) {
        const node = document.createElement('div');
        node.className = 'db-comment';
        node.innerHTML = `
            <div class="db-comment-body">${escapeHtml(comment.body)}</div>
            <div class="db-comment-meta">
                ${escapeHtml(comment.author)} · ${comment.created_human}
                <button type="button" class="db-comment-reply-btn" onclick="toggleReplyForm(this, ${comment.id})">Reply</button>
            </div>
            <div class="db-comment-reply-slot"></div>
            <div class="db-comment-children"></div>
        `;
        const childrenWrap = node.querySelector('.db-comment-children');
        comment.replies.forEach(r => childrenWrap.appendChild(buildCommentNode(r)));
        return node;
    }

    function toggleReplyForm(btn, parentId) {
        const slot = btn.closest('.db-comment').querySelector('.db-comment-reply-slot');
        if (slot.childElementCount > 0) {
            slot.innerHTML = '';
            return;
        }
        const announcementId = btn.closest('.db-thread').id.replace('thread-', '');
        slot.appendChild(buildReplyForm(announcementId, parentId));
    }

    function buildReplyForm(announcementId, parentId) {
        const form = document.createElement('form');
        form.className = 'db-comment-form';
        form.innerHTML = `
            <input type="text" placeholder="Write a ${parentId ? 'reply' : 'comment'}..." maxlength="1000" required>
            <button type="submit">Post</button>
        `;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const input = form.querySelector('input');
            const body = input.value.trim();
            if (!body) return;

            fetch(`/announcements/${announcementId}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ body, parent_id: parentId }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadedThreads.delete(Number(announcementId));
                    loadThread(announcementId);
                }
            });
        });
        return form;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>
@endsection