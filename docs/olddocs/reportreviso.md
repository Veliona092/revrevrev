# Reviso — Full System Report

**Date:** March 30, 2026  
**Version:** Post-FDD implementation pass  
**Status:** All core features built and verified

---

## 1. System Overview

Reviso is a web-based learning management system (LMS) built for academic institutions. It supports multiple student tracks (Accountancy, Education, Psychology), teacher-led class management, quiz and assessment delivery, AI-generated content insights, and a full admin/superadmin governance layer.

The system is designed around a role-based access model where each user belongs to one of: `student`, `accountancy`, `educ`, `psych`, `teacher`, `admin`, or `superadmin`.

---

## 2. Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Language | PHP | 8.2+ |
| Framework | Laravel | v12 |
| Frontend Reactivity | Blade + jQuery + AJAX | — |
| Database | MySQL/MariaDB in current dev environment; Laravel fallback default is SQLite if env is unset | — |
| Email | Gmail API (OAuth2) | google/apiclient 2.19 |
| AI Generation | Cloudflare Workers AI  | — |
| PDF Parsing | smalot/pdfparser | — |
| JWT Auth | firebase/php-jwt | ^7.0 |
| Role/Permission | spatie/laravel-permission | — |
| PDF Viewer | PDF.js (bundled) | — |
| CSS/JS | Vite + Bootstrap 5 | — |
| Testing | PHPUnit | v11 |

---

## 3. Architecture

### 3.1 Application Structure

```
app/
  Http/
    Controllers/    — 17 controllers (see §5)
    Requests/       — 3 Form Request classes
    Middleware/     — UpdateLastSeen
  Models/           — 14 Eloquent models (see §4)
  Services/         — 4 service classes (see §6)
  Providers/        — AppServiceProvider, AuthServiceProvider
  Auth/             — PasswordResetLinkController
  Mail/             — VerifyEmail
  Notifications/    — CustomResetPasswordNotification, ResetPasswordNotification
database/
  migrations/       — 41 migrations
  factories/        — UserFactory
  seeders/
routes/
  web.php           — 122 named routes
resources/
  views/
    layouts/        — appAdmin, appTeach, appEduc, appPsych, appAcc, app, domain, guest, quiz, sidebar
    pages/          — admin, teacher, student, accountancy, educ, psych, chat, announcements, users
tests/
  Feature/          — 15 feature test files
  Unit/             — 1 unit test file
```

### 3.2 Role Hierarchy

```
superadmin
  └── admin
        └── teacher
              └── student (tracks: accountancy, educ, psych)
```

Each role has dedicated dashboard, layout, and navigation. Superadmins can manage admins; admins manage teachers and students; teachers manage classes and modules; students consume learning content.

---

## 4. Data Models

| Model | Table | Key Relations |
|-------|-------|---------------|
| `User` | `users` | hasMany Classes (as teacher), belongsToMany Classes (as student), hasMany ModuleProgress, hasMany QuizAttempts |
| `ClassModel` | `classes` | belongsTo User (teacher), belongsToMany Users (students), hasMany Modules, hasMany Announcements |
| `Module` | `modules` | belongsTo ClassModel, hasMany QuizQuestions, hasMany ModuleProgress, hasMany QuizAttempts, belongsToMany Users (visibility) |
| `ModuleProgress` | `module_progress` | belongsTo User, belongsTo Module — tracks completion per user |
| `QuizQuestion` | `quiz_questions` | belongsTo Module, hasMany QuizAnswers |
| `QuizAttempt` | `quiz_attempts` | belongsTo Module, belongsTo User, hasMany QuizAnswers (`answers()`) |
| `QuizAnswer` | `quiz_answers` | belongsTo QuizAttempt, belongsTo QuizQuestion |
| `Announcement` | `announcements` | belongsTo User (`author()` alias), belongsTo ClassModel, hasMany AnnouncementReads |
| `AnnouncementRead` | `announcement_reads` | belongsTo Announcement, belongsTo User |
| `Chat` | `chats` | belongsTo User (initiator), belongsTo User (recipient), hasMany ChatMessages |
| `ChatMessage` | `chat_messages` | belongsTo Chat, belongsTo User |
| `AiSetting` | `ai_settings` | Global and per-class AI configuration |
| `Signup` | `signups` | Pre-registration record before User creation |

