# Legacy Browser Popup Report

Date: 2026-04-01

## Summary
The following active pages still use native browser dialogs (`alert`, `confirm`, `prompt`) that produce the old default "localhost says" popup UI.

## Files, Functions, and Handlers Using Legacy Popups

1. `resources/views/pages/teacher/manageclass.blade.php`
- `$(document).ready(...)` -> `#addSelectedStudentsBtn` click handler
	- `alert('Select at least one student.')`
	- `alert('Failed to add students.')`
- `removeStudent(id)`
	- `confirm('Remove this student?')`
- `deleteModuleFromTab(moduleId, type, containerId)`
	- `confirm('Delete this item? This cannot be undone.')`
	- `alert(xhr.responseJSON?.message || 'Failed to delete.')`
- `deleteAnnouncement(announcementId)`
	- `confirm('Delete this announcement?')`
	- `alert('Failed to delete announcement.')`
- `$('#announcementForm').on('submit', ...)` fail callback
	- `alert(message)`
- `$('#moduleUploadForm').on('submit', ...)` AJAX callbacks
	- `alert(res.success || 'Uploaded!')`
	- `alert('Upload failed: ...')`

2. `resources/views/pages/student/modules.blade.php`
- `handleTab()`
	- `alert(...)` warning popup
	- `alert(...)` auto-fail popup

3. `resources/views/pages/student/assessment-take.blade.php`
- `handleTab()`
	- `alert(...)` warning popup
	- `alert(...)` auto-fail popup

4. `resources/views/pages/teacher/modules-list.blade.php`
- `.delete-module` click listener (`addEventListener('click', ...)`)
	- `confirm('Delete this module? This cannot be undone.')`
	- `alert('Failed to delete module.')`

5. `resources/views/pages/teacher/quiz-create.blade.php`
- `#aiQuizForm` submit handler
	- `alert(response.message || 'No questions returned.')`
	- `alert('Failed to generate questions.')`

6. `resources/views/pages/chat/teacher.blade.php`
- `.ch-search-item` click handler fail callback (start chat)
	- `alert(xhr.responseJSON?.message || 'Unable to start chat.')`
- `sendMessage()` fail callback
	- `alert('Message failed to send.')`

7. `resources/views/pages/teacher/lectures.blade.php`
- Inline delete button handler
	- `onclick="return confirm('Delete this lecture? This cannot be undone.')"`

## Notes
- `prompt(...)` for announcement editing was already replaced earlier with a styled custom modal.
- Some third-party bundled assets in `public/tinymce/**` include internal alert/confirm APIs, but those are vendor files and not app UI logic.

## Proper CSS Replacement Standard

Use a shared, styled UI pattern instead of native browser dialogs:

1. **Toast** for non-blocking feedback (`alert(...)` replacements)
2. **Confirm Modal** for destructive actions (`confirm(...)` replacements)
3. **Edit Modal / Inline Form** for input requests (`prompt(...)` replacements)

### Recommended CSS (shared)

```css
.ui-toast {
	position: fixed;
	top: 16px;
	right: 16px;
	min-width: 260px;
	max-width: 380px;
	padding: 12px 14px;
	border-radius: 10px;
	border: 1px solid #e4e4e4;
	background: #fff;
	color: #222;
	box-shadow: 0 12px 28px rgba(0,0,0,.16);
	opacity: 0;
	transform: translateY(-8px);
	transition: .2s ease;
	z-index: 12000;
}

.ui-toast.show {
	opacity: 1;
	transform: translateY(0);
}

.ui-toast.success { border-color: #cbe9d7; background: #edf9f1; color: #155b3a; }
.ui-toast.error { border-color: #f3c5c5; background: #fff3f3; color: #8d2b2b; }
.ui-toast.warn { border-color: #f2deaf; background: #fff9ea; color: #7b5a10; }

.ui-modal-overlay {
	position: fixed;
	inset: 0;
	display: none;
	align-items: center;
	justify-content: center;
	background: rgba(0,0,0,.42);
	z-index: 12000;
	padding: 16px;
}

.ui-modal-overlay.show { display: flex; }

.ui-modal {
	width: min(460px, 96vw);
	background: #fff;
	border: 1px solid #ececec;
	border-radius: 12px;
	box-shadow: 0 20px 50px rgba(0,0,0,.22);
	padding: 14px;
}

.ui-modal-title { margin: 0 0 6px; font-size: 14px; font-weight: 600; color: #111; }
.ui-modal-text { margin: 0; font-size: 13px; color: #555; }

.ui-modal-actions {
	margin-top: 12px;
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
```

### Replacement Mapping (by behavior)

- `alert('Success...')` -> `showToast('Success...', 'success')`
- `alert('Failed...')` -> `showToast('Failed...', 'error')`
- `confirm('Delete...?')` -> `openConfirmModal({...})` and run delete only on confirm callback
- `prompt(...)` -> dedicated edit modal with textarea/input and validation

### Priority Order

1. `teacher/manageclass.blade.php` (highest number of remaining legacy popups)
2. `teacher/modules-list.blade.php`
3. `teacher/quiz-create.blade.php`
4. `chat/teacher.blade.php`
5. `student/modules.blade.php` and `student/assessment-take.blade.php` (anti-cheat warnings)
6. `teacher/lectures.blade.php`

### UX Rule

For destructive actions, always use explicit button labels (`Cancel`, `Delete`) and never rely on browser-native confirm dialogs.
