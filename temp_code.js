
window.safeOpenDialog = function(dialogId) {
    const el = document.getElementById(dialogId);
    if (!el) return;
    el.classList.add('open');
    const overlay = document.getElementById('rvOverlay');
    if (overlay) overlay.classList.add('open');
};

window.safeCloseDialog = function(dialogId) {
    const el = document.getElementById(dialogId);
    if (!el) return;
    el.classList.remove('open');
    if (!document.querySelector('.rv-drawer.open')) {
        const overlay = document.getElementById('rvOverlay');
        if (overlay) overlay.classList.remove('open');
    }
};

window.openCreateDialog = function() {
    window.safeOpenDialog('dialogCreate');
};

window.closeCreateDialog = function() {
    window.safeCloseDialog('dialogCreate');
};

let deleteModeActive = false;
window.toggleDeleteMode = function() {
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
};

let currentDeleteForm = null;
window.openDeleteClassConfirm = function(btn) {
    currentDeleteForm = btn.closest('.delete-class-form');
    const className = currentDeleteForm ? currentDeleteForm.dataset.className : '';
    const msg = document.getElementById('deleteClassConfirmMessage');
    if (msg) msg.textContent = 'Delete class "' + className + '"? This cannot be undone.';
    const overlay = document.getElementById('deleteClassConfirmOverlay');
    if (overlay) overlay.setAttribute('aria-hidden', 'false');
};

window.closeDeleteClassConfirm = function() {
    const overlay = document.getElementById('deleteClassConfirmOverlay');
    if (overlay) overlay.setAttribute('aria-hidden', 'true');
    currentDeleteForm = null;
};

let currentClassId = null;

// ── Visibility State & Reset (initialized early) ──
const visDebounceTimers = {};
const visSelectedStudents = { doc: {}, quiz: {}, assessment: {} };