### 4.1 Key Schema Notes

- `module_user_visibility` — pivot table controlling per-student module visibility (implementation detail, not a named FDD table)
- `class_user` — pivot table for class enrollment with `assessment_ai_analysis` column
- `quiz_attempts` — includes `attempt_count` and cached AI insight columns (`ai_strong`, `ai_weak`, `ai_recommendation`)
- `announcement_reads` — tracks per-user read state for announcement badge counts
- Removed columns confirmed unused and dropped: `time_taken` (quiz_attempts), `topic` (quiz_questions), `scroll_position`/`scroll_height` (module_progress), `title` (chats)

### 4.2 Legacy / Non-Core Models

| Model | Status | Notes |
|-------|--------|-------|
| `Lecture` | Legacy | Old lecture-era model still present in repository; current Reviso learning-content flow is centered on `Module` |

---

## 5. Controllers

| Controller | Responsibility |
|-----------|---------------|
| `ClassManagerController` | Class CRUD, module CRUD, student enrollment, module lock/prerequisite gate, file delivery |
| `AdminApprovalController` | Pending user queue, approve/reject/approve-all/approve-many actions |
| `AdminDashboardController` | Admin dashboard overview |
| `AdminUserController` | User listing, search, export, status toggle, password reset |
| `AiSettingsController` | Global and class-level AI settings (superadmin + admin) |
| `AnnouncementController` | Class announcements CRUD, feed, mark-read |
| `ChatController` | Conversations list, start chat, send/receive messages |
| `PerformanceController` | Class progress tracker, student performance detail, assessment analysis, AI summary generation |
| `ProfileController` | Profile view, password/email/program updates, program unlock |
| `QuizController` | Quiz draft creation, answer submission, scoring, AI insights generation |
| `RegisterController` | Account registration handling |
| `StudentAssessmentController` | Student assessment index and take views |
| `StudentDashboardController` | Role-aware student dashboard (accountancy/educ/psych variants) |
| `StudentProgressController` | Student self-service progress view |
| `TeacherDashboardController` | Teacher dashboard |

### 5.1 Legacy / Debug Controllers

| Controller | Status | Notes |
|-----------|--------|-------|
| `LectureController` | Legacy | Old lecture-era controller still routed in the repository but not part of the current module-driven Reviso flow |
| `TestAiController` | Debug | Dev/test AI endpoint, not part of normal product-facing flows |

---

## 6. Services

| Service | Purpose |
|---------|---------|
| `GmailService` | Sends transactional emails via Gmail OAuth2 API. Methods: `send()` (primary), `sendMail()` (alias for legacy callers) |
| `GmailSender` | Sends email verification links during signup |
| `CloudflareAI` | Calls Cloudflare Workers AI. Methods: `run(model, payload)` for quiz/insights generation; `generateSummary(stats)` for class performance summaries |
| `AiSettingsResolver` | Reads global and per-class AI configuration. Gates feature availability, resolves model/token/prompt settings per context |

---

## 7. Form Requests

| Request Class | Guards |
|--------------|--------|
| `StoreClassAnnouncementRequest` | Authorizes teacher/admin/superadmin; validates `message` (required, max 1000), `is_pinned` (nullable boolean) |
| `UpdateClassAiSettingsRequest` | Validates class-level AI settings fields |
| `UpdateGlobalAiSettingsRequest` | Validates global AI settings fields |

---

## 8. Authentication & Authorization

- **Login:** ID number + password. Pending/rejected users are blocked before session creation and redirected to dedicated status pages (`/pending-approval`, `/account-rejected`).
- **Registration:** Signup creates a `Signup` record; email verification required before the account becomes active; admin approval also required (status moves: `pending` → `active` or `rejected`).
- **Session:** Laravel session-based auth with CSRF protection. Throttled login at 10 attempts/minute.
- **Password reset:** Temporary password sent via Gmail API; user prompted to change on next login.
- **Email verification:** Token-based (`/email/verify/{token}`).
- **Role guard:** Middleware and inline checks throughout controllers enforce role boundaries. Spatie permission package used for role assignment.
- **Module access gate:** Students cannot open a module if the preceding module is not completed (sequential lock enforced at controller level with 403 response, and visually in the UI).

