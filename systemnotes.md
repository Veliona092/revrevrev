# System Notes — Reviso

Quick reference for working on this codebase. Last scanned: 2026-03-30.

---

## App Overview

**Reviso** is a Laravel 12 LMS (Learning Management System). Teachers create classes, upload lecture modules with quizzes, and track student performance. Students enroll, read modules, and take quizzes. Admins approve signups and manage users.

---

## Roles & Auth

| Role | Dashboard | Notes |
|---|---|---|
| `student` / `psych` / `educ` / `accountancy` | program-specific dashboard | Legacy roles normalized to `student` + `program` |
| `teacher` | `/teacher-dashboard` | `program = 'teacher'` |
| `admin` | `/admin-dashboard` | 3 predefined accounts |
| `superadmin` | `/admin-dashboard` | Can manage admins, override approvals |

- Login uses `idnumber` (not email) as the auth identifier — `username()` returns `'idnumber'`
- Signup flow: student submits → email verified → **admin must approve** before login works
- User `status`: `pending`, `active`, `rejected`
- `program` field stores student track (`psych`, `educ`, `accountancy`). Teachers have `program = 'teacher'`
- `program_locked` boolean prevents students from changing program once set

---

## Database — Key Tables

| Table | Model | Notes |
|---|---|---|
| `users` | `User` | `idnumber`, `role`, `program`, `program_locked`, `status`, `last_seen_at` |
| `signups` | `Signup` | Pre-registration staging; moved to `users` after email verify + admin approval |
| `classes` | `ClassModel` | `created_by` (teacher id), `ai_summary`, `ai_settings` (JSON) |
| `class_user` | pivot | `user_id`, `class_id`, `joined_at` |
| `modules` | `Module` | `class_id`, `is_quiz`, `is_formal_assessment`, `is_assignment`, `time_limit`, `visibility` |
| `module_progress` | `ModuleProgress` | `user_id`, `module_id`, `progress`, `completed`, `completed_at`, `scroll_position` |
| `module_user_visibility` | pivot | Per-student module visibility overrides |
| `quiz_questions` | `QuizQuestion` | `module_id`, `options` (JSON array A/B/C/D), `correct_option`, `difficulty`, `order` |
| `quiz_attempts` | `QuizAttempt` | `user_id`, `module_id`, `score`, `total`, `percentage`, `passed`, `time_taken` |
| `quiz_answers` | `QuizAnswer` | `attempt_id`, `question_id`, `selected_option`, `is_correct` |
| `announcements` | `Announcement` | `class_id`, `user_id`, `message`, `is_pinned` |
| `announcement_reads` | `AnnouncementRead` | Tracks which users read which announcements |
| `chats` | `Chat` | `kind`, `created_by`, `class_id`, `title` |
| `chat_user` | pivot | Chat participants |
| `chat_messages` | `ChatMessage` | |
| `ai_settings` | `AiSetting` | Global key/value AI config (key, value columns) |
| `lectures` | `Lecture` | Older lecture content system, separate from modules |

### Important column notes
- `modules.is_formal_assessment = true` → Assessment tab quiz; `false` → Pre-Assessment / practice quiz
- `modules.visibility` → controls student visibility; `module_user_visibility` overrides per student
- `quiz_questions.options` → cast to `array`, keys are strings A/B/C/D
- `quiz_attempts.passed` → boolean cast
- `ClassModel` uses table `classes` — named ClassModel because `Class` is a reserved PHP keyword

---

## Model Relationships

```
User
  └── belongsToMany ClassModel  (via class_user)
  └── hasMany ModuleProgress
  └── hasMany QuizAttempt
  └── belongsToMany Chat        (via chat_user)

ClassModel  (table: classes)
  └── belongsTo User            (creator, via created_by)
  └── belongsToMany User        (students, via class_user)  [alias: students()]
  └── hasMany Module

Module
  └── belongsTo ClassModel
  └── hasMany ModuleProgress
  └── hasMany QuizQuestion      (ordered by `order`)
  └── belongsToMany User        (visibleTo, via module_user_visibility)

QuizQuestion
  └── belongsTo Module

QuizAttempt
  └── belongsTo User
  └── belongsTo Module

QuizAnswer
  └── belongsTo QuizAttempt
  └── belongsTo QuizQuestion

Announcement
  └── belongsTo ClassModel
  └── belongsTo User
  └── hasMany AnnouncementRead

Chat
  └── belongsTo User            (creator)
  └── belongsToMany User        (participants)
  └── hasMany ChatMessage
  └── hasOne ChatMessage        (lastMessage — latest)
```

