# Manual Test Plan — Reviso Implementation Pass

**Date:** March 30, 2026  
**Scope:** All features and fixes applied in the current implementation pass  
**Prerequisite:** App is running locally with at least one user of each role seeded or created manually.

---

## 1. Pending Approval Page

**What was done:** A dedicated `/pending-approval` page was added for users whose account status is `pending`. Previously they were shown a generic error.

### Steps
1. Register a new account (do not approve it in admin).
2. Attempt to log in with those credentials.
3. **Expect:** You are redirected to `/pending-approval` — a styled page saying your account is awaiting approval.
4. Check that the page has a meaningful message and does not throw a 404 or redirect to `/login` in a loop.
5. Log in as admin, reject the pending user.
6. Log out, attempt to log in as the rejected user.
7. **Expect:** You are redirected to `/account-rejected` — a separate page explaining the account was rejected.
8. Confirm both pages are accessible without authentication (guest-accessible).

---

## 2. Sequential Module Lock

**What was done:** Students cannot open module N+1 until module N is marked completed. Lock is enforced in both the backend (403) and visually in the UI (lock icon + disabled pointer).

### Steps
1. Log in as a student enrolled in a class that has at least 2 modules.
2. Navigate to the class module list.
3. **Expect:** Module 1 is clickable (unlocked). Module 2 shows a lock icon and is visually greyed out (45% opacity, no click).
4. Click on the locked module link.
5. **Expect:** The click does nothing in the UI (pointer-events: none or href removed).
6. Attempt to navigate directly to the locked module URL (e.g. `/modules/2/view`).
7. **Expect:** Server returns 403 Forbidden (or redirects to a 403 page) — not 200.
8. Go back and complete Module 1 (scroll to bottom or submit progress).
9. Refresh the module list.
10. **Expect:** Module 2 is now unlocked (no lock icon, clickable).
11. Click Module 2 — it should open normally.

---

## 3. Class Announcements

**What was done:** `StoreClassAnnouncementRequest` was implemented with authorization and validation rules.

### Steps
1. Log in as a teacher.
2. Navigate to a class you manage.
3. Create a new announcement with a valid message (under 1000 characters).
4. **Expect:** Announcement is created and appears in the feed.
5. Create an announcement with an empty message.
6. **Expect:** Validation error — message is required.
7. Create an announcement with a message over 1000 characters.
8. **Expect:** Validation error — message too long.
9. Toggle the "pin" option on and create.
10. **Expect:** Pinned announcement appears at the top of the feed (or marked as pinned).
11. Log in as a student in the same class.
12. Navigate to announcements.
13. **Expect:** Student can view announcements but has no create/delete/edit controls.
14. Attempt to POST directly to the store announcement route as a student (e.g. via curl or browser dev tools).
15. **Expect:** 403 Forbidden.

---

## 4. Announcement Mark as Read

**What was done:** Announcement read tracking is wired to the `AnnouncementRead` model with the `reads()` relation.

### Steps
1. Log in as a student with unread announcements in a class.
2. Navigate to the announcements page.
3. **Expect:** Unread count badge is visible (e.g. "3 unread").
4. Click an announcement to read it.
5. **Expect:** That announcement is marked as read; the badge count decreases.
6. Refresh the page.
7. **Expect:** Previously read announcement is still marked as read (persisted in DB).
8. Mark all as read.
9. **Expect:** Badge disappears or shows 0.

---

## 5. Gmail Email Sending

**What was done:** `GmailService::sendMail()` alias was confirmed to call `send()` correctly. Previously, calls to `sendMail()` could silently fail if the alias was missing.

### Steps
1. Register a new account with a real email address.
2. **Expect:** Verification email arrives in the inbox within a few minutes.
3. Click the verification link.
4. **Expect:** Account is marked as verified.
5. Trigger a password reset (Forgot Password flow).
6. **Expect:** Temporary password arrives in the inbox.
7. Log in with the temporary password.
8. **Expect:** Login succeeds. (If prompted to change password — go through that flow too.)
9. As admin, approve a user.
10. **Expect:** Approval email arrives in the user's inbox.
11. As admin, reject a user.
12. **Expect:** Rejection email arrives in the user's inbox.

---

## 6. Model Relation Aliases

**What was done:** `Announcement::author()`, `QuizAttempt::answers()`, and `Module::attempts()` relation aliases were added. These prevent `BadMethodCallException` when any view or controller calls these relations.

### Steps
1. Log in as a teacher.
2. Navigate to the class announcements list.
3. **Expect:** Each announcement shows the author's name correctly (coming from `author()` relation — no errors in logs).
4. Navigate to the quiz insights or performance page for a module.
5. **Expect:** Page loads without errors; student attempt data is displayed correctly.
6. Open `storage/logs/laravel.log`.
7. **Expect:** No `BadMethodCallException` or "Call to undefined relationship" errors appearing after the above steps.