---

## 9. Representative Route Summary (122 total)

This section highlights the primary application routes and selected AJAX endpoints used by the current UI. It is not an exhaustive listing of all 122 registered routes.

### Dashboards
| Route | Name |
|-------|------|
| `GET /dashboard` | `dashboard` |
| `GET /psych-dashboard` | `psychDashboard` |
| `GET /educ-dashboard` | `educDashboard` |
| `GET /accountancy-dashboard` | `accountancyDashboard` |
| `GET /teacher-dashboard` | `teacherDashboard` |
| `GET /admin-dashboard` | `adminDashboard` |

### Authentication
| Route | Name |
|-------|------|
| `GET /login` | `login` |
| `POST /login` | `login.post` |
| `POST /logout` | `logout` |
| `GET /pending-approval` | `login.pending` |
| `GET /account-rejected` | `login.rejected` |
| `POST /signup` | `signup.post` |
| `GET /email/verify/{token}` | `verification.verify` |
| `POST /email/resend` | `verification.resend` |
| `GET /forgot-password` | `password.request` |
| `POST /forgot-password` | `password.email` |
| `GET /reset-password/{token}` | `password.reset` |
| `POST /reset-password` | `password.update` |

### Student
| Route | Name |
|-------|------|
| `GET /my-classes` | `student.classes` |
| `GET /my-classes/{class}/modules` | `student.modules` |
| `GET /modules/{module}/view` | `module.view` |
| `POST /modules/{module}/progress` | `modules.progress.update` |
| `GET /modules/{module}/quiz/questions` | `quiz.get.questions` |
| `GET /assessment` | `assessment` |
| `GET /assessment/{module}` | `assessment.take` |
| `POST /modules/{module}/quiz/submit` | `quiz.submit` |
| `POST /quiz/{module}/answer` | `quiz.answer` |
| `GET /progress` | `progress` |

### Teacher
| Route | Name |
|-------|------|
| `GET /manageclass` | `manageclass` |
| `GET /profile` | `profile` |
| `POST /profile/password` | `profile.password.update` |
| `POST /profile/email` | `profile.email.update` |
| `POST /profile/email/verify` | `profile.email.verify` |
| `POST /profile/program` | `profile.program.update` |
| `POST /classes` | `classes.store` |
| `POST /classes/{class}/modules` | `classes.modules.store` |
| `GET /classes/{class}/students` | `classes.students.get` |
| `POST /classes/{class}/invite` | `classes.invite` |
| `GET /classes/{class}/modules/list` | `classes.modules.list` |
| `DELETE /modules/{module}` | `modules.delete` |
| `POST /classes/{class}/announcements` | `announcements.store` |
| `DELETE /announcements/{announcement}` | `announcements.destroy` |
| `GET /classes/{class}/announcements/feed` | `announcements.class-feed` |
| `GET /student-performance/{class}` | `student.performance` |
| `GET /student-performance/{class}/progress` | `student.progress.tracker` |
| `GET /student-performance/{class}/students/{student}` | `student.performance.student-item-analysis` |
| `POST /student-performance/{class}/refresh-ai` | `student.performance.refresh` |
| `GET /student-performance/{class}/assessment-analysis/{student}` | `student.assessment.analysis` |
| `POST /student-performance/{class}/assessment-analysis/{student}/generate-ai` | `student.assessment.analysis.generate-ai` |
| `POST /modules/{module}/quiz/generate` | `quiz.generate` |
| `POST /modules/{module}/quiz/store` | `quiz.store` |
| `POST /modules/{module}/quiz/insights` | `quiz.insights` |
| `GET /chat` | `chat.index` |
| `POST /chat/conversations/{chat}/messages` | `chat.send_message` |