---

## Controllers & What They Do

| Controller | Responsibility |
|---|---|
| `ClassManagerController` | CRUD classes, add/remove students, module upload, quiz create/generate/store, invite links |
| `PerformanceController` | Teacher performance page, item analysis JSON, AI summary refresh, per-student assessment analysis |
| `QuizController` | Get quiz questions (JSON), submit quiz, generate AI insights |
| `StudentAssessmentController` | Student-facing assessment list + take a specific assessment |
| `StudentProgressController` | Student-facing `/progress` page (student sees own progress only) |
| `LectureController` | CRUD lectures (older content system) |
| `AnnouncementController` | Create/delete announcements, mark-read, per-class feed |
| `ChatController` | Conversations list, messages, start/send/remove |
| `ProfileController` | Show/update password, email, program; admin unlock program |
| `AdminUserController` | List users, export CSV, reset password, toggle status |
| `AdminApprovalController` | Approve/reject pending signups |
| `AiSettingsController` | Global AI settings (superadmin) + per-class AI settings (admin) |
| `AdminDashboardController` | Admin dashboard view |
| `TeacherDashboardController` | Teacher dashboard view |
| `StudentDashboardController` | Student dashboard view (shared: psych/educ/accountancy) |

---

## Routes (Key Ones)

All routes live in a single file: `routes/web.php`.

### Teacher / Performance
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/student-performance/{class}` | `student.performance` | 2-tab performance page (Pre-Assessment + Assessments) |
| GET | `/student-performance/{class}/students/{student}` | `student.performance.student-item-analysis` | JSON item analysis (latest attempt) |
| POST | `/student-performance/{class}/refresh-ai` | `student.performance.refresh` | Refresh AI class summary |
| GET | `/student-performance/{class}/assessment-analysis/{student}` | `student.assessment.analysis` | **Assessment Data Analysis** page |
| POST | `/student-performance/{class}/assessment-analysis/{student}/generate-ai` | `student.assessment.analysis.generate-ai` | Generate AI for assessment analysis |

### Student
| Method | URI | Name |
|---|---|---|
| GET | `/assessment` | `assessment` |
| GET | `/assessment/{module}` | `assessment.take` |
| GET | `/progress` | `progress` |
| GET | `/my-classes` | `student.classes` |
| GET | `/my-classes/{class}/modules` | `student.modules` |

### Class / Module / Quiz
- `POST /classes` → `classes.store`
- `POST /classes/{class}/students` → `classes.students.add`
- `DELETE /classes/{class}/students/{student}` → `classes.students.remove`
- `GET /classes/{class}/students` → `classes.students.get`
- `POST /classes/{class}/invite` → `classes.invite`
- `POST /classes/{class}/modules` → `classes.modules.store`
- `DELETE /modules/{module}` → `modules.delete`
- `POST /modules/{module}/progress` → `modules.progress.update`
- `GET /modules/{module}/quiz/create` → `quiz.create`
- `POST /modules/{module}/quiz/generate` → `quiz.generate` (AI)
- `POST /modules/{module}/quiz/store` → `quiz.store` (manual)
- `GET /modules/{module}/quiz/questions` → `quiz.get.questions` (JSON)
- `POST /modules/{module}/quiz/submit` → `quiz.submit`
- `POST /modules/{module}/quiz/insights` → `quiz.insights` (AI)

### Admin
- `GET /admin/users` → `admin.users`
- `GET /admin/approvals` → `admin.approvals`
- `GET /admin/ai-settings/classes` → `admin.class-ai-settings`
- `GET /superadmin/admins` → `superadmin.admins`
- `GET /superadmin/ai-settings` → `superadmin.ai-settings`
- `POST /superadmin/approvals/{user}/override` → `superadmin.approvals.override`

---

## Views Structure

```
resources/views/
  layouts/
    appTeach.blade.php          — teacher layout
    appAcc.blade.php            — accountancy student layout
    appPsych.blade.php          — psych student layout
    appEduc.blade.php           — educ student layout
    appAdmin.blade.php          — admin layout
    quiz.blade.php              — standalone quiz layout
    sidebar.blade.php           — shared sidebar component
    guest.blade.php             — unauthenticated layout
  pages/
    teacher/
      student-performance.blade.php          — main perf page (2 tabs)
      student-assessment-analysis.blade.php  — Assessment Data Analysis (per-student)
      manageclass.blade.php
      modules-list.blade.php
      quiz-create.blade.php
      lectures.blade.php / lectures-edit.blade.php
      profile.blade.php
      assessmentexams.blade.php
    student/
      assessment.blade.php / assessment-take.blade.php
      modules.blade.php
      progress.blade.php
      pdfjs-viewer.blade.php
      quiz-create.blade.php
    admin/
      users.blade.php, approvals.blade.php
      ai-settings-classes.blade.php, ai-settings-global.blade.php
      manage-admins.blade.php
      lectures.blade.php, tables.blade.php, etc.
