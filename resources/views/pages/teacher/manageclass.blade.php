@extends('layouts.appTeach')

@section('title', 'Class Management')
@section('page-heading', 'Class Management')

@section('header-actions')
    <button type="button" class="rv-btn" id="deleteModeToggle" onclick="toggleDeleteMode()">
        <i class="fas fa-trash-alt"></i> Delete Classes
    </button>
    <button type="button" class="rv-btn rv-btn-primary" onclick="openCreateDialog()">
        <i class="fas fa-plus"></i> New Class
    </button>
@endsection

@section('content')

<style>
    @media (max-width: 640px) {
        .lecture-content-upload-row {
            grid-template-columns: 1fr !important;
        }
        .lecture-remove-upload {
            width: 100%;
        }
    }

    /* ── Stats row ── */
    .mc-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .mc-stat {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 12px;
        padding: 16px 18px;
    }
    .mc-stat-label {
        font-size: 17px;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #bbb;
        margin-bottom: 6px;
    }
    .mc-stat-val {
        font-family: 'DM Sans', sans-serif;
        font-size: 36px;
        color: #111;
        line-height: 1;
    }

    /* ── Class cards grid ── */
    .mc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 14px;
    }
    .mc-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        transition: box-shadow 0.15s, border-color 0.15s;
        animation: mc-fadein 0.2s ease both;
    }
    @keyframes mc-fadein {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .mc-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border-color: #d8d8d8;
    }
    .mc-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }
    .mc-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #f3f3f3;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #888;
        flex-shrink: 0;
    }
    .mc-card-badge {
        font-size: 17px;
        font-weight: 500;
        padding: 3px 9px;
        border-radius: 99px;
        background: #f3f3f3;
        color: #666;
    }
    .mc-card-name {
        font-size: 18px;
        font-weight: 500;
        color: #111;
        margin: 0 0 3px;
        line-height: 1.3;
    }
    .mc-card-meta {
        font-size: 17px;
        color: #aaa;
    }
    .mc-card-divider {
        height: 1px;
        background: #f3f3f3;
    }
    .mc-card-stats {
        display: flex;
        gap: 16px;
    }
    .mc-card-stat-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .mc-card-stat-num {
        font-size: 20px;
        font-weight: 500;
        color: #111;
    }
    .mc-card-stat-lbl {
        font-size: 17px;
        color: #bbb;
    }
    .mc-card-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
    }
    .mc-action-btn {
        flex: 1;
        height: 38px;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 17px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.12s, border-color 0.12s, color 0.12s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #ebebeb;
        background: #fafafa;
        color: #444;
    }
    .mc-action-btn:hover {
        background: #f0f0f0;
        border-color: #d8d8d8;
        color: #111;
    }
    .mc-action-btn.accent {
        background: #eef5f3;
        border-color: #c6e0d9;
        color: #245E55;
    }
    .mc-action-btn.accent:hover {
        background: #e0eee9;
        border-color: #a8cfc4;
    }

    /* ── Delete mode ── */
    .class-delete-action {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #f0cccc;
    }
    .delete-class-btn {
        width: 100%;
        height: 34px;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #f0cccc;
        background: #fff5f5;
        color: #dc2626;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .delete-class-btn:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }
    #deleteModeToggle.active {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #dc2626;
    }

    /* ── Empty state ── */
    .mc-empty {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 56px 24px;
        text-align: center;
        grid-column: 1 / -1;
    }
    .mc-empty-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: #f3f3f3;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: #bbb;
        margin: 0 auto 16px;
    }

    /* ── Tabs & Visibility styles ── */
    .rv-tabs {
        display: flex;
        gap: 4px;
        border-bottom: 1px solid #DDD8CF;
        margin-bottom: 24px;
    }
    .rv-tab {
        padding: 10px 18px;
        font-family: 'DM Sans', sans-serif;
        font-size: 15px;
        font-weight: 500;
        color: #8a8580;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        margin-bottom: -1px;
        transition: color 0.15s, border-color 0.15s;
    }
    .rv-tab:hover {
        color: #2D2D2B;
    }
    .rv-tab.active {
        color: #245E55;
        border-bottom-color: #245E55;
        font-weight: 600;
    }
    .rv-tab-panel {
        display: none;
    }
    .rv-tab-panel.active {
        display: block;
    }

    .vis-toggle {
        display: flex;
        border: 1px solid #DDD8CF;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 10px;
    }
    .vis-opt {
        flex: 1;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 500;
        background: #F7F2E9;
        border: none;
        cursor: pointer;
        color: #8a8580;
        text-align: center;
        transition: background 0.15s, color 0.15s;
    }
    .vis-opt.active {
        background: #245E55;
        color: #fff;
    }
    .vis-student-picker {
        display: none;
        background: #fff;
        border: 1px solid #DDD8CF;
        border-radius: 8px;
        padding: 12px;
        margin-top: 8px;
    }
    .vis-search-wrap {
        position: relative;
    }
    .vis-search-wrap input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #DDD8CF;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
    }
    .vis-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #DDD8CF;
        border-radius: 6px;
        max-height: 160px;
        overflow-y: auto;
        z-index: 100;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: none;
    }
    .vis-result-item {
        padding: 8px 12px;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .vis-result-item:hover {
        background: #f3f3f3;
    }
    .vis-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .vis-chip {
        background: #eef5f3;
        border: 1px solid #c6e0d9;
        color: #245E55;
        border-radius: 99px;
        padding: 2px 10px;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .vis-chip-remove {
        cursor: pointer;
        font-weight: bold;
    }
    .vis-empty-hint {
        font-size: 12px;
        color: #aaa;
        margin-top: 6px;
    }

    /* Select2 overrides */
    .select2-container--default .select2-selection--multiple {
        background-color: #F7F2E9;
        border: 1px solid #DDD8CF;
        border-radius: 8px;
        min-height: 42px;
        padding: 2px 6px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #245E55;
    }
</style>

{{-- ── Stats ── --}}
<div class="mc-stats">
    <div class="mc-stat">
        <div class="mc-stat-label">Total Classes</div>
        <div class="mc-stat-val">{{ $classes->count() }}</div>
    </div>
    <div class="mc-stat">
        <div class="mc-stat-label">Total Students</div>
        <div class="mc-stat-val">{{ $classes->sum(fn($c) => $c->students_count ?? $c->users_count ?? 0) }}</div>
    </div>
</div>

{{-- ── Grid ── --}}
<div class="mc-grid" id="classGrid">
    @forelse($classes as $i => $class)
        <div class="mc-card" style="animation-delay: {{ $i * 40 }}ms">
            <div class="mc-card-top">
                <div class="mc-card-icon"><i class="fas fa-chalkboard"></i></div>
                @if($class->school_year)
                    <span class="mc-card-badge">{{ $class->school_year }}</span>
                @endif
            </div>

            <div>
                <p class="mc-card-name">{{ $class->name }}</p>
                <p class="mc-card-meta">
                    {{ $class->code ? 'Code: ' . $class->code : 'No code' }}
                    {{ $class->description ? ' ' . Str::limit($class->description, 40) : '' }}
                </p>
            </div>

            <div class="mc-card-divider"></div>

            <div class="mc-card-stats">
                <div class="mc-card-stat-item">
                    <span class="mc-card-stat-num">{{ $class->students_count ?? $class->users_count ?? 0 }}</span>
                    <span class="mc-card-stat-lbl">Students</span>
                </div>
            </div>

            <div class="mc-card-actions">
                <button type="button" class="mc-action-btn" onclick="openStudentsDrawer({{ $class->id }}, '{{ addslashes($class->name) }}')">
                    <i class="fas fa-users"></i> Students
                </button>
                <button type="button" class="mc-action-btn accent" onclick="openModulesDrawer({{ $class->id }}, '{{ addslashes($class->name) }}')">
                    <i class="fas fa-folder-open"></i> Modules
                </button>
            </div>

            {{-- Delete class button (shown in delete mode) --}}
            <div class="class-delete-action" style="display: none;">
                <form method="POST" action="{{ route('classes.destroy', $class) }}" class="delete-class-form" style="display: contents;" data-class-name="{{ $class->name }}">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="delete-class-btn" onclick="openDeleteClassConfirm(this)">
                        <i class="fas fa-trash-alt"></i> Delete Class
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="mc-empty">
            <div class="mc-empty-icon"><i class="fas fa-chalkboard"></i></div>
            <div style="font-size: 16px; color: #888; margin-bottom: 14px;">No classes yet. Create your first class to get started.</div>
            <button type="button" class="rv-btn rv-btn-primary" onclick="openCreateDialog()">
                <i class="fas fa-plus"></i> Create Class
            </button>
        </div>
    @endforelse
</div>

@endsection

@section('drawers')

<style>
    /* Confirmation Modals */
    .delete-confirm-overlay, .mc-confirm-overlay, .announcement-edit-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 100000;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .delete-confirm-overlay[aria-hidden="false"], 
    .mc-confirm-overlay[aria-hidden="false"], 
    .announcement-edit-overlay[aria-hidden="false"] {
        display: flex !important;
        opacity: 1;
    }
    .delete-confirm-modal, .mc-confirm-modal, .announcement-edit-modal {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        padding: 24px 28px;
        max-width: 440px;
        width: 90%;
        text-align: center;
    }
    .delete-confirm-title, .mc-confirm-title {
        font-size: 20px;
        font-weight: 600;
        color: #111827;
        margin: 0 0 10px 0;
    }
    .delete-confirm-text, .mc-confirm-text {
        font-size: 15px;
        color: #6b7280;
        margin: 0 0 20px 0;
        line-height: 1.5;
    }
    .delete-confirm-actions, .mc-confirm-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .delete-confirm-actions button, .mc-confirm-actions button {
        flex: 1;
        height: 42px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        border: none;
    }
    .delete-confirm-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    .delete-confirm-continue {
        background: #dc2626;
        color: #ffffff;
    }

    /* Native HTML5 Dialog Drawers */
    dialog.rv-dialog {
        padding: 0;
        border: none;
        border-radius: 0;
        margin: 0 0 0 auto;
        width: 480px;
        max-width: 95vw;
        height: 100vh;
        max-height: 100vh;
        background: transparent;
        outline: none;
    }
    dialog.rv-dialog::backdrop {
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(2px);
    }
    .rv-dialog-panel {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #FAF7F2;
        border-left: 1px solid #DDD8CF;
        overflow: hidden;
        box-shadow: -8px 0 36px rgba(0, 0, 0, 0.18);
    }
</style>

{{-- ── DIALOG: Create Class ── --}}
<dialog id="dialogCreate" class="rv-dialog">
    <div class="rv-dialog-panel">
        <div class="rv-drawer-head">
            <div>
                <div class="rv-drawer-title">New Class</div>
                <div class="rv-drawer-subtitle">Fill in the details below to create a class.</div>
            </div>
            <button type="button" class="rv-drawer-close" onclick="closeCreateDialog()">&#x2715;</button>
        </div>

        <div class="rv-drawer-body">
            @if ($errors->any())
                <div style="background: #fee2e2; border: 1px solid #f87171; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #991b1b; font-size: 14px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('classes.store') }}" id="createClassForm">
                @csrf

                <div class="rv-form-group">
                    <label class="rv-label">Class Name <span style="color:#e24b4a">*</span></label>
                    <input type="text" name="name" class="rv-input" required placeholder="e.g. Grade 10 - Section A" value="{{ old('name') }}">
                </div>

                <div class="rv-form-group">
                    <label class="rv-label">Code (optional)</label>
                    <input type="text" name="code" class="rv-input" placeholder="e.g. 10A-2026" value="{{ old('code') }}">
                </div>

                <div class="rv-form-group">
                    <label class="rv-label">School Year</label>
                    <input type="text" name="school_year" class="rv-input" placeholder="e.g. 2026" value="{{ old('school_year', date('Y')) }}">
                </div>

                <div class="rv-form-group">
                    <label class="rv-label">Year Level (optional)</label>
                    <select name="year_level" class="rv-input">
                        <option value="">Select Year Level</option>
                        <option value="1" {{ old('year_level') == '1' ? 'selected' : '' }}>1st Year</option>
                        <option value="2" {{ old('year_level') == '2' ? 'selected' : '' }}>2nd Year</option>
                        <option value="3" {{ old('year_level') == '3' ? 'selected' : '' }}>3rd Year</option>
                        <option value="4" {{ old('year_level') == '4' ? 'selected' : '' }}>4th Year</option>
                    </select>
                </div>

                <div class="rv-form-group">
                    <label class="rv-label">Description (optional)</label>
                    <textarea name="description" class="rv-textarea" placeholder="Brief description of the class...">{{ old('description') }}</textarea>
                </div>
            </form>
        </div>

        <div class="rv-drawer-footer">
            <button type="button" class="rv-btn rv-btn-secondary" onclick="closeCreateDialog()">Cancel</button>
            <button type="button" class="rv-btn rv-btn-primary" onclick="document.getElementById('createClassForm').submit()">
                <i class="fas fa-plus"></i> Create Class
            </button>
        </div>
    </div>
</dialog>

{{-- ── DIALOG: Manage Students ── --}}
<dialog id="dialogStudents" class="rv-dialog">
    <div class="rv-dialog-panel">
        <div class="rv-drawer-head">
            <div>
                <div class="rv-drawer-title">Students</div>
                <div class="rv-drawer-subtitle" id="studentsDrawerSubtitle">—</div>
            </div>
            <button type="button" class="rv-drawer-close" onclick="document.getElementById('dialogStudents').close()">&#x2715;</button>
        </div>

        <div class="rv-drawer-body">
            <div class="rv-form-group">
                <label class="rv-label">Add students</label>
                <div style="display: flex; gap: 8px;">
                    <div style="flex: 1; min-width: 0;">
                        <select id="studentSelect" multiple="multiple" style="width: 100%;"></select>
                    </div>
                    <button type="button" class="rv-btn rv-btn-success" id="addSelectedStudentsBtn" style="height: 42px; padding: 0 16px; flex-shrink: 0;">
                        <i class="fas fa-user-plus"></i> Add
                    </button>
                </div>
            </div>

            <div style="margin-top: 16px;">
                <label class="rv-label" style="margin-bottom: 10px;">Current students</label>
                <div id="currentStudentsList">
                    <p style="font-size: 15px; color: #aaa; text-align: center; padding: 1rem 0;">Loading...</p>
                </div>
            </div>

            {{-- Join Link Section --}}
            <div class="rv-join-link-section" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #ebebeb;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-weight: 500; font-size: 14px;">
                    <i class="fas fa-link" style="color: #245E55;"></i>
                    <span>Invite Link</span>
                </div>
                <p style="font-size: 13px; color: #888; margin-bottom: 10px;">Generate a link valid for 24 hours to share with students.</p>
                <div style="display: flex; gap: 8px;">
                    <input type="text" class="rv-input" id="joinLinkInput" readonly placeholder="Click Generate to create link..." style="flex: 1;">
                    <button type="button" class="rv-btn rv-btn-secondary" id="joinLinkGenerateBtn" onclick="generateJoinLink()" title="Generate new link">
                        <i class="fas fa-sync-alt"></i> Generate
                    </button>
                    <button type="button" class="rv-btn rv-btn-secondary" id="joinLinkCopyBtn" onclick="copyJoinLink()" title="Copy link" disabled>
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="rv-drawer-footer">
            <button type="button" class="rv-btn rv-btn-secondary" onclick="document.getElementById('dialogStudents').close()">Close</button>
        </div>
    </div>
</dialog>

{{-- ── DIALOG: Manage Modules ── --}}
<dialog id="dialogModules" class="rv-dialog" style="width: 560px; max-width: 95vw;">
    <div class="rv-dialog-panel">
        <div class="rv-drawer-head">
            <div>
                <div class="rv-drawer-title">Manage Modules</div>
                <div class="rv-drawer-subtitle" id="modulesDrawerSubtitle">—</div>
            </div>
            <button type="button" class="rv-drawer-close" onclick="document.getElementById('dialogModules').close()">&#x2715;</button>
        </div>

        <div style="padding: 0 24px; border-bottom: 1px solid #DDD8CF; background: #FAF7F2;">
            <div class="rv-tabs">
                <button type="button" class="rv-tab active" onclick="switchTab('tabUpload', this)">Documents</button>
                <button type="button" class="rv-tab" onclick="switchTab('tabQuiz', this)">Quizzes</button>
                <button type="button" class="rv-tab" onclick="switchTab('tabAssessment', this)">Assessments</button>
                <button type="button" class="rv-tab" onclick="switchTab('tabAnnouncements', this)">Announcements</button>
            </div>
        </div>

        <div class="rv-drawer-body">
            {{-- TAB 1: Documents & Content --}}
            <div id="tabUpload" class="rv-tab-panel active">
                <form method="POST" action="{{ route('modules.store') }}" enctype="multipart/form-data" id="moduleUploadForm">
                    @csrf
                    <input type="hidden" name="class_id" id="moduleClassId">
                    <input type="hidden" name="type" value="document">

                    <div class="rv-form-group">
                        <label class="rv-label">Module title <span style="color:#e24b4a">*</span></label>
                        <input type="text" name="title" class="rv-input" required placeholder="e.g. Module 1: Introduction">
                    </div>

                    <div class="rv-form-group">
                        <label class="rv-label">Description (optional)</label>
                        <textarea name="description" class="rv-textarea" placeholder="Brief overview..."></textarea>
                    </div>

                    <div class="rv-form-group">
                        <label class="rv-label">Module content</label>
                        <div id="lectureContentUploadFields">
                            <div class="lecture-content-upload-row" style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:8px;">
                                <input type="text" name="subpart_titles[]" class="rv-input" required maxlength="150" placeholder="Subdomain title" style="grid-column:1 / -1;">
                                <input type="file" name="files[]" class="rv-input" accept=".pdf,.ppt,.pptx,.docx,.mov" required style="padding:7px 12px;">
                                <button type="button" class="rv-btn rv-btn-danger lecture-remove-upload" style="height:38px;padding:0 10px;display:none;" onclick="this.closest('.lecture-content-upload-row').remove()"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" class="rv-btn rv-btn-secondary" onclick="addLectureUploadField()"><i class="fas fa-plus"></i> Add another file</button>
                        <p style="font-size:13px;color:#aaa;margin:6px 0 0;">Each file needs its own Subdomain title. Titles are numbered automatically as 1.1, 1.2, 1.3, and so on.</p>
                    </div>

                    <div class="rv-form-group">
                        <label class="rv-label">Who can see this?</label>
                        <input type="hidden" name="visibility" id="visInput_doc" value="all">
                        <div class="vis-toggle">
                            <button type="button" class="vis-opt active" onclick="setVisibility(this,'doc')">All Students</button>
                            <button type="button" class="vis-opt" onclick="setVisibility(this,'doc')">Selected Students</button>
                            <button type="button" class="vis-opt" onclick="setVisibility(this,'doc')">Except Students</button>
                        </div>
                        <div class="vis-student-picker" id="visPicker_doc">
                            <div class="vis-search-wrap">
                                <input type="text" id="visSearch_doc" placeholder="Search students by name, ID, email..." autocomplete="off">
                                <div class="vis-results" id="visResults_doc"></div>
                            </div>
                            <div class="vis-chips" id="visChips_doc"></div>
                            <div class="vis-empty-hint" id="visHint_doc">Search and select students above.</div>
                        </div>
                    </div>

                    <button type="submit" class="rv-btn rv-btn-primary" style="width:100%;">
                        <i class="fas fa-upload"></i> Create Module
                    </button>
                </form>

                <div style="margin-top:20px;padding-top:16px;border-top:1.5px dashed #e5e7eb;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <label class="rv-label" style="margin:0;font-size:13px;font-weight:600;color:#374151;">
                            <i class="fas fa-folder-open" style="color:#245E55;margin-right:6px;"></i> Existing Documents
                        </label>
                        <span id="documentsCountBadge" style="font-size:12px;color:#6b7280;font-weight:500;"></span>
                    </div>
                    <div id="documentsList" style="max-height:250px;overflow-y:auto;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;">
                        <p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">Open this tab to load.</p>
                    </div>
                </div>
            </div>

            {{-- TAB 2: Quizzes --}}
            <div id="tabQuiz" class="rv-tab-panel">
                <form method="POST" action="" id="quizDraftForm">
                    @csrf
                    <input type="hidden" name="class_id" id="quizClassId">
                    <input type="hidden" name="type" value="pre_assessment">

                    <div class="rv-form-group">
                        <label class="rv-label">Quiz Title <span style="color:#e24b4a">*</span></label>
                        <input type="text" name="title" class="rv-input" required placeholder="e.g. Diagnostic Quiz">
                    </div>

                    <div class="rv-form-group">
                        <label class="rv-label">Description (optional)</label>
                        <textarea name="description" class="rv-textarea" placeholder="Brief instructions..."></textarea>
                    </div>

                    <div class="rv-form-group">
                        <label class="rv-label">Who can see this?</label>
                        <input type="hidden" name="visibility" id="visInput_quiz" value="all">
                        <div class="vis-toggle">
                            <button type="button" class="vis-opt active" onclick="setVisibility(this,'quiz')">All Students</button>
                            <button type="button" class="vis-opt" onclick="setVisibility(this,'quiz')">Selected Students</button>
                            <button type="button" class="vis-opt" onclick="setVisibility(this,'quiz')">Except Students</button>
                        </div>
                        <div class="vis-student-picker" id="visPicker_quiz">
                            <div class="vis-search-wrap">
                                <input type="text" id="visSearch_quiz" placeholder="Search students by name, ID, email..." autocomplete="off">
                                <div class="vis-results" id="visResults_quiz"></div>
                            </div>
                            <div class="vis-chips" id="visChips_quiz"></div>
                            <div class="vis-empty-hint" id="visHint_quiz">Search and select students above.</div>
                        </div>
                    </div>

                    <button type="submit" class="rv-btn rv-btn-primary" style="width:100%;">
                        <i class="fas fa-plus-circle"></i> Create & Add Questions
                    </button>
                </form>

                <div style="margin-top:20px;padding-top:16px;border-top:1.5px dashed #e5e7eb;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <label class="rv-label" style="margin:0;font-size:13px;font-weight:600;color:#374151;">
                            <i class="fas fa-question-circle" style="color:#245E55;margin-right:6px;"></i> Existing Quizzes
                        </label>
                        <span id="preAssessmentsCountBadge" style="font-size:12px;color:#6b7280;font-weight:500;"></span>
                    </div>
                    <div id="preAssessmentsList" style="max-height:250px;overflow-y:auto;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;">
                        <p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">Open this tab to load.</p>
                    </div>
                </div>
            </div>

            {{-- TAB 3: Assessments --}}
            <div id="tabAssessment" class="rv-tab-panel">
                <form method="POST" action="" id="assessmentDraftForm">
                    @csrf
                    <input type="hidden" name="class_id" id="assessmentClassId">
                    <input type="hidden" name="type" value="formal_assessment">

                    <div class="rv-form-group">
                        <label class="rv-label">Assessment Title <span style="color:#e24b4a">*</span></label>
                        <input type="text" name="title" class="rv-input" required placeholder="e.g. Midterm Assessment">
                    </div>

                    <div class="rv-form-group">
                        <label class="rv-label">Description (optional)</label>
                        <textarea name="description" class="rv-textarea" placeholder="Brief instructions..."></textarea>
                    </div>

                    <div class="rv-form-group">
                        <label class="rv-label">Who can see this?</label>
                        <input type="hidden" name="visibility" id="visInput_assessment" value="all">
                        <div class="vis-toggle">
                            <button type="button" class="vis-opt active" onclick="setVisibility(this,'assessment')">All Students</button>
                            <button type="button" class="vis-opt" onclick="setVisibility(this,'assessment')">Selected Students</button>
                            <button type="button" class="vis-opt" onclick="setVisibility(this,'assessment')">Except Students</button>
                        </div>
                        <div class="vis-student-picker" id="visPicker_assessment">
                            <div class="vis-search-wrap">
                                <input type="text" id="visSearch_assessment" placeholder="Search students by name, ID, email..." autocomplete="off">
                                <div class="vis-results" id="visResults_assessment"></div>
                            </div>
                            <div class="vis-chips" id="visChips_assessment"></div>
                            <div class="vis-empty-hint" id="visHint_assessment">Search and select students above.</div>
                        </div>
                    </div>

                    <button type="submit" class="rv-btn rv-btn-primary" style="width:100%;">
                        <i class="fas fa-plus-circle"></i> Create & Add Questions
                    </button>
                </form>

                <div style="margin-top:20px;padding-top:16px;border-top:1.5px dashed #e5e7eb;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <label class="rv-label" style="margin:0;font-size:13px;font-weight:600;color:#374151;">
                            <i class="fas fa-award" style="color:#245E55;margin-right:6px;"></i> Existing Assessments
                        </label>
                        <span id="formalAssessmentsCountBadge" style="font-size:12px;color:#6b7280;font-weight:500;"></span>
                    </div>
                    <div id="formalAssessmentsList" style="max-height:250px;overflow-y:auto;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;">
                        <p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">Open this tab to load.</p>
                    </div>
                </div>
            </div>

            {{-- TAB 4: Announcements --}}
            <div id="tabAnnouncements" class="rv-tab-panel">
                <form method="POST" action="" id="announcementForm">
                    @csrf
                    <div class="rv-form-group">
                        <label class="rv-label">Post Announcement <span style="color:#e24b4a">*</span></label>
                        <textarea name="message" class="rv-textarea" required placeholder="Write your announcement here..." style="min-height:90px;"></textarea>
                    </div>
                    <button type="submit" class="rv-btn rv-btn-primary" style="width:100%;">
                        <i class="fas fa-paper-plane"></i> Post Announcement
                    </button>
                </form>

                <div style="margin-top:20px;padding-top:16px;border-top:1.5px dashed #e5e7eb;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <label class="rv-label" style="margin:0;font-size:13px;font-weight:600;color:#374151;">
                            <i class="fas fa-bullhorn" style="color:#245E55;margin-right:6px;"></i> Announcements Feed
                        </label>
                        <span id="announcementsCountBadge" style="font-size:12px;color:#6b7280;font-weight:500;"></span>
                    </div>
                    <div id="classAnnouncementsList" style="max-height:280px;overflow-y:auto;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;">
                        <p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">Open this tab to load.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rv-drawer-footer">
            <button type="button" class="rv-btn rv-btn-secondary" onclick="document.getElementById('dialogModules').close()">Close</button>
        </div>
    </div>
</dialog>

{{-- ── DIALOG: Lecture Content ── --}}
<dialog id="dialogLectureContent" class="rv-dialog" style="width: 540px; max-width: 95vw;">
    <div class="rv-dialog-panel">
        <div class="rv-drawer-head">
            <div>
                <div class="rv-drawer-title">Module Content</div>
                <div class="rv-drawer-subtitle" id="lectureContentSubtitle">Manage Domains and Lessons.</div>
            </div>
            <button type="button" class="rv-drawer-close" onclick="document.getElementById('dialogLectureContent').close()">&#x2715;</button>
        </div>
        <div class="rv-drawer-body">
            <form id="lectureContentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="lectureContentModuleId">
                <div class="rv-form-group">
                    <label class="rv-label">Add Content File</label>
                    <input type="text" id="lectureContentTitle" class="rv-input" required maxlength="150" placeholder="Content title">
                    <input type="file" id="lectureContentFile" class="rv-input" accept=".pdf,.ppt,.pptx,.docx,.mov" required style="margin-top:8px;padding:7px 12px;">
                </div>
                <button type="submit" class="rv-btn rv-btn-primary"><i class="fas fa-upload"></i> Upload Content</button>
            </form>
            <form id="lectureSubpartForm" style="margin-top:20px;padding-top:16px;border-top:1px solid #ebebeb;">
                @csrf
                <input type="hidden" id="lectureSubpartModuleId">
                <div class="rv-form-group">
                    <label class="rv-label">New Domain</label>
                    <input type="text" id="lectureSubpartTitle" class="rv-input" required maxlength="150" placeholder="e.g. 1.1 Introduction">
                </div>
                <div class="rv-form-group">
                    <textarea id="lectureSubpartDescription" class="rv-textarea" placeholder="Domain description (optional)"></textarea>
                </div>
                <button type="submit" class="rv-btn rv-btn-primary"><i class="fas fa-plus"></i> Add Domain</button>
            </form>
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid #ebebeb;">
                <label class="rv-label">Domains and Lessons</label>
                <div id="lectureContentList"><p style="font-size:16px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p></div>
            </div>
        </div>
        <div class="rv-drawer-footer">
            <button type="button" class="rv-btn rv-btn-secondary" onclick="document.getElementById('dialogLectureContent').close()">Close</button>
        </div>
    </div>
</dialog>

{{-- Delete Class Confirmation Modal --}}
<div id="deleteClassConfirmOverlay" class="delete-confirm-overlay" aria-hidden="true">
    <div class="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteClassConfirmTitle">
        <p class="delete-confirm-title" id="deleteClassConfirmTitle">Please Confirm</p>
        <p class="delete-confirm-text" id="deleteClassConfirmMessage">Are you sure?</p>
        <div class="delete-confirm-actions">
            <button type="button" class="delete-confirm-cancel" onclick="closeDeleteClassConfirm()">Cancel</button>
            <button type="button" class="delete-confirm-continue" id="deleteClassConfirmProceedBtn">Continue</button>
        </div>
    </div>
</div>

{{-- Generic Manage Confirmation Modal --}}
<div id="mcConfirmOverlay" class="mc-confirm-overlay" aria-hidden="true">
    <div class="mc-confirm-modal">
        <p class="mc-confirm-title" id="mcConfirmTitle">Please Confirm</p>
        <p class="mc-confirm-text" id="mcConfirmMessage">Are you sure?</p>
        <div class="mc-confirm-actions">
            <button type="button" class="delete-confirm-cancel" onclick="closeManageConfirm()">Cancel</button>
            <button type="button" class="delete-confirm-continue" id="mcConfirmProceedBtn">Continue</button>
        </div>
    </div>
</div>

{{-- Announcement Edit Modal --}}
<div id="announcementEditOverlay" class="announcement-edit-overlay" aria-hidden="true">
    <div class="announcement-edit-modal">
        <p class="mc-confirm-title" id="announcementEditTitle">Edit Announcement</p>
        <textarea id="announcementEditInput" class="rv-textarea" style="width:100%;margin-bottom:16px;"></textarea>
        <div class="mc-confirm-actions">
            <button type="button" class="delete-confirm-cancel" onclick="closeAnnouncementEditModal()">Cancel</button>
            <button type="button" class="rv-btn rv-btn-primary" id="announcementEditSaveBtn" onclick="submitAnnouncementEdit()">Save Changes</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let currentClassId = null;
let deleteModeActive = false;
let currentDeleteForm = null;
let manageConfirmCallback = null;
let editingAnnouncementId = null;

const visDebounceTimers = {};
const visSelectedStudents = { doc: {}, quiz: {}, assessment: {} };

// ── Dialog Handlers ──
function openCreateDialog() {
    const d = document.getElementById('dialogCreate');
    if (d && typeof d.showModal === 'function') d.showModal();
}
window.openCreateDialog = openCreateDialog;

function closeCreateDialog() {
    const d = document.getElementById('dialogCreate');
    if (d && typeof d.close === 'function') d.close();
}
window.closeCreateDialog = closeCreateDialog;

function openStudentsDrawer(classId, className) {
    currentClassId = classId;
    const sub = document.getElementById('studentsDrawerSubtitle');
    if (sub) sub.textContent = className;
    const d = document.getElementById('dialogStudents');
    if (d && typeof d.showModal === 'function') d.showModal();

    const joinInput = document.getElementById('joinLinkInput');
    if (joinInput) joinInput.value = '';
    const joinCopy = document.getElementById('joinLinkCopyBtn');
    if (joinCopy) joinCopy.disabled = true;

    loadCurrentStudents();

    if (typeof $.fn.select2 === 'function') {
        if (!$('#studentSelect').hasClass('select2-hidden-accessible')) {
            try {
                $('#studentSelect').select2({
                    placeholder: 'Search by name or ID...',
                    allowClear: true,
                    multiple: true,
                    minimumInputLength: 1,
                    dropdownParent: $('#dialogStudents'),
                    ajax: {
                        url: "{{ route('students.search') }}",
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: data => ({ results: data.results }),
                        cache: true
                    }
                });
            } catch (e) {
                console.warn('Select2 init warning:', e);
            }
        }
    }
}
window.openStudentsDrawer = openStudentsDrawer;

function openModulesDrawer(classId, className) {
    currentClassId = classId;
    const sub = document.getElementById('modulesDrawerSubtitle');
    if (sub) sub.textContent = className;
    const modId = document.getElementById('moduleClassId');
    if (modId) modId.value = classId;
    const quizId = document.getElementById('quizClassId');
    if (quizId) quizId.value = classId;
    const quizForm = document.getElementById('quizDraftForm');
    if (quizForm) quizForm.action = "{{ url('/quiz/create-draft') }}/" + classId;
    const assessId = document.getElementById('assessmentClassId');
    if (assessId) assessId.value = classId;
    const assessForm = document.getElementById('assessmentDraftForm');
    if (assessForm) assessForm.action = "{{ url('/quiz/create-draft') }}/" + classId;
    const annForm = document.getElementById('announcementForm');
    if (annForm) annForm.action = "{{ url('/classes') }}/" + classId + "/announcements";

    switchTab('tabUpload', document.querySelector('#dialogModules .rv-tab'));
    ['doc','quiz','assessment'].forEach(resetVisibilityPicker);

    const d = document.getElementById('dialogModules');
    if (d && typeof d.showModal === 'function') d.showModal();
}
window.openModulesDrawer = openModulesDrawer;

function openLectureContent(moduleId, title) {
    $('#lectureContentSubtitle').text(title);
    $('#lectureSubpartModuleId').val(moduleId);
    $('#lectureContentModuleId').val(moduleId);
    $('#lectureContentList').html('<p style="font-size:15px;color:#888;text-align:center;padding:1rem 0;"><i class="fas fa-spinner fa-spin"></i> Loading Domains...</p>');
    loadLectureSubparts(moduleId);
    const d = document.getElementById('dialogLectureContent');
    if (d && typeof d.showModal === 'function') d.showModal();
}
window.openLectureContent = openLectureContent;

// ── Tab Switcher ──
function switchTab(panelId, btn) {
    closeManageConfirm();
    document.querySelectorAll('.rv-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.rv-tab').forEach(b => b.classList.remove('active'));
    const panel = document.getElementById(panelId);
    if (panel) panel.classList.add('active');
    if (btn) btn.classList.add('active');

    if (panelId === 'tabUpload' && currentClassId) loadModulesForTab(currentClassId, 'document', 'documentsList');
    if (panelId === 'tabQuiz' && currentClassId) loadModulesForTab(currentClassId, 'pre_assessment', 'preAssessmentsList');
    if (panelId === 'tabAssessment' && currentClassId) loadModulesForTab(currentClassId, 'formal_assessment', 'formalAssessmentsList');
    if (panelId === 'tabAnnouncements' && currentClassId) loadClassAnnouncements(currentClassId);
}
window.switchTab = switchTab;

// ── Delete Classes Mode ──
function toggleDeleteMode() {
    deleteModeActive = !deleteModeActive;
    const toggleBtn = document.getElementById('deleteModeToggle');
    const deleteActions = document.querySelectorAll('.class-delete-action');

    if (deleteModeActive) {
        if (toggleBtn) {
            toggleBtn.classList.add('active');
            toggleBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Delete';
        }
        deleteActions.forEach(el => el.style.display = 'block');
    } else {
        if (toggleBtn) {
            toggleBtn.classList.remove('active');
            toggleBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Classes';
        }
        deleteActions.forEach(el => el.style.display = 'none');
    }
}
window.toggleDeleteMode = toggleDeleteMode;

function openDeleteClassConfirm(btn) {
    currentDeleteForm = btn.closest('.delete-class-form');
    const className = currentDeleteForm ? currentDeleteForm.dataset.className : '';
    const msg = document.getElementById('deleteClassConfirmMessage');
    if (msg) msg.textContent = 'Delete class "' + className + '"? This cannot be undone.';
    const overlay = document.getElementById('deleteClassConfirmOverlay');
    if (overlay) overlay.setAttribute('aria-hidden', 'false');
}
window.openDeleteClassConfirm = openDeleteClassConfirm;

function closeDeleteClassConfirm() {
    const overlay = document.getElementById('deleteClassConfirmOverlay');
    if (overlay) overlay.setAttribute('aria-hidden', 'true');
    currentDeleteForm = null;
}
window.closeDeleteClassConfirm = closeDeleteClassConfirm;

// ── Students Management ──
function loadCurrentStudents() {
    if (!currentClassId) return;
    const $list = $('#currentStudentsList');
    $list.html('<p style="font-size: 15px; color: #aaa; text-align: center; padding: 1rem 0;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');

    $.get("{{ url('/classes') }}/" + currentClassId + "/students", function(data) {
        if (!data.students || data.students.length === 0) {
            $list.html('<p style="font-size: 15px; color: #aaa; text-align: center; padding: 1rem 0;">No students enrolled yet.</p>');
            return;
        }

        const programLabel = p => p ? '<span style="font-size: 12px; padding: 2px 7px; border-radius: 99px; background: #f3f3f3; color: #666; margin-left: 6px;">' + (p.charAt(0).toUpperCase() + p.slice(1)) + '</span>' : '';

        const html = data.students.map(s => {
            const displayName = s.text || s.name || ('Student #' + s.id);
            return '<div class="rv-student-item" data-student-id="' + s.id + '" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border:1px solid #ebebeb;border-radius:8px;margin-bottom:6px;background:#fff;">'
                + '<span>' + displayName + programLabel(s.program) + '</span>'
                + '<button type="button" class="rv-btn rv-btn-danger" style="height:28px;padding:0 10px;font-size:13px;" onclick="removeStudent(this, ' + s.id + ')">Remove</button>'
                + '</div>';
        }).join('');

        $list.html(html);
    }).fail(() => {
        $list.html('<p style="font-size: 15px; color: #e24b4a; text-align: center;">Failed to load students.</p>');
    });
}
window.loadCurrentStudents = loadCurrentStudents;

function removeStudent(triggerBtn, id) {
    openManageConfirm('Remove this student from the class?', function () {
        $.ajax({
            url: "{{ url('/classes') }}/" + currentClassId + "/students/" + id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function () {
                loadCurrentStudents();
            },
            error: function () {
                showUploadValidationToast('Failed to remove student.', 'error');
            }
        });
    });
}
window.removeStudent = removeStudent;

// ── Modules Listing & Actions ──
function loadModulesForTab(classId, type, containerId) {
    $('#' + containerId).html('<p style="font-size: 14px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p>');

    $.get("{{ url('/classes') }}/" + classId + "/modules/list", function (data) {
        const modules = (data.modules || []).filter(m => m.type === type);
        const badgeMap = { document: 'documentsCountBadge', pre_assessment: 'preAssessmentsCountBadge', formal_assessment: 'formalAssessmentsCountBadge' };
        if (badgeMap[type]) {
            $('#' + badgeMap[type]).text(modules.length + ' item' + (modules.length === 1 ? '' : 's'));
        }

        if (modules.length === 0) {
            $('#' + containerId).html('<p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">None yet.</p>');
            return;
        }

        const html = modules.map(m => {
            const visLabel = m.visibility === 'selected' ? '<span style="font-size:11px;background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;margin-left:6px;">Selected</span>'
                : m.visibility === 'except' ? '<span style="font-size:11px;background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;margin-left:6px;">Except</span>' : '';

            let actions = '';
            if (type === 'document') {
                actions += '<button type="button" class="rv-btn rv-btn-secondary" style="height:28px;padding:0 8px;font-size:12px;margin-right:4px;" onclick="openLectureContent(' + m.id + ', ' + JSON.stringify(m.title) + ')"><i class="fas fa-folder-open"></i> Content</button>';
            } else {
                actions += '<a href="' + "{{ url('/quiz/builder') }}/" + m.id + '" class="rv-btn rv-btn-secondary" style="height:28px;padding:0 8px;font-size:12px;margin-right:4px;text-decoration:none;"><i class="fas fa-edit"></i> Edit</a>';
            }
            actions += '<button type="button" class="rv-btn rv-btn-danger" style="height:28px;padding:0 8px;font-size:12px;" onclick="deleteModuleFromTab(' + m.id + ', \x27' + type + '\x27, \x27' + containerId + '\x27)"><i class="fas fa-trash"></i></button>';

            return '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f1f1;font-size:14px;">'
                + '<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding-right:8px;">'
                + '<strong>' + m.title + '</strong>' + visLabel
                + '</div>'
                + '<div style="display:flex;align-items:center;flex-shrink:0;">' + actions + '</div>'
                + '</div>';
        }).join('');

        $('#' + containerId).html(html);
    }).fail(() => {
        $('#' + containerId).html('<p style="font-size: 14px;color:#e24b4a;text-align:center;">Failed to load.</p>');
    });
}
window.loadModulesForTab = loadModulesForTab;

function deleteModuleFromTab(moduleId, type, containerId) {
    openManageConfirm('Delete this item? This cannot be undone.', function () {
        $.ajax({
            url: "{{ url('/modules') }}/" + moduleId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function () {
                loadModulesForTab(currentClassId, type, containerId);
            },
            error: function () {
                showUploadValidationToast('Failed to delete module.', 'error');
            }
        });
    });
}
window.deleteModuleFromTab = deleteModuleFromTab;

// ── Lecture Subparts & Lessons ──
function loadLectureSubparts(moduleId) {
    $.get("{{ url('/modules') }}/" + moduleId + "/subparts", function (data) {
        const subparts = data.subparts || [];
        if (subparts.length === 0) {
            $('#lectureContentList').html('<p style="font-size:14px;color:#aaa;text-align:center;padding:1rem 0;">No Domains yet.</p>');
            return;
        }

        const html = subparts.map(subpart => {
            return '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:10px;background:#fff;">'
                + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
                + '<strong>' + subpart.title + '</strong>'
                + '<button type="button" class="rv-btn rv-btn-danger" style="height:24px;padding:0 8px;font-size:11px;" onclick="deleteLectureSubpart(' + subpart.id + ', ' + moduleId + ')"><i class="fas fa-trash"></i></button>'
                + '</div>'
                + '<div id="lessons-' + subpart.id + '" style="margin:6px 0 10px 10px;"></div>'
                + '<form onsubmit="addLectureLesson(event, ' + subpart.id + ', ' + moduleId + ')" style="display:flex;gap:6px;">'
                + '<input type="text" class="rv-input" placeholder="New lesson title" required maxlength="150" style="flex:1;height:32px;font-size:13px;padding:4px 8px;">'
                + '<button type="submit" class="rv-btn rv-btn-secondary" style="height:32px;padding:0 10px;font-size:12px;"><i class="fas fa-plus"></i> Lesson</button>'
                + '</form>'
                + '</div>';
        }).join('');

        $('#lectureContentList').html(html);
        subparts.forEach(s => loadLectureLessons(s.id));
    }).fail(() => $('#lectureContentList').html('<p style="font-size:14px;color:#e24b4a;text-align:center;">Failed to load Domains.</p>'));
}
window.loadLectureSubparts = loadLectureSubparts;

function loadLectureLessons(subpartId) {
    $.get("{{ url('/subparts') }}/" + subpartId + "/lessons", function (data) {
        const lessons = data.lessons || [];
        const html = lessons.length === 0 ? '<p style="font-size:12px;color:#aaa;margin:0;">No lessons yet.</p>'
            : lessons.map(lesson => '<div style="display:flex;justify-content:space-between;gap:8px;padding:3px 0;border-bottom:1px solid #f9f9f9;font-size:13px;">'
                + '<span>' + lesson.order + '. ' + lesson.title + '</span>'
                + '<button type="button" class="rv-btn rv-btn-danger" style="height:22px;padding:0 6px;font-size:10px;" onclick="deleteLectureLesson(' + lesson.id + ', ' + subpartId + ')"><i class="fas fa-trash"></i></button>'
                + '</div>').join('');
        $('#lessons-' + subpartId).html(html);
    });
}
window.loadLectureLessons = loadLectureLessons;

function addLectureLesson(event, subpartId, moduleId) {
    event.preventDefault();
    const input = event.target.querySelector('input');
    const title = input.value.trim();
    if (!title) return;

    $.post("{{ url('/subparts') }}/" + subpartId + "/lessons", {
        _token: '{{ csrf_token() }}',
        title: title
    }).done(() => {
        input.value = '';
        loadLectureLessons(subpartId);
    }).fail(() => showUploadValidationToast('Failed to add lesson.', 'error'));
}
window.addLectureLesson = addLectureLesson;

function deleteLectureSubpart(subpartId, moduleId) {
    openManageConfirm('Delete this Domain and its Lessons? This cannot be undone.', function () {
        $.ajax({ url: "{{ url('/subparts') }}/" + subpartId, type: 'DELETE', data: { _token: '{{ csrf_token() }}' } })
            .done(() => loadLectureSubparts(moduleId))
            .fail(() => showUploadValidationToast('Failed to delete Domain.', 'error'));
    });
}
window.deleteLectureSubpart = deleteLectureSubpart;

function deleteLectureLesson(lessonId, subpartId) {
    openManageConfirm('Delete this Lesson? This cannot be undone.', function () {
        $.ajax({ url: "{{ url('/lessons') }}/" + lessonId, type: 'DELETE', data: { _token: '{{ csrf_token() }}' } })
            .done(() => loadLectureLessons(subpartId))
            .fail(() => showUploadValidationToast('Failed to delete Lesson.', 'error'));
    });
}
window.deleteLectureLesson = deleteLectureLesson;

function addLectureUploadField() {
    const row = document.createElement('div');
    row.className = 'lecture-content-upload-row';
    row.style.cssText = 'display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:8px;';
    row.innerHTML = '<input type="text" name="subpart_titles[]" class="rv-input" required maxlength="150" placeholder="Subdomain title" style="grid-column:1 / -1;">'
        + '<input type="file" name="files[]" class="rv-input" accept=".pdf,.ppt,.pptx,.docx,.mov" required style="padding:7px 12px;">'
        + '<button type="button" class="rv-btn rv-btn-danger lecture-remove-upload" style="height:38px;padding:0 10px;" onclick="this.closest(\\x27.lecture-content-upload-row\\x27).remove()"><i class="fas fa-trash"></i></button>';
    document.getElementById('lectureContentUploadFields').appendChild(row);
}
window.addLectureUploadField = addLectureUploadField;

// ── Announcements ──
function loadClassAnnouncements(classId) {
    $('#classAnnouncementsList').html('<p style="font-size: 14px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p>');

    $.get("{{ url('/classes') }}/" + classId + "/announcements/feed", function (data) {
        const announcements = data.announcements || [];
        $('#announcementsCountBadge').text(announcements.length + ' post' + (announcements.length === 1 ? '' : 's'));

        if (announcements.length === 0) {
            $('#classAnnouncementsList').html('<p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">No announcements yet.</p>');
            return;
        }

        const html = announcements.map(a => {
            return '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:8px;">'
                + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
                + '<span style="font-size:12px;color:#6b7280;">' + (a.created_at_formatted || 'Just now') + '</span>'
                + '<div>'
                + '<button type="button" class="rv-btn rv-btn-secondary" style="height:24px;padding:0 6px;font-size:11px;margin-right:4px;" onclick="editAnnouncement(' + a.id + ', ' + JSON.stringify(a.message) + ')"><i class="fas fa-edit"></i></button>'
                + '<button type="button" class="rv-btn rv-btn-danger" style="height:24px;padding:0 6px;font-size:11px;" onclick="deleteAnnouncement(' + a.id + ')"><i class="fas fa-trash"></i></button>'
                + '</div>'
                + '</div>'
                + '<div style="font-size:14px;color:#1f2937;white-space:pre-wrap;">' + $('<div>').text(a.message).html() + '</div>'
                + '</div>';
        }).join('');

        $('#classAnnouncementsList').html(html);
    }).fail(() => $('#classAnnouncementsList').html('<p style="font-size: 14px;color:#e24b4a;text-align:center;">Failed to load announcements.</p>'));
}
window.loadClassAnnouncements = loadClassAnnouncements;

function deleteAnnouncement(announcementId) {
    openManageConfirm('Delete this announcement?', function () {
        $.ajax({
            url: "{{ url('/announcements') }}/" + announcementId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: () => loadClassAnnouncements(currentClassId),
            error: () => showUploadValidationToast('Failed to delete announcement.', 'error')
        });
    });
}
window.deleteAnnouncement = deleteAnnouncement;

function editAnnouncement(announcementId, message) {
    editingAnnouncementId = announcementId;
    $('#announcementEditInput').val(message);
    const modal = document.getElementById('announcementEditOverlay');
    if (modal) modal.setAttribute('aria-hidden', 'false');
}
window.editAnnouncement = editAnnouncement;

function closeAnnouncementEditModal() {
    const modal = document.getElementById('announcementEditOverlay');
    if (modal) modal.setAttribute('aria-hidden', 'true');
    editingAnnouncementId = null;
}
window.closeAnnouncementEditModal = closeAnnouncementEditModal;

function submitAnnouncementEdit() {
    const message = $('#announcementEditInput').val().trim();
    if (!message || !editingAnnouncementId) return;

    $.ajax({
        url: "{{ url('/announcements') }}/" + editingAnnouncementId,
        type: 'PUT',
        data: { _token: '{{ csrf_token() }}', message: message },
        success: function () {
            closeAnnouncementEditModal();
            loadClassAnnouncements(currentClassId);
        },
        error: () => showUploadValidationToast('Failed to update announcement.', 'error')
    });
}
window.submitAnnouncementEdit = submitAnnouncementEdit;

// ── Visibility Picker ──
function setVisibility(btn, form) {
    btn.closest('.vis-toggle').querySelectorAll('.vis-opt').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const label = btn.textContent.trim();
    const map = { 'All Students': 'all', 'Selected Students': 'selected', 'Except Students': 'except' };
    const val = map[label] || 'all';
    document.getElementById('visInput_' + form).value = val;

    const picker = document.getElementById('visPicker_' + form);
    const hint = document.getElementById('visHint_' + form);

    if (val === 'all') {
        if (picker) picker.style.display = 'none';
    } else {
        if (picker) picker.style.display = 'block';
        if (hint) {
            hint.textContent = val === 'selected'
                ? 'Only these students will see this content.'
                : 'Everyone EXCEPT these students will see this content.';
        }
    }
}
window.setVisibility = setVisibility;

function resetVisibilityPicker(form) {
    if (visSelectedStudents[form]) visSelectedStudents[form] = {};
    const picker = document.getElementById('visPicker_' + form);
    if (picker) {
        picker.style.display = 'none';
        const searchEl = document.getElementById('visSearch_' + form);
        if (searchEl) searchEl.value = '';
        const resultsEl = document.getElementById('visResults_' + form);
        if (resultsEl) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; }
        const chipsEl = document.getElementById('visChips_' + form);
        if (chipsEl) chipsEl.innerHTML = '';
    }
    const visInput = document.getElementById('visInput_' + form);
    if (visInput) visInput.value = 'all';

    const formKeyToTab = { doc: 'tabUpload', quiz: 'tabQuiz', assessment: 'tabAssessment' };
    const panel = document.getElementById(formKeyToTab[form]);
    if (panel && typeof panel.querySelectorAll === 'function') {
        panel.querySelectorAll('.vis-opt').forEach(b => {
            b.classList.toggle('active', b.textContent.trim() === 'All Students');
        });
    }
}
window.resetVisibilityPicker = resetVisibilityPicker;

function addVisChip(form, id, name) {
    if (!visSelectedStudents[form]) visSelectedStudents[form] = {};
    if (visSelectedStudents[form][id]) return;
    visSelectedStudents[form][id] = name;

    const chipsEl = document.getElementById('visChips_' + form);
    if (!chipsEl) return;
    const chip = document.createElement('div');
    chip.className = 'vis-chip';
    chip.dataset.id = id;
    chip.innerHTML = $('<span>').text(name).html() + '<span class="vis-chip-remove" onclick="removeVisChip(this,\\x27' + form + '\\x27,' + id + ')">&times;</span>';
    chipsEl.appendChild(chip);

    const hint = document.getElementById('visHint_' + form);
    if (hint) hint.style.display = 'none';
}
window.addVisChip = addVisChip;

function removeVisChip(btn, form, id) {
    if (visSelectedStudents[form]) delete visSelectedStudents[form][id];
    btn.closest('.vis-chip').remove();
    const chipsEl = document.getElementById('visChips_' + form);
    if (chipsEl && chipsEl.children.length === 0) {
        const hint = document.getElementById('visHint_' + form);
        if (hint) hint.style.display = 'block';
    }
}
window.removeVisChip = removeVisChip;

// ── Generic Confirmation ──
function openManageConfirm(message, onConfirm) {
    manageConfirmCallback = onConfirm;
    $('#mcConfirmMessage').text(message);
    const modal = document.getElementById('mcConfirmOverlay');
    if (modal) modal.setAttribute('aria-hidden', 'false');
}
window.openManageConfirm = openManageConfirm;

function closeManageConfirm() {
    const modal = document.getElementById('mcConfirmOverlay');
    if (modal) modal.setAttribute('aria-hidden', 'true');
    manageConfirmCallback = null;
}
window.closeManageConfirm = closeManageConfirm;

function showUploadValidationToast(message, type) {
    const bg = type === 'error' ? '#ef4444' : type === 'warn' ? '#f59e0b' : '#10b981';
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:' + bg + ';color:#fff;padding:12px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:999999;font-size:14px;';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
window.showUploadValidationToast = showUploadValidationToast;

// ── Join Link ──
function generateJoinLink() {
    if (!currentClassId) return;
    const btn = document.getElementById('joinLinkGenerateBtn');
    if (btn) btn.disabled = true;

    $.post("{{ url('/classes') }}/" + currentClassId + "/join-link", { _token: '{{ csrf_token() }}' })
        .done(function (data) {
            const input = document.getElementById('joinLinkInput');
            if (input) input.value = data.join_url;
            const copyBtn = document.getElementById('joinLinkCopyBtn');
            if (copyBtn) copyBtn.disabled = false;
        })
        .fail(() => showUploadValidationToast('Failed to generate join link.', 'error'))
        .always(() => { if (btn) btn.disabled = false; });
}
window.generateJoinLink = generateJoinLink;

function copyJoinLink() {
    const input = document.getElementById('joinLinkInput');
    if (!input || !input.value) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('joinLinkCopyBtn');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
        }
    });
}
window.copyJoinLink = copyJoinLink;