### Admin / Superadmin
| Route | Name |
|-------|------|
| `GET /admin/approvals` | `admin.approvals` |
| `POST /admin/approvals/{user}/approve` | `admin.approvals.approve` |
| `POST /admin/approvals/{user}/reject` | `admin.approvals.reject` |
| `POST /admin/approvals/approve-many` | `admin.approvals.approve-many` |
| `POST /admin/approvals/approve-all` | `admin.approvals.approve-all` |
| `GET /admin/users` | `admin.users` |
| `POST /admin/users/{id}/toggle-status` | `admin.users.toggle-status` |
| `POST /admin/users/{id}/reset-password` | `admin.users.reset` |
| `GET /admin/users/export` | `admin.users.export` |
| `POST /admin/users/{user}/unlock-program` | `admin.unlock.program` |
| `DELETE /classes/{class}/students/{student}` | `classes.students.remove` |
| `GET /admin/ai-settings/classes` | `admin.class-ai-settings` |
| `GET /superadmin/ai-settings` | `superadmin.ai-settings` |
| `GET /superadmin/admins` | `superadmin.admins` |

---

## 10. AI Features

### Quiz Generation
- Teacher uploads a module PDF; system parses it and passes content to Cloudflare Workers AI to generate quiz questions.
- Route: `POST /modules/{module}/quiz/generate` → `ClassManagerController::generateQuizAi()`
- Gated by: `AiSettingsResolver::isFeatureEnabled('quiz_generation', $class)`

### Quiz Insights
- After a quiz is taken, teacher can request AI-generated insight summary on class performance for that module.
- Generated insight fields are cached in `quiz_attempts` and reused until the corresponding attempt is reset or replaced.
- Route: `POST /modules/{module}/quiz/insights` → `QuizController::generateInsights()`
- Gated by: `AiSettingsResolver::isFeatureEnabled('insights', $class)`

### Class Performance AI Summary
- Teacher can refresh an AI-generated narrative summary of overall class performance.
- Route: `POST /student-performance/{class}/refresh-ai` → `PerformanceController::refreshAiSummary()`

### Assessment Analysis AI
- Teacher can request per-student AI analysis of assessment results.
- Route: `POST /student-performance/{class}/assessment-analysis/{student}/generate-ai`
- Model/token settings resolved via `AiSettingsResolver` (global and per-class overrides supported).

### AI Settings Hierarchy
- Superadmin sets global defaults (model, max tokens, feature toggles, prompt templates).
- Admin can override settings per class.
- `AiSettingsResolver` merges global and class settings at runtime.

---

## 11. Module Learning Flow

```
Student joins class
  → Views module list (locked/unlocked based on sequential progress)
  → Opens module → reads content / views PDF
  → Marks progress (scroll/completion tracking)
  → When previous module completed → next module unlocks
  → Takes assessment quiz (attempt is tracked with attempt_count)
  → Results stored in quiz_attempts + quiz_answers
  → If no attempt remains, teacher can reset attempt for a re-take (practice flow)
  → Teacher sees results in performance tracker
  → AI insights available per module quiz (generated once then served from cache)
```

Sequential lock enforcement:
- **Backend:** `ClassManagerController::viewModuleFile()` aborts with 403 if previous module progress is not `completed = true`
- **Frontend:** In the current student module experience, locked modules render with a lock badge, 45% opacity, and pointer-events disabled via the `$locked` map in `resources/views/pages/student/modules.blade.php`
- **Controller:** `studentModules()` computes `$locked` map passed to view

---

## 12. Tests

| File | Scope |
|------|-------|
| `AdminApprovalFlowTest` | Approve/reject/approve-all flows, status changes, email triggers |
| `AdminDashboardOverviewTest` | Admin dashboard access and data |
| `AdminUserResetPermissionTest` | Password reset authorization by role |
| `AdminUsersManagementTest` | User listing, search, export, toggle |
| `AdminUserStatusToggleTest` | Active/inactive toggle behavior |
| `AiSettingsAuthorizationTest` | AI settings access by role (superadmin/admin gates) |
| `AnnouncementsFeatureTest` | Announcement create/edit/delete/pin/mark-read |
| `ChatRouteViewResolutionTest` | Chat route access and view rendering |
| `ExampleTest` | Basic framework smoke test scaffold retained under Feature |
| `ModuleVisibilityTest` | Per-student module visibility filtering |
| `MyClassesViewResolutionTest` | Student class list rendering by role |
| `PerformanceStudentItemAnalysisTest` | Student performance and assessment analysis views |
| `StudentDashboardTest` | Dashboard access by student track |
| `TeacherAssessmentEditingTest` | Assessment creation/editing flows |
| `TeacherDashboardTest` | Teacher dashboard access |