window.resetVisibilityPicker = function(form) {
    if (visSelectedStudents[form]) {
        visSelectedStudents[form] = {};
    }
    const picker = document.getElementById('visPicker_' + form);
    if (picker) {
        picker.style.display = 'none';
        const searchEl = document.getElementById('visSearch_' + form);
        if (searchEl) searchEl.value = '';
        const resultsEl = document.getElementById('visResults_' + form);
        if (resultsEl) {
            resultsEl.innerHTML = '';
            resultsEl.style.display = 'none';
        }
        const chipsEl = document.getElementById('visChips_' + form);
        if (chipsEl) chipsEl.innerHTML = '';
        const hintEl = document.getElementById('visHint_' + form);
        if (hintEl) hintEl.textContent = 'Search and select students above.';
    }
    const visInput = document.getElementById('visInput_' + form);
    if (visInput) visInput.value = 'all';

    const formKeyToTab = { doc: 'tabUpload', quiz: 'tabQuiz', assessment: 'tabAssessment' };
    const panel = document.getElementById(formKeyToTab[form]);
    if (panel) {
        panel.querySelectorAll('.vis-opt').forEach(b => {
            if (b.textContent.trim() === 'All Students') {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });
    }
};

// ── Tab switcher ──
window.switchTab = function(panelId, btn) {
    if (typeof closeManageConfirm === 'function') {
        closeManageConfirm();
    }
    document.querySelectorAll('.rv-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.rv-tab').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById(panelId);
    if (panel) panel.classList.add('active');
    if (btn) btn.classList.add('active');

    if (panelId === 'tabUpload' && currentClassId && typeof loadModulesForTab === 'function') loadModulesForTab(currentClassId, 'document', 'documentsList');
    if (panelId === 'tabQuiz' && currentClassId && typeof loadModulesForTab === 'function') loadModulesForTab(currentClassId, 'pre_assessment', 'preAssessmentsList');
    if (panelId === 'tabAssessment' && currentClassId && typeof loadModulesForTab === 'function') loadModulesForTab(currentClassId, 'formal_assessment', 'formalAssessmentsList');
    if (panelId === 'tabAnnouncements' && currentClassId && typeof loadClassAnnouncements === 'function') loadClassAnnouncements(currentClassId);
};

// ── Open Students drawer ──
window.initStudentsDrawer = window.openStudentsDrawer = function(classId, className) {
    currentClassId = classId;
    const sub = document.getElementById('studentsDrawerSubtitle');
    if (sub) sub.textContent = className;
    window.safeOpenDialog('dialogStudents');

    const joinInput = document.getElementById('joinLinkInput');
    if (joinInput) joinInput.value = '';
    const joinCopy = document.getElementById('joinLinkCopyBtn');
    if (joinCopy) joinCopy.disabled = true;

    if (typeof loadCurrentStudents === 'function') {
        loadCurrentStudents();
    }

    if (typeof $.fn.select2 === 'function' && !$('#studentSelect').hasClass('select2-hidden-accessible')) {
        try {
            $('#studentSelect').select2({
                placeholder: 'Search by name or ID...',
                allowClear: true,
                multiple: true,
                minimumInputLength: 1,
                dropdownParent: $('#dialogStudents'),
                ajax: {
                    url: "sample",
                    dataType: 'json',
                    delay: 200,
                    data: params => ({ q: params.term }),
                    processResults: data => ({ results: data.results }),
                    cache: true
                }
            });
        } catch (e) {
            console.warn('Select2 init warning:', e);
        }
    }
};

// ── Open Modules drawer ──
window.initModulesDrawer = window.openModulesDrawer = function(classId, className) {
    currentClassId = classId;
    const sub = document.getElementById('modulesDrawerSubtitle');
    if (sub) sub.textContent = className;
    const modId = document.getElementById('moduleClassId');
    if (modId) modId.value = classId;
    const quizId = document.getElementById('quizClassId');
    if (quizId) quizId.value = classId;
    const quizForm = document.getElementById('quizDraftForm');
    if (quizForm) quizForm.action = "sample/" + classId;
    const assessId = document.getElementById('assessmentClassId');
    if (assessId) assessId.value = classId;
    const assessForm = document.getElementById('assessmentDraftForm');
    if (assessForm) assessForm.action = "sample/" + classId;
    const annForm = document.getElementById('announcementForm');
    if (annForm) annForm.action = "sample/" + classId + "/announcements";

    window.switchTab('tabUpload', document.querySelector('#dialogModules .rv-tab'));
    ['doc','quiz','assessment'].forEach(window.resetVisibilityPicker);
    window.safeOpenDialog('dialogModules');
};



// -”€-”€ Load current students -”€-”€

function loadCurrentStudents() {

    $('#currentStudentsList').html('<p style="font-size: 16px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p>');



    $.get("sample/" + currentClassId + "/students", function(data) {

        if (!data.students || data.students.length === 0) {

            $('#currentStudentsList').html('<p style="font-size: 16px;color:#ccc;text-align:center;padding:1rem 0;">No students yet.</p>');

            return;

        }



        const programLabel = p => p ? `<span style="font-size: 16px;padding:2px 7px;border-radius:99px;background:#f3f3f3;color:#666;margin-left:6px;">${p.charAt(0).toUpperCase()+p.slice(1)}</span>` : '';



        const html = data.students.map(s => `

            <div class="rv-student-item" data-student-id="${s.id}">

                <span>${s.text}${programLabel(s.program)}</span>

                <button class="rv-btn rv-btn-danger" style="height:28px;padding:0 10px;font-size: 16px;" onclick="removeStudent(this, ${s.id})">

                    Remove

                </button>

            </div>

        `).join('');



        $('#currentStudentsList').html(html);

    }).fail(() => {

        $('#currentStudentsList').html('<p style="font-size: 16px;color:#e24b4a;text-align:center;">Failed to load.</p>');

    });

}



// -”€-”€ Add students -”€-”€

$(document).ready(function () {

    $('#addSelectedStudentsBtn').on('click', function () {

        const selected = $('#studentSelect').val() || [];

        if (selected.length === 0) {

            showUploadValidationToast('Select at least one student.', 'warn');

            return;

        }



        $.post("sample/" + currentClassId + "/students", {

            _token: 'sample',

            student_ids: selected

        }).done(function () {

            loadCurrentStudents();

            $('#studentSelect').val(null).trigger('change');

        }).fail(() => showUploadValidationToast('Failed to add students.', 'error'));

    });

});



// -”€-”€ Remove student -”€-”€

function removeStudent(triggerBtn, id) {

    openManageConfirm('Remove this student from the class?', function () {

        $.ajax({

            url: "sample/" + currentClassId + "/students/" + id,

            type: 'DELETE',

            data: { _token: 'sample' }

        }).done(function () {

            const studentRow = triggerBtn ? triggerBtn.closest('.rv-student-item') : null;

            if (studentRow) {

                studentRow.remove();

            }



            if (!document.querySelector('#currentStudentsList .rv-student-item')) {

                $('#currentStudentsList').html('<p style="font-size: 16px;color:#ccc;text-align:center;padding:1rem 0;">No students yet.</p>');

            }



            showUploadValidationToast('Student removed.', 'success');

            loadCurrentStudents();

        })

          .fail(() => showUploadValidationToast('Failed to remove student.', 'error'));

    });

}



// -”€-”€ Load modules by type (documents / pre-assessments / formal assessments) -”€-”€

function loadModulesForTab(classId, type, containerId) {

    $('#' + containerId).html('<p style="font-size: 16px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p>');



    $.get("sample/" + classId + "/modules/list", function (data) {

        const all = data.modules || [];



        const filtered = all.filter(m => {

            if (type === 'document')         return !m.is_quiz && !m.is_formal_assessment;

            if (type === 'pre_assessment')   return m.type === 'Quiz' && !m.is_formal_assessment;

            if (type === 'formal_assessment') return m.is_formal_assessment;

            return false;

        });



        const badgeMap = {
            'documentsList': 'documentsCountBadge',
            'preAssessmentsList': 'preAssessmentsCountBadge',
            'formalAssessmentsList': 'formalAssessmentsCountBadge'
        };
        const badgeId = badgeMap[containerId];
        if (badgeId) {
            const badgeEl = document.getElementById(badgeId);
            if (badgeEl) badgeEl.textContent = filtered.length > 0 ? `(${filtered.length})` : '(0)';
        }

        if (filtered.length === 0) {
            $('#' + containerId).html('<p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">None yet.</p>');
            return;
        }



const html = filtered.map(m => `
            <div class="rv-module-item">
                <div style="flex:1;">
                    <div class="rv-module-title">${m.title}</div>
                    <div class="rv-module-meta">${m.created_at}${m.due_date ? ' · Due ' + m.due_date : ''}</div>
                </div>

                <div style="display:flex;align-items:center;gap:6px;">

                    ${(type === 'pre_assessment' || type === 'formal_assessment') && m.edit_url

                        ? `<a href="${m.edit_url}" class="rv-btn rv-btn-secondary" style="height:28px;padding:0 10px;font-size: 16px;text-decoration:none;"><i class="fas fa-pen"></i></a>`

                        : ''}

                    ${m.file_path ? `<a href="${m.file_path}" target="_blank" class="rv-btn rv-btn-secondary" style="height:28px;padding:0 10px;font-size: 16px;text-decoration:none;"><i class="fas fa-eye"></i></a>` : ''}

                    <button class="rv-btn rv-btn-danger" style="height:28px;padding:0 10px;font-size: 16px;" onclick="deleteModuleFromTab(${m.id}, '${type}', '${containerId}')">

                        <i class="fas fa-trash"></i>

                    </button>

                </div>

            </div>

        `).join('');



        $('#' + containerId).html(html);

    }).fail(() => {

        $('#' + containerId).html('<p style="font-size: 16px;color:#e24b4a;text-align:center;">Failed to load.</p>');

    });

}



// -”€-”€ Delete module from a typed tab -”€-”€

// ── Lecture Pre-Test / Post-Test list (Assessment tab) ──
function loadLectureAssessments(classId) {
    $('#lectureAssessmentsList').html('<p style="font-size: 16px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p>');

    $.get("sample/" + classId + "/modules/lectures", function (data) {
        const modules = data.modules || [];

        if (modules.length === 0) {
            $('#lectureAssessmentsList').html('<p style="font-size: 16px;color:#ccc;text-align:center;padding:1rem 0;">No Lecture modules yet. Create one from the Content tab first.</p>');
            return;
        }

        const html = modules.map(m => {
            const preLabel = m.has_pre_test ? 'Edit Pre-Test' : 'Add Pre-Test';
            const postLabel = m.has_post_test ? 'Edit Post-Test' : 'Add Post-Test';

            return `
                <div class="rv-module-item">
                    <div style="flex:1;">
                        <div class="rv-module-title">${m.title}</div>
                        <div class="rv-module-meta">
                            ${m.has_pre_test ? '<span style="color:#1d9e75;">&#10003; Pre-Test</span>' : '<span style="color:#ccc;">No Pre-Test</span>'}
                            &nbsp;&bull;&nbsp;
                            ${m.has_post_test ? '<span style="color:#1d9e75;">&#10003; Post-Test</span>' : '<span style="color:#ccc;">No Post-Test</span>'}
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <button type="button" class="rv-btn rv-btn-primary" style="height:28px;padding:0 10px;font-size:14px;" onclick="openLectureContent(${m.id}, ${JSON.stringify(m.title)})">
                            <i class="fas fa-layer-group"></i> Content
                        </button>
                        <a href="${m.pre_test_url}" class="rv-btn rv-btn-secondary" style="height:28px;padding:0 10px;font-size: 14px;text-decoration:none;">${preLabel}</a>
                        <a href="${m.post_test_url}" class="rv-btn rv-btn-secondary" style="height:28px;padding:0 10px;font-size: 14px;text-decoration:none;">${postLabel}</a>
                    </div>
                </div>
            `;
        }).join('');

        $('#lectureAssessmentsList').html(html);
    }).fail(() => {
        $('#lectureAssessmentsList').html('<p style="font-size: 16px;color:#e24b4a;text-align:center;">Failed to load.</p>');
    });
}

function openLectureContent(moduleId, title) {
    $('#lectureContentSubtitle').text(title);
    $('#lectureSubpartModuleId').val(moduleId);
    $('#lectureContentModuleId').val(moduleId);
    $('#lectureContentList').html('<p style="font-size:16px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p>');
    safeOpenDialog('dialogLectureContent');
    loadLectureSubparts(moduleId);
}

$('#lectureContentForm').on('submit', function (event) {
    event.preventDefault();
    const moduleId = $('#lectureContentModuleId').val();
    const formData = new FormData();
    formData.append('_token', 'sample');
    formData.append('title', $('#lectureContentTitle').val());
    formData.append('file', $('#lectureContentFile').get(0).files[0]);

    $.ajax({
        url: `sample/${moduleId}/subparts`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false
    }).done(function () {
        document.getElementById('lectureContentForm').reset();
        loadLectureSubparts(moduleId);
        showUploadValidationToast('Content uploaded.', 'success');
    }).fail(xhr => showUploadValidationToast(xhr.responseJSON?.message || 'Failed to upload content.', 'error'));
});

function loadLectureSubparts(moduleId) {
    $.get(`sample/${moduleId}/subparts`, function (data) {
        const subparts = data.subparts || [];

        if (subparts.length === 0) {
            $('#lectureContentList').html('<p style="font-size:16px;color:#aaa;">No Domains yet.</p>');
            return;
        }

        $('#lectureContentList').html(subparts.map(subpart => `
            <div class="rv-module-item" style="display:block;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                    <div>
                        <div class="rv-module-title">${subpart.order}. ${subpart.title}</div>
                        <div class="rv-module-meta">${subpart.description || 'No description'}</div>
                    </div>
                    <button type="button" class="rv-btn rv-btn-danger" style="height:28px;padding:0 10px;font-size:14px;" onclick="deleteLectureSubpart(${subpart.id}, ${moduleId})"><i class="fas fa-trash"></i></button>
                </div>
                <div id="lessons-${subpart.id}" style="margin:10px 0 0 14px;"><p style="font-size:14px;color:#aaa;">Loading lessons...</p></div>
                <form onsubmit="addLectureLesson(event, ${subpart.id}, ${moduleId})" enctype="multipart/form-data" style="margin:10px 0 0 14px;">
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <input name="title" class="rv-input" required maxlength="150" placeholder="New lesson title" style="flex:1;min-width:180px;">
                        <textarea name="body" class="rv-textarea" placeholder="Lesson body (optional)" style="flex:1;min-width:180px;min-height:34px;"></textarea>
                        <input name="file" type="file" class="rv-input" accept=".pdf,.ppt,.pptx,.docx,.mov" style="flex:1;min-width:200px;padding:7px 12px;">
                        <button type="submit" class="rv-btn rv-btn-secondary" style="height:34px;"><i class="fas fa-plus"></i> Lesson</button>
                    </div>
                </form>
            </div>
        `).join(''));

        subparts.forEach(subpart => loadLectureLessons(subpart.id));
    }).fail(() => $('#lectureContentList').html('<p style="font-size:16px;color:#e24b4a;">Failed to load Domains.</p>'));
}

function loadLectureLessons(subpartId) {
    $.get(`sample/${subpartId}/lessons`, function (data) {
        const lessons = data.lessons || [];
        const html = lessons.length === 0
            ? '<p style="font-size:14px;color:#aaa;">No lessons yet.</p>'
            : lessons.map(lesson => `<div style="display:flex;justify-content:space-between;gap:8px;padding:5px 0;border-bottom:1px solid #f1f1f1;font-size:14px;"><span>${lesson.order}. ${lesson.title}</span><button type="button" class="rv-btn rv-btn-danger" style="height:24px;padding:0 8px;font-size:12px;" onclick="deleteLectureLesson(${lesson.id}, ${subpartId})"><i class="fas fa-trash"></i></button></div>`).join('');
        $(`#lessons-${subpartId}`).html(html);
    });
}

$('#lectureSubpartForm').on('submit', function (event) {
    event.preventDefault();
    const moduleId = $('#lectureSubpartModuleId').val();
    $.post(`sample/${moduleId}/subparts`, {
        _token: 'sample',
        title: $('#lectureSubpartTitle').val(),
        description: $('#lectureSubpartDescription').val()
    }).done(function () {
        $('#lectureSubpartTitle, #lectureSubpartDescription').val('');
        loadLectureSubparts(moduleId);
        showUploadValidationToast('Domain added.', 'success');
    }).fail(() => showUploadValidationToast('Failed to add Domain.', 'error'));
});

function addLectureLesson(event, subpartId, moduleId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('_token', 'sample');

    $.ajax({
        url: `sample/${subpartId}/lessons`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false
    }).done(function () {
        form.reset();
        loadLectureLessons(subpartId);
        showUploadValidationToast('Lesson added.', 'success');
    }).fail(xhr => showUploadValidationToast(xhr.responseJSON?.message || 'Failed to add lesson.', 'error'));
}

function deleteLectureSubpart(subpartId, moduleId) {
    openManageConfirm('Delete this Domain and its Lessons? This cannot be undone.', function () {
        $.ajax({ url: `sample/${subpartId}`, type: 'DELETE', data: { _token: 'sample' } })
            .done(() => { loadLectureSubparts(moduleId); showUploadValidationToast('Domain deleted.', 'success'); })
            .fail(() => showUploadValidationToast('Failed to delete Domain.', 'error'));
    });
}

function deleteLectureLesson(lessonId, subpartId) {
    openManageConfirm('Delete this Lesson? This cannot be undone.', function () {
        $.ajax({ url: `sample/${lessonId}`, type: 'DELETE', data: { _token: 'sample' } })
            .done(() => { loadLectureLessons(subpartId); showUploadValidationToast('Lesson deleted.', 'success'); })
            .fail(() => showUploadValidationToast('Failed to delete lesson.', 'error'));
    });
}

// ── Delete module from a typed tab ──
function deleteModuleFromTab(moduleId, type, containerId) {
    openManageConfirm('Delete this item? This cannot be undone.', function () {

        $.ajax({

            url: "sample/" + moduleId,

            type: 'DELETE',

            data: { _token: 'sample' }

        }).done(() => {

            loadModulesForTab(currentClassId, type, containerId);

            showUploadValidationToast('Item deleted.', 'success');

        })

          .fail(xhr => showUploadValidationToast(xhr.responseJSON?.message || 'Failed to delete.', 'error'));

    });

}



function loadClassAnnouncements(classId) {

    $('#classAnnouncementsList').html('<p style="font-size: 16px;color:#ccc;text-align:center;padding:1rem 0;">Loading...</p>');



    $.get("sample/" + classId + "/announcements/feed", function (data) {

        const items = data.announcements || [];



        const badgeEl = document.getElementById('announcementsCountBadge');
        if (badgeEl) badgeEl.textContent = items.length > 0 ? `(${items.length})` : '(0)';

        if (items.length === 0) {
            $('#classAnnouncementsList').html('<p style="font-size: 14px;color:#9ca3af;text-align:center;padding:1rem 0;">No announcements yet.</p>');
            return;
        }



        const html = items.map(a => {

            const pinned = a.is_pinned ? '<span style="font-size: 16px;padding:2px 8px;border-radius:99px;background:#f8df9f;color:#825b00;">Pinned</span>' : '';

            const edit = a.can_edit

                ? `<button class="rv-btn rv-btn-secondary" style="height:24px;padding:0 8px;font-size: 16px;" onclick='editAnnouncement(${a.id}, ${JSON.stringify(a.message)})'><i class="fas fa-pen"></i></button>`

                : '';

            const del = a.can_delete

                ? `<button class="rv-btn rv-btn-danger" style="height:24px;padding:0 8px;font-size: 16px;" onclick="deleteAnnouncement(${a.id})"><i class="fas fa-trash"></i></button>`

                : '';



            return `

                <div style="border:1px solid #f1f1f1;border-radius:8px;padding:10px;margin-bottom:8px;${a.is_pinned ? 'background:#fffaf0;border-color:#f2d17f;' : ''}">

                    <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:5px;">

                        <div style="font-size: 16px;color:#888;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">

                            ${pinned}

                            <span>${a.author}</span>

                            <span>${a.created_human ?? ''}</span>

                        </div>

                        <div style="display:flex;gap:6px;align-items:center;">

                            ${edit}

                            ${del}

                        </div>

                    </div>

                    <div style="font-size: 16px;color:#222;white-space:pre-wrap;">${$('<div/>').text(a.message).html()}</div>

                </div>

            `;

        }).join('');



        $('#classAnnouncementsList').html(html);

    }).fail(() => {

        $('#classAnnouncementsList').html('<p style="font-size: 16px;color:#e24b4a;text-align:center;padding:1rem 0;">Failed to load announcements.</p>');

    });

}



function deleteAnnouncement(announcementId) {

    openManageConfirm('Delete this announcement?', function () {

        $.ajax({

            url: "sample/" + announcementId,

            type: 'POST',

            data: {

                _token: 'sample',

                _method: 'DELETE'

            }

        }).done(() => {

            loadClassAnnouncements(currentClassId);

            showUploadValidationToast('Announcement deleted.', 'success');

        })

          .fail(() => showUploadValidationToast('Failed to delete announcement.', 'error'));

    });

}



let editingAnnouncementId = null;



function closeAnnouncementEditModal() {

    const overlay = document.getElementById('announcementEditOverlay');

    const input = document.getElementById('announcementEditInput');

    const saveBtn = document.getElementById('announcementEditSaveBtn');



    editingAnnouncementId = null;

    overlay.classList.remove('show');

    overlay.setAttribute('aria-hidden', 'true');

    input.value = '';

    saveBtn.disabled = false;

}



function editAnnouncement(announcementId, currentMessage) {

    editingAnnouncementId = announcementId;



    const overlay = document.getElementById('announcementEditOverlay');

    const input = document.getElementById('announcementEditInput');

    overlay.classList.add('show');

    overlay.setAttribute('aria-hidden', 'false');

    input.value = (currentMessage || '').trim();

    setTimeout(function () {

        input.focus();

        input.setSelectionRange(input.value.length, input.value.length);

    }, 0);

}



function submitAnnouncementEdit() {

    if (!editingAnnouncementId) {

        return;

    }



    const input = document.getElementById('announcementEditInput');

    const saveBtn = document.getElementById('announcementEditSaveBtn');

    const trimmedMessage = input.value.trim();



    if (trimmedMessage === '') {

        showUploadValidationToast('Announcement message is required.');

        input.focus();

        return;

    }



    saveBtn.disabled = true;



    $.ajax({

        url: "sample/" + editingAnnouncementId,

        type: 'POST',

        data: {

            _token: 'sample',

            _method: 'PATCH',

            message: trimmedMessage,

        }

    }).done(() => {

        closeAnnouncementEditModal();

        loadClassAnnouncements(currentClassId);

    })

      .fail(xhr => {

          saveBtn.disabled = false;

          const message = xhr.responseJSON?.message || 'Failed to update announcement.';

          showUploadValidationToast(message);

      });

}



document.addEventListener('keydown', function (e) {

    if (e.key === 'Escape') {

        const overlay = document.getElementById('announcementEditOverlay');

        if (overlay && overlay.classList.contains('show')) {

            closeAnnouncementEditModal();

        }

    }

});



$('#announcementForm').on('submit', function (e) {

    e.preventDefault();



    $.post($(this).attr('action'), $(this).serialize())

        .done(function () {

            document.getElementById('announcementForm').reset();

            loadClassAnnouncements(currentClassId);

        })

        .fail(xhr => {

            const message = xhr.responseJSON?.message || 'Failed to post announcement.';

            showUploadValidationToast(message, 'error');

        });

});



// ── Visibility helpers ──
window.setVisibility = function(btn, form) {
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
};



function addVisChip(form, id, name) {

    if (visSelectedStudents[form][id]) return;

    visSelectedStudents[form][id] = name;



    const chip = document.createElement('span');

    chip.className = 'vis-chip';

    chip.dataset.userId = id;

    chip.innerHTML = name + ' <button type="button" class="vis-chip-remove" onclick="removeVisChip(this,\'' + form + '\',' + id + ')">&times;</button>';

    document.getElementById('visChips_' + form).appendChild(chip);

    document.getElementById('visHint_' + form).style.display = 'none';

}



function removeVisChip(btn, form, id) {

    delete visSelectedStudents[form][id];

    btn.closest('.vis-chip').remove();

    if (Object.keys(visSelectedStudents[form]).length === 0) {

        document.getElementById('visHint_' + form).style.display = '';

    }

}



['doc', 'quiz', 'assessment'].forEach(function (form) {

    const input = document.getElementById('visSearch_' + form);

    const resultsDiv = document.getElementById('visResults_' + form);



    input.addEventListener('input', function () {

        clearTimeout(visDebounceTimers[form]);

        const q = this.value.trim();

        if (q.length < 1) { resultsDiv.style.display = 'none'; return; }



        visDebounceTimers[form] = setTimeout(function () {

            $.get("sample/" + currentClassId + "/students/search", { q: q }, function (data) {

                if (!data || data.length === 0) {

                    resultsDiv.innerHTML = '<div style="padding:10px;font-size: 16px;color:#ccc;text-align:center;">No results</div>';

                    resultsDiv.style.display = 'block';

                    return;

                }



                resultsDiv.innerHTML = data.map(function (s) {

                    const prog = s.program

                        ? '<span class="vis-result-program">' + s.program.charAt(0).toUpperCase() + s.program.slice(1) + '</span>'

                        : '';

                    const idnum = s.idnumber ? ' <span class="vis-result-id">(' + s.idnumber + ')</span>' : '';

                    return '<div class="vis-result-item" data-id="' + s.id + '" data-name="' + $('<span>').text(s.name).html() + '">'

                        + '<span class="vis-result-name">' + $('<span>').text(s.name).html() + idnum + '</span> '

                        + '<span class="vis-result-email">' + $('<span>').text(s.email).html() + '</span>'

                        + prog

                        + '</div>';

                }).join('');

                resultsDiv.style.display = 'block';

            });

        }, 300);

    });



    $(document).on('click', '#visResults_' + form + ' .vis-result-item', function () {

        addVisChip(form, $(this).data('id'), $(this).data('name'));

        resultsDiv.style.display = 'none';

        input.value = '';

    });



    input.addEventListener('blur', function () {

        setTimeout(function () { resultsDiv.style.display = 'none'; }, 200);

    });

});



function injectVisUserIds(form, formData) {

    const ids = Object.keys(visSelectedStudents[form]);

    ids.forEach(function (id) {

        if (formData instanceof FormData) {

            formData.append('visible_user_ids[]', id);

        }

    });

}



function injectVisHiddenInputs(form, formEl) {

    formEl.querySelectorAll('input[name="visible_user_ids[]"]').forEach(function (el) { el.remove(); });

    Object.keys(visSelectedStudents[form]).forEach(function (id) {

        const inp = document.createElement('input');

        inp.type = 'hidden';

        inp.name = 'visible_user_ids[]';

        inp.value = id;

        formEl.appendChild(inp);

    });

}



let uploadToastTimer = null;

let manageConfirmAction = null;



function closeManageConfirm() {

    const overlay = document.getElementById('mcConfirmOverlay');

    if (!overlay) {

        return;

    }



    overlay.classList.remove('show');

    overlay.setAttribute('aria-hidden', 'true');

    manageConfirmAction = null;

}



function openManageConfirm(message, onConfirm) {

    const overlay = document.getElementById('mcConfirmOverlay');

    const messageEl = document.getElementById('mcConfirmMessage');

    const proceedBtn = document.getElementById('mcConfirmProceedBtn');

    if (!overlay || !messageEl || !proceedBtn) {

        return;

    }



    const activeDialogPanel = document.querySelector('dialog[open] .rv-dialog-panel')

        || document.querySelector('#dialogModules .rv-dialog-panel')

        || document.querySelector('#dialogStudents .rv-dialog-panel');

    if (activeDialogPanel && overlay.parentElement !== activeDialogPanel) {

        activeDialogPanel.appendChild(overlay);

    }



    messageEl.textContent = message;

    manageConfirmAction = onConfirm;

    overlay.classList.add('show');

    overlay.setAttribute('aria-hidden', 'false');



    proceedBtn.onclick = function () {

        const action = manageConfirmAction;

        closeManageConfirm();

        if (typeof action === 'function') {

            action();

        }

    };

}



function showUploadValidationToast(message, type) {

    type = type || 'error';

    let toast = document.getElementById('mcUploadToast');

    const activeDialogPanel = document.querySelector('dialog[open] .rv-dialog-panel')

        || document.querySelector('#dialogModules .rv-dialog-panel')

        || document.querySelector('#dialogStudents .rv-dialog-panel');

    if (!toast) {

        toast = document.createElement('div');

        toast.id = 'mcUploadToast';

        toast.className = 'mc-toast';

        (activeDialogPanel || document.body).appendChild(toast);

    } else if (activeDialogPanel && toast.parentElement !== activeDialogPanel) {

        activeDialogPanel.appendChild(toast);

    }



    toast.classList.remove('success', 'error', 'warn');

    toast.classList.add(type);

    toast.textContent = message;

    toast.classList.add('show');



    if (uploadToastTimer) {

        clearTimeout(uploadToastTimer);

    }



    uploadToastTimer = setTimeout(function () {

        toast.classList.remove('show');

    }, 2600);

}



// Intercept quiz/assessment form submits to inject visible_user_ids

$('#quizDraftForm').on('submit', function (e) {

    const visibility = document.getElementById('visInput_quiz').value;

    if ((visibility === 'selected' || visibility === 'except') && Object.keys(visSelectedStudents['quiz']).length === 0) {

        e.preventDefault();

        showUploadValidationToast('Please select at least one student.');

        return;

    }



    injectVisHiddenInputs('quiz', this);

});



$('#assessmentDraftForm').on('submit', function (e) {

    const visibility = document.getElementById('visInput_assessment').value;

    if ((visibility === 'selected' || visibility === 'except') && Object.keys(visSelectedStudents['assessment']).length === 0) {

        e.preventDefault();

        showUploadValidationToast('Please select at least one student.');

        return;

    }



    injectVisHiddenInputs('assessment', this);

});



function addLectureUploadField() {
    const row = document.createElement('div');
    row.className = 'lecture-content-upload-row';
    row.style.cssText = 'display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:8px;';
    row.innerHTML = `
        <input type="text" name="subpart_titles[]" class="rv-input" required maxlength="150" placeholder="Subdomain title" style="grid-column:1 / -1;">
        <input type="file" name="files[]" class="rv-input" accept=".pdf,.ppt,.pptx,.docx,.mov" required style="padding:7px 12px;">
        <button type="button" class="rv-btn rv-btn-danger lecture-remove-upload" style="height:38px;padding:0 10px;" onclick="this.closest('.lecture-content-upload-row').remove()"><i class="fas fa-trash"></i></button>
    `;
    document.getElementById('lectureContentUploadFields').appendChild(row);
}

// -”€-”€ Upload module form -”€-”€

$('#moduleUploadForm').on('submit', function (e) {

    e.preventDefault();



    const visibility = document.getElementById('visInput_doc').value;

    if ((visibility === 'selected' || visibility === 'except') && Object.keys(visSelectedStudents['doc']).length === 0) {

        showUploadValidationToast('Please select at least one student.');

        return;

    }



    const formData = new FormData(this);

    injectVisUserIds('doc', formData);



    $.ajax({

        url: "sample/" + currentClassId + "/modules",

        type: 'POST',

        data: formData,

        processData: false,

        contentType: false,

        headers: { 'X-CSRF-TOKEN': 'sample' }

    }).done(function (res) {

        showUploadValidationToast(res.success || 'Lecture created!', 'success');

        document.getElementById('moduleUploadForm').reset();

        document.querySelectorAll('#lectureContentUploadFields .lecture-content-upload-row').forEach((row, index) => {
            if (index > 0) {
                row.remove();
            }
        });

        const firstUploadRow = document.querySelector('#lectureContentUploadFields .lecture-content-upload-row');
        firstUploadRow?.querySelector('.lecture-remove-upload')?.style.setProperty('display', 'none');

        resetVisibilityPicker('doc');

        loadModulesForTab(currentClassId, 'document', 'documentsList');

    }).fail(xhr => showUploadValidationToast('Upload failed: ' + (xhr.responseJSON?.message || 'Unknown error'), 'error'));

});



// -"€-"€ Join Link functionality -"€-"€

function generateJoinLink() {

    const genBtn = document.getElementById('joinLinkGenerateBtn');

    const copyBtn = document.getElementById('joinLinkCopyBtn');

    const input = document.getElementById('joinLinkInput');



    genBtn.classList.add('generating');

    genBtn.disabled = true;



    fetch(`/classes/${currentClassId}/join-link`, {

        method: 'POST',

        headers: {

            'X-CSRF-TOKEN': 'sample',

            'Accept': 'application/json'

        }

    })

    .then(r => r.json())

    .then(data => {

        if (data.success) {

            input.value = data.url;

            copyBtn.disabled = false;

        } else {

            input.value = 'Error: ' + (data.message || 'Failed to generate');

        }

    })

    .catch(() => {

        input.value = 'Error generating link';

    })

    .finally(() => {
        genBtn.classList.remove('generating');
        genBtn.disabled = false;
    });
}

window.copyJoinLink = function() {
    const input = document.getElementById('joinLinkInput');
    const btn = document.getElementById('joinLinkCopyBtn');
    if (!input || !input.value) return;

    input.select();
    input.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(input.value).then(() => {
        if (btn) {
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fas fa-copy"></i>';
            }, 2000);
        }
    });
};

document.addEventListener('DOMContentLoaded', function () {
    const proceedBtn = document.getElementById('deleteClassConfirmProceedBtn');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function() {
            if (currentDeleteForm) {
                currentDeleteForm.submit();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.rv-drawer.open').forEach(d => {
                window.safeCloseDialog(d.id);
            });
            window.closeDeleteClassConfirm();
        }
    });

    /* if */
});