---

## 7. Layout Consistency by Role

**What was done:** Education-track student pages were confirmed to use `appEduc` layout; teacher lecture-edit page was confirmed to use `appTeach` layout.

### Steps
1. Log in as an **Education (educ) track student**.
2. Navigate to `/my-classes` (or the modules page).
3. **Expect:** The sidebar and navigation match the Education student layout — correct role label, correct menu items.
4. Navigate to other pages available to this role (profile, progress, assessment).
5. **Expect:** Consistent header/sidebar across all pages — no page showing a different role's nav.
6. Log in as a **Teacher**.
7. Navigate to the lecture edit page (if applicable).
8. **Expect:** Page uses the teacher layout — teacher sidebar and nav visible, not the admin layout.
9. Log in as an **Admin**.
10. Navigate to the user management page.
11. **Expect:** Admin layout — correct nav items for admin role, not teacher's nav.
12. Log in as a **Psych track student** and an **Accountancy track student** separately.
13. **Expect:** Each sees their respective layout (`appPsych`, `appAcc`) with no cross-contamination.

---

## 8. Admin Approval Queue

**What was done:** Admin approval flows (approve, reject, approve-all, approve-many) are backed by `AdminApprovalController`.

### Steps
1. Create 3 or more pending user accounts (register without approving).
2. Log in as admin.
3. Navigate to `/admin/approvals`.
4. **Expect:** All pending accounts are listed.
5. Approve one user individually.
6. **Expect:** That user disappears from the queue; they can now log in.
7. Reject one user individually.
8. **Expect:** Rejected user disappears from the queue; they see the rejection page on login.
9. Use "Approve All" to approve remaining pending users.
10. **Expect:** Queue is now empty.
11. Create more pending users, then use "Approve Many" (select checkboxes).
12. **Expect:** Only selected users are approved; non-selected remain pending.

---

## 9. AI-Gated Features

**What was done:** AI features are gated by `AiSettingsResolver`. Superadmin controls global defaults; admin can override per-class.

### Steps
1. Log in as superadmin.
2. Navigate to AI settings and **disable** quiz generation globally.
3. Log in as a teacher.
4. Go to a module with a PDF uploaded.
5. **Expect:** "Generate Quiz" button is hidden or disabled. No AI generation is triggered.
6. Log back in as admin or superadmin.
7. Enable quiz generation for the specific class (class-level override).
8. Log back in as the teacher.
9. **Expect:** "Generate Quiz" button is now visible and functional for that class.
10. Trigger quiz generation.
11. **Expect:** Quiz questions are generated and saved. No error thrown.
12. Trigger "Generate Insights" after a quiz has been taken.
13. **Expect:** Insights narrative appears in the teacher's performance view.

---

## 10. Student Performance & AI Summary

**What was done:** `PerformanceController::refreshAiSummary()` generates a class-level AI narrative summary.

### Steps
1. Have at least 2 students take a quiz in a class.
2. Log in as the teacher.
3. Navigate to `/student-performance/{class}`.
4. **Expect:** A table or list showing student scores is visible.
5. Click "Refresh AI Summary".
6. **Expect:** A narrative summary of class performance appears or updates on the page. No error.
7. Navigate to the individual student assessment analysis page.
8. **Expect:** Per-student breakdown is visible, and if AI analysis was generated, it displays correctly.

---

## 11. Module Push (For Admin)

**Regression check — no new code, but verify nothing broke.**

### Steps
1. Log in as admin.
2. Navigate to user management (`/admin/users`).
3. Search for a user by name or email.
4. **Expect:** Results filter correctly.
5. Toggle status (Active → Inactive) on a user.
6. **Expect:** Status updates; that user can no longer log in (if inactive).
7. Reset a user's password.
8. **Expect:** Temporary password email is sent; user can log in with it.
9. Export users.
10. **Expect:** CSV/export file downloads successfully with user data.

---

## Quick Regression Checklist

After all tests above, do a final smoke pass:

| Check | Pass/Fail |
|-------|-----------|
| Student can log in and see class list | |
| Student can view an unlocked module | |
| Student can take a quiz and see their score | |
| Teacher can create a class | |
| Teacher can upload a module file | |
| Teacher can view performance tracker | |
| Admin can log in and see dashboard | |
| Admin approval queue loads | |
| Superadmin AI settings page loads | |
| Profile page saves changes | |
| No 500 errors in `storage/logs/laravel.log` after all above | |
| No unread error badge or broken layout across role switches | |