// ── Event Listeners ──
$(document).ready(function () {
    // Add selected students
    $('#addSelectedStudentsBtn').on('click', function () {
        const selected = $('#studentSelect').val() || [];
        if (selected.length === 0) {
            showUploadValidationToast('Select at least one student.', 'warn');
            return;
        }
        $.post("{{ url('/classes') }}/" + currentClassId + "/students", {
            _token: '{{ csrf_token() }}',
            student_ids: selected
        }).done(function () {
            loadCurrentStudents();
            $('#studentSelect').val(null).trigger('change');
        }).fail(() => showUploadValidationToast('Failed to add students.', 'error'));
    });

    // Close dialog when clicking on backdrop
    document.querySelectorAll('dialog.rv-dialog').forEach(dialog => {
        dialog.addEventListener('click', function (e) {
            const rect = this.getBoundingClientRect();
            const isInDialog = (
                rect.top <= e.clientY && e.clientY <= rect.top + rect.height &&
                rect.left <= e.clientX && e.clientX <= rect.left + rect.width
            );
            if (!isInDialog) {
                this.close();
            }
        });
    });

    // Delete class proceed
    $('#deleteClassConfirmProceedBtn').on('click', function () {
        if (currentDeleteForm) currentDeleteForm.submit();
    });

    // Generic confirm proceed
    $('#mcConfirmProceedBtn').on('click', function () {
        if (typeof manageConfirmCallback === 'function') {
            const cb = manageConfirmCallback;
            closeManageConfirm();
            cb();
        }
    });

    // Announcements post
    $('#announcementForm').on('submit', function (e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize())
            .done(() => {
                document.getElementById('announcementForm').reset();
                loadClassAnnouncements(currentClassId);
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.message || 'Failed to post announcement.';
                showUploadValidationToast(msg, 'error');
            });
    });

    // Lecture subpart form
    $('#lectureSubpartForm').on('submit', function (e) {
        e.preventDefault();
        const moduleId = $('#lectureSubpartModuleId').val();
        $.post("{{ url('/modules') }}/" + moduleId + "/subparts", {
            _token: '{{ csrf_token() }}',
            title: $('#lectureSubpartTitle').val(),
            description: $('#lectureSubpartDescription').val()
        }).done(() => {
            $('#lectureSubpartTitle, #lectureSubpartDescription').val('');
            loadLectureSubparts(moduleId);
        }).fail(() => showUploadValidationToast('Failed to add Domain.', 'error'));
    });

    // Lecture content form
    $('#lectureContentForm').on('submit', function (e) {
        e.preventDefault();
        const moduleId = $('#lectureContentModuleId').val();
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('title', $('#lectureContentTitle').val());
        formData.append('file', $('#lectureContentFile').get(0).files[0]);

        $.ajax({
            url: "{{ url('/modules') }}/" + moduleId + "/subparts",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: () => {
                $('#lectureContentTitle, #lectureContentFile').val('');
                loadLectureSubparts(moduleId);
            },
            error: () => showUploadValidationToast('Failed to upload content.', 'error')
        });
    });

    // Visibility search debounced
    ['doc', 'quiz', 'assessment'].forEach(form => {
        $('#visSearch_' + form).on('input', function () {
            const q = $(this).val().trim();
            const resultsEl = $('#visResults_' + form);
            clearTimeout(visDebounceTimers[form]);

            if (q.length < 1) {
                resultsEl.hide().empty();
                return;
            }

            visDebounceTimers[form] = setTimeout(() => {
                $.get("{{ url('/classes') }}/" + currentClassId + "/students/search", { q: q }, function (data) {
                    const students = data.students || [];
                    if (students.length === 0) {
                        resultsEl.html('<div style="padding:8px 12px;font-size:13px;color:#aaa;">No students found.</div>').show();
                        return;
                    }

                    const html = students.map(s => {
                        return '<div class="vis-result-item" data-id="' + s.id + '" data-name="' + $('<span>').text(s.name).html() + '">'
                            + '<span>' + s.name + (s.idnumber ? ' (' + s.idnumber + ')' : '') + '</span>'
                            + '<span style="color:#aaa;font-size:11px;">' + (s.email || '') + '</span>'
                            + '</div>';
                    }).join('');

                    resultsEl.html(html).show();
                });
            }, 250);
        });

        $(document).on('click', '#visResults_' + form + ' .vis-result-item', function () {
            addVisChip(form, $(this).data('id'), $(this).data('name'));
            $('#visResults_' + form).hide().empty();
            $('#visSearch_' + form).val('');
        });
    });

    @if ($errors->any())
    openCreateDialog();
    @endif
});
</script>

@endsection
