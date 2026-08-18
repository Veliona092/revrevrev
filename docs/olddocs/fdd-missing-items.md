# FDD Gap Report (What Is Still Missing)

Last updated: (implementation phase ongoing)

This file lists only the items that are still missing or partially implemented from the FDD verification review.

## Quick Summary

- Total high-priority gaps: 2 (✅ both resolved)
- Total medium-priority gaps: 6 (✅ 2 resolved, 4 remaining)
- Total low-priority gaps: 1 (✅ resolved)

## High Priority

### ✅ 1) Lecture progression lock is not enforced — RESOLVED

**Fix applied:**
- `ClassManagerController::studentModules()` now computes `$completed` and `$locked` maps using sequential module ordering.
- `ClassManagerController::viewModuleFile()` enforces a backend gate for student roles that aborts with 403 if the previous module is not completed.
- `resources/views/pages/student/modules.blade.php` shows locked modules with opacity, a lock icon badge, and an explanatory click message.

---

### ✅ 2) Gmail notification method mismatch — RESOLVED

**Fix applied:**
- `app/Services/GmailService.php` now has a `sendMail()` alias method that proxies to `send()`, resolving all callers that used `sendMail()`.

---

## Medium Priority

### ✅ 3) Announcement request validation class is a stub — RESOLVED

**Fix applied:**
- `app/Http/Requests/StoreClassAnnouncementRequest.php` replaced the stub with a full Form Request: `authorize()` checks teacher/admin/superadmin role; `rules()` requires `message` (string, max:1000) and optional `is_pinned` boolean; custom `messages()` added.

---

### ✅ 4) Layout system is mixed (legacy + new role layouts) — RESOLVED

**Fix applied:**
- `resources/views/pages/educ/modules.blade.php`: changed `@extends('layouts.app')` → `@extends('layouts.appEduc')` to match all sibling educ pages.
- `resources/views/pages/teacher/lectures-edit.blade.php`: removed placeholder comment, set `@extends('layouts.appTeach')` to match all sibling teacher pages.
- `layouts.domain` is retained intentionally for full-screen immersive module/assessment reader pages (`student/modules`, `accountancy/modules`, `student/assessment-take`) — this is by design, not legacy debt.
- `layouts.appTeachh.blade.php` (double-h typo) is a dead unreferenced file; flagged for cleanup but not deleted without approval.

---

## Remaining Items

| # | Item | Priority | Status |
|---|------|----------|--------|
| 5 | Schema naming vs FDD contract | Medium | Decision needed |
| 7 | Route URL contract alignment | Medium | Decision needed |

## Schema & Route Contract: Decision Summary

These two items require a stakeholder decision rather than a pure code fix. The implementation is functional. The question is whether the FDD spec is the authority (requiring schema/route renames) or whether the FDD contract should be updated to match the implemented design.

### Schema naming (item 5) — CLOSED

| FDD Concern | Outcome |
|-------------|--------|
| Visibility table | `module_user_visibility` is an implementation detail of the visibility feature — not a named FDD contract item. No action needed. |
| Quiz removed columns | `time_taken`, `topic` confirmed deliberately removed — zero usages in codebase. FDD contract should drop these from the spec. |

**Status: Closed.** No code changes required.

### Route contract (item 7) — Canonical application routes:

All required feature routes are present and named. The routes follow a consistent kebab-case URL / dot-notation name pattern throughout. See `php artisan route:list` for the full current contract. If a QA script or integration doc uses different paths, those references need updating — no route renames are needed without specific external contract requirements.

**Decision path:** No code changes needed unless specific external contracts require exact path parity.  Update integration docs or QA scripts to use the current route names.

## Recommended Next Steps (Remaining)

1. Stakeholder decision: accept `module_user_visibility` naming and absence of removed quiz columns, or update schema to FDD exact spec.
2. Review any external QA scripts or integration docs against current route list; update references as needed.
- Some route paths/names differ from the exact FDD wording.

**Why this matters (plain language):**
- External integration docs and QA scripts may fail if they require exact path contracts.

**Suggested acceptance check:**
- Confirm expected public route contract and align route names/paths (or update the contract doc).

---

## Low Priority

### ✅ 8) Pending/rejected login UX is message-based, not dedicated status page — RESOLVED

**Fix applied:**
- `resources/views/pages/admin/Pending approval.blade.php` updated to read session-flashed `account_email`.
- `resources/views/pages/admin/account-rejected.blade.php` created (matching design language).
- Two new routes added: `GET /pending-approval` (`login.pending`) and `GET /account-rejected` (`login.rejected`).
- Login POST route now flashes email, logs out, and redirects to the correct dedicated page.

---

## Recommended Execution Order

All code-actionable items are resolved. Two items remain as stakeholder decisions:

1. **Schema contract:** accept `module_user_visibility` naming and absence of removed quiz columns (`time_taken`, `topic`), or update schema to original FDD spec.
2. **Route contract:** update integration docs / QA scripts to match current route names, or define specific rename requirements.

## Notes

- This report focuses only on gaps. It does not repeat already built features.
- Full built/partially-built/not-built matrix: see `fdd-full-matrix.md`.