```

---

## Services

### `CloudflareAI`
- Calls Cloudflare Workers AI via HTTP
- Needs in `.env`: `CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_TOKEN`
- Optional: `CLOUDFLARE_GATEWAY` (AI Gateway slug)
- `run(string $model, array $payload): array` — returns the `result` portion

### `AiSettingsResolver`
- Merges global `ai_settings` table + per-class `classes.ai_settings` JSON
- Feature flags: `quiz_generation_enabled`, `quiz_insights_enabled`, `class_summary_enabled`
- Available models: `@cf/meta/llama-3.2-3b-instruct` (fast), `@cf/meta/llama-3.1-8b-instruct` (balanced), `@cf/meta/llama-3.3-70b-instruct-fp8-fast` (powerful)
- Default model: Llama 3.2 3B, max tokens: 400

### `GmailService`
- Sends email via Gmail API OAuth2
- Credentials: `storage/app/google/credentials.json`
- Tokens: `storage/app/google/tokens.json`
- OAuth flow: `GET /authorize-gmail` → `GET /oauth2callback`

---

## AI Features

| Feature | Trigger | Output |
|---|---|---|
| Quiz generation | Teacher uploads PDF, clicks generate | Strict JSON MCQ array |
| Quiz insights | After student submits quiz | Strong Areas / Weak Areas / Recommendation |
| Class AI summary | Teacher clicks refresh on performance page | Narrative stored in `classes.ai_summary` |
| Assessment Data Analysis | Teacher clicks "Generate Analysis" on per-student page | Strong Areas / Weak Areas / Recommendation via session flash |

---

## Key Patterns & Conventions

- **Teacher authorization**: `PerformanceController::authorizeClassAccess()` — checks `class->created_by === auth()->id()`; admin/superadmin bypass
- **Pass threshold**: 50% for both pre-assessment and formal assessments
- **Performance page JSON mode**: `studentPerformance()` checks `expectsJson()` for AJAX class-switcher
- **Layout resolution**: Controllers resolve layout dynamically based on `role`/`program`
- **`UserFactory` defaults**: only `name`, `email`, `email_verified_at`, `password`, `remember_token` — add `role`, `program`, `status`, `idnumber` manually in tests
- **Pint**: always run `vendor/bin/pint --dirty --format agent` after editing PHP files
- **Module distinction**: `is_formal_assessment` is the single flag that separates quiz types

---

## Tests (Feature)

All PHPUnit classes in `tests/Feature/`:

| File | Covers |
|---|---|
| `AdminApprovalFlowTest` | Approve/reject pending signups |
| `AdminDashboardOverviewTest` | Admin dashboard access |
| `AdminUserResetPermissionTest` | Only admin can reset passwords |
| `AdminUsersManagementTest` | User list, CSV export |
| `AdminUserStatusToggleTest` | Toggle user active/inactive |
| `AiSettingsAuthorizationTest` | Only superadmin/admin can edit AI settings |
| `AnnouncementsFeatureTest` | Create, pin, delete, mark-read |
| `ChatRouteViewResolutionTest` | Chat routes load correct views |
| `ModuleVisibilityTest` | Per-student module visibility |
| `MyClassesViewResolutionTest` | Student class list |
| `PerformanceStudentItemAnalysisTest` | Teacher item analysis + auth check |
| `StudentDashboardTest` | Dashboard redirect by program |
| `TeacherAssessmentEditingTest` | Teacher can edit formal assessments |
| `TeacherDashboardTest` | Teacher dashboard loads |

Run a single test: `php artisan test --compact --filter=TestName`  
Run all: `php artisan test --compact`

---

## What Is NOT Yet Built

- **Teacher-side class progress tracker** — `ModuleProgress` data exists but no teacher UI/route showing who completed which modules
- Full multi-class switching on student performance (noted as PARTIALLY DONE in `notes.md`)

---

## Environment Variables Needed

```env
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_TOKEN=
CLOUDFLARE_GATEWAY=   # optional
```

Gmail OAuth stored as JSON files in `storage/app/google/`.