**Result as of March 30, 2026: 68 tests, 173 assertions — all passing.**

---

## 13. Layouts

| Layout File | Used By |
|------------|---------|
| `appAdmin.blade.php` | All admin and superadmin pages |
| `appTeach.blade.php` | All teacher pages |
| `appEduc.blade.php` | Education track student pages |
| `appPsych.blade.php` | Psychology track student pages |
| `appAcc.blade.php` | Accountancy track student pages |
| `app.blade.php` | Generic authenticated pages (chat, profile, quiz create) |
| `domain.blade.php` | Full-screen immersive module reader and assessment-take pages (intentional — no sidebar) |
| `guest.blade.php` | Login, forgot password |
| `sidebar.blade.php` | Reusable sidebar partial |

Note: `appTeachh.blade.php` is retained as a legacy/alternate layout file tied to an older accountancy-oriented variant and should not be treated as a generic dead file without a deliberate cleanup decision.

---

## 14. Email Notifications

| Trigger | Service | Method |
|---------|---------|--------|
| Account approval | `GmailService` | `sendMail()` → `send()` |
| Account rejection | `GmailService` | `sendMail()` → `send()` |
| Password reset (temporary password) | `GmailService` | `send()` |
| Email verification at signup | `GmailSender` | `sendVerification()` |

All email is sent via the Gmail OAuth2 API using credentials stored in `storage/app/google/`. Token refresh is handled automatically on each instantiation.

---

## 15. FDD Compliance Summary

| # | Feature | Status |
|---|---------|--------|
| 1 | Signup with email verification | ✅ BUILT |
| 2 | Login blocked for pending/rejected with dedicated pages | ✅ BUILT |
| 3 | Password reset | ✅ BUILT |
| 4 | Student dashboard and class access | ✅ BUILT |
| 5 | Module visibility per student | ✅ BUILT |
| 6 | Sequential lecture lock / prerequisite gate | ✅ BUILT |
| 7 | Quiz taking and scoring pipeline | ✅ BUILT |
| 8 | Teacher class/module management | ✅ BUILT |
| 9 | Teacher assessment creation/editing | ✅ BUILT |
| 10 | Announcements lifecycle | ✅ BUILT |
| 11 | Teacher performance analytics surfaces | ✅ BUILT |
| 12 | AI settings management | ✅ BUILT |
| 13 | AI runtime generation (quiz/insights/summaries) | ✅ BUILT |
| 14 | Admin approvals queue and actions | ✅ BUILT |
| 15 | User management | ✅ BUILT |
| 16 | Email notifications | ✅ BUILT |
| 17 | Layout consistency by role | ✅ BUILT |
| 18 | Migration contract fidelity | ✅ BUILT |
| 19 | Model API naming fidelity | ✅ BUILT |

**19 / 19 features — BUILT. 0 PARTIALLY BUILT. 0 NOT BUILT.**

Item 11 covers the currently confirmed teacher analytics surfaces: the student performance page, per-student item analysis endpoint, class progress tracker page, and student assessment analysis page.

---

## 16. Known Cleanup Items (Non-Blocking)

| Item | Impact | Action |
|------|--------|--------|
| `GET /teacher-dashboard-teachh` | Appears to be a legacy/test route | Review and remove if unused |
| `GET /test-ai-laravel` | Dev debug route (`TestAiController`) | Remove or gate behind local-only middleware before production |
| `resources/views/layouts/quiz.blade.php` | Layout file exists but no confirmed active usage was found in the current primary quiz flows | Keep labeled as legacy/unused or retire deliberately |
| `Lecture` model, `LectureController`, and lecture routes | Legacy code from the old lecture-based flow can confuse active-system documentation | Keep clearly labeled as legacy or retire deliberately |
| `GET /tables`, `GET /register`, `GET /assessmentexams` | Stub/placeholder routes | Review intent and remove or implement |
| Route contract: confirm no external QA scripts reference specific route paths | No code change needed | Verify with QA team |

---

## 17. Live Data Snapshot (March 30, 2026)

| Entity | Count |
|--------|-------|
| Users | 9 |
| Classes | 2 |
| Modules | 17 |
| Migrations run | 41 |
| Named routes | 122 |
| Test assertions | 173 |
