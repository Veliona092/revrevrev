# Introducing Reviso

> A written-from-the-code introduction to the Reviso platform.
> Everything below was verified against the current `main` branch of this repository, not against older design docs.

---

## 1. What Reviso Is

**Reviso is a Laravel-based learning and assessment platform built for schools that prepare students for licensure board exams.**

It is not a generic LMS clone. The whole system is organized around one central idea: a **mock board** — a simulated licensure examination that a teacher runs against a cohort of students in two phases (a *pre-test* and a *pre-board*), so the school can measure how much the review program actually moved the needle.

Around that core, Reviso provides the supporting machinery a review program needs:

- classes and enrollment,
- lecture/module delivery with per-student visibility,
- a quiz engine with a question bank, timers, attempt limits and passing grades,
- board-exam topic libraries organized by topic → subtopic → file,
- statistical analytics (ANOVA, paired t-test, item analysis) over cohort results,
- AI-generated insights on student and class performance,
- announcements and class chat,
- an admin approval workflow for new accounts.

### Who it is for

| Role | What they do in Reviso |
|---|---|
| **Student** | Sees a program-specific dashboard, works through lecture modules and board-exam topics, takes quizzes and mock boards, tracks their own progress and results. |
| **Teacher** | Creates classes, uploads lectures, builds quizzes and mock boards, controls who sees what, reads analytics for their cohort, posts announcements, chats with students. |
| **Admin** | Approves or rejects registrations, manages and resets users, toggles account status, approves mock boards, views system-wide dashboards. |
| **Superadmin** | Everything an admin can do, plus global AI configuration. |

Roles are a plain `role` column on `users` (`student`, `teacher`, `admin`, `superadmin`) and are checked directly in controllers and routes.

### The three programs

Reviso is multi-program by design. Every user, class and mock board carries a `program` value — currently **Psychology (`psych`)**, **Education (`educ`)** and **Accountancy (`accountancy`)**. The program drives:

- which dashboard and layout a student gets (`appPsych`, `appEduc`, `appAcc`, alongside `appTeach` and `appAdmin`),
- how batch analytics are sliced,
- what a teacher's newly created mock boards default to.

A user's program can be **locked** (`program_locked`) so students cannot reassign themselves out of their cohort; only an admin can unlock it.

---

## 2. The Core Concept: Mock Boards

This is the part of Reviso worth understanding first, because most other features exist to feed it.

### Structure

```
MockBoard  (title, program, class, teacher, review period, passing %, visibility, status)
  └── MockBoardPhase   phase_type = "pre_test"  → Module → QuizQuestions
  └── MockBoardPhase   phase_type = "pre_board" → Module → QuizQuestions
        └── MockBoardAttempt / QuizAttempt (per student, per phase)
              └── QuizAnswer (per question)
                    └── MockBoardStatistic (computed cohort results)
```

A **mock board** is a container with a review period (`review_period_start` / `review_period_end`), a passing percentage, and visibility rules. Its `visible_to` field is a JSON array, so a board can be shown to a whole class, to a hand-picked list of students, or to everyone *except* a list.

Each **phase** wraps a real `Module` (the quiz) and can either reuse the exact same questions as the other phase (`is_same_questions`) or pin its own subset via `question_ids`. Reusing the same items is what makes the pre-test → pre-board comparison statistically meaningful.

### Approval lifecycle

Mock boards are not live the moment a teacher saves them. They carry a `status` and move through:

```
pending ──approve──> approved   (markApproved: records approver + timestamp)
   │
   └────reject────> rejected    (markRejected: records reason)
                        │
                        └──resetToPending──> pending
```

`MockBoardApprovalController` drives this; `MockBoard::isActive()` gates student access on both approval and the review period window.

### The analytics payoff

`app/Services/MockBoardStatisticsService.php` is where Reviso earns its name. It computes, per mock board and per program cohort:

- `computeClassStatistics()` — cohort-level score distribution and passing rates
- `computePairedTTest()` — did the same students improve from pre-test to pre-board?
- `computeOneWayANOVA()` / `calculateBatchANOVA()` — are differences between groups significant?
- `getItemAnalysis()` — per-question difficulty and discrimination, optionally filtered by phase
- `computeHierarchicalBatchStats()` / `getHierarchicalStats()` — roll-ups across a whole program batch
- `getDetailedStudentResults()` — the per-student drill-down behind the charts

Results are persisted to `MockBoardStatistic` so dashboards do not recompute on every page load.

---

## 3. How the Pieces Fit Together

### Content and assessment

- **`Module`** is the universal content/assessment unit. Flags on it decide what it *is*: `is_quiz`, `is_formal_assessment`, `is_mock_board`. It carries `time_limit`, `passing_grade`, `max_attempts`, `due_date`, and per-user visibility via the `module_user_visibility` pivot.
- **`QuizQuestion`** holds the item text, JSON `options`, correct answer, points, and both a general `explanation` and a `domain_explanation` (used for board-exam domain feedback).
- **`QuizAttempt`** records a student's run at a module — score, pass/fail, timing, attempt count, status tracking, and cached `ai_insights`. **`QuizAnswer`** stores the individual responses.
- **`AssessmentAttemptGrant`** lets a teacher hand an individual student an extra attempt without changing the module's global limit.
- **`Lecture`** and the **board-exam topic tree** (`board_exam_topics` → `board_exam_subtopics` → `board_exam_subtopic_files`, each with its own progress table) provide the review material students work through between phases.
- **`ModuleProgress`** tracks reading progress, including scroll position, so students resume where they left off.

### Communication

`Announcement` (with pinning, per-user read receipts via `AnnouncementRead`, and `AnnouncementComment`) plus `Chat` / `ChatMessage` for class messaging. A `UpdateLastSeen` middleware stamps `users.last_seen_at`, and `User::isOnline()` / `lastSeenLabel()` turn that into the presence indicator in the UI.

### AI features

Reviso talks to **Cloudflare Workers AI** through `app/Services/CloudflareAI.php`, optionally routed via an AI Gateway. Three Llama models are used depending on the task:

- `@cf/meta/llama-3.2-3b-instruct`
- `@cf/meta/llama-3.1-8b-instruct`
- `@cf/meta/llama-3.3-70b-instruct-fp8-fast`

`AiSettingsResolver` resolves the effective configuration by layering **per-class settings** (columns on `classes`) over **global settings** (`ai_settings` table) — superadmins set the global defaults, teachers can override for their own class. AI output shows up as quiz insights on attempts (`quiz_attempts.ai_insights`), class assessment summaries (`classes.assessment_ai_summary`), and per-student analysis on the enrollment pivot (`class_user.assessment_ai_analysis`).

### Email

Two custom services, `GmailService` and `GmailSender`, send transactional mail (verification, approval decisions, password resets) through the Gmail API rather than SMTP, using `google/apiclient`.

---

## 4. Technology Stack

Taken from `composer.json`, `vite.config.js`, and the code itself:

| Layer | Choice |
|---|---|
| Framework | Laravel 12 |
| PHP | `^8.2` (`composer.json`); the Boost guidelines in `AGENTS.md` / `CLAUDE.md` state 8.5 |
| Structure | Laravel 10-style layout (`app/Http/Kernel.php`, `app/Exceptions/`, `app/Providers/`) — intentionally not migrated to the streamlined Laravel 11+ skeleton |
| Database | MySQL via Eloquent, 68 migrations |
| Views | Blade, with role- and program-specific layouts |
| Styling | Argon Dashboard (Bootstrap 4) SCSS **plus** Tailwind via `@tailwindcss/vite` |
| Bundler | Vite (`resources/sass/argon-dashboard.scss`, `resources/js/app.js`) |
| Reactive UI | `livewire/livewire` v4 is installed as a dependency |
| Authorization | Laravel Gates and policies; `spatie/laravel-permission` is installed and registered in `app/Http/kernel.php` |
| AI | `anthropic-ai/sdk` in Composer; the code path in use is Cloudflare Workers AI over HTTP |
| Other libs | `firebase/php-jwt`, `phpseclib`, `smalot/pdfparser` (PDF lecture parsing), `google/apiclient` |
| Testing | PHPUnit 11 — 20 test files, mostly feature tests |
| Formatting | Laravel Pint |
| Dev tooling | Laravel Sail, Pail, Boost, Collision, Faker |

---

## 5. Repository Map

```
revrevrev/
├── app/
│   ├── Auth/            custom auth pieces
│   ├── Http/
│   │   ├── Controllers/ 24 controllers, split by role
│   │   ├── Middleware/  incl. UpdateLastSeen
│   │   ├── Requests/    form-request validation
│   │   └── Kernel.php   Laravel 10-style middleware registration
│   ├── Mail/            mailables
│   ├── Models/          20 Eloquent models
│   ├── Providers/       AppServiceProvider, AuthServiceProvider
│   ├── Services/        CloudflareAI, AiSettingsResolver, GmailService,
│   │                    GmailSender, MockBoardStatisticsService
│   └── notifications/
├── database/
│   ├── migrations/      68 migrations
│   ├── seeders/         AdminSeeder, DemoUsersSeeder, SignupsTableSeeder, …
│   └── factories/
├── resources/views/
│   ├── layouts/         appTeach, appAdmin, appPsych, appEduc, appAcc
│   ├── pages/           teacher/ admin/ student/ psych/ educ/ accountancy/ …
│   ├── analytics/  assessments/  auth/  emails/  reusable/
├── routes/
│   ├── web.php          736 lines — the main route file
│   ├── api.php          near-empty; the app is server-rendered
│   └── console.php
├── docs/
│   ├── CODEBASE_CONTEXT_REPORT.md   detailed agent-facing context report
│   └── olddocs/                     ERDs, DFDs, plans, correction notes
└── tests/               20 PHPUnit test files
```

### Controllers at a glance

| Group | Controllers |
|---|---|
| Auth & profile | `RegisterController`, `ProfileController` |
| Teacher | `TeacherDashboardController`, `ClassManagerController`, `QuizController`, `LectureController`, `TeacherBoardExamModuleController`, `MockBoardController`, `AnnouncementController` |
| Student | `StudentDashboardController`, `StudentAssessmentController`, `StudentProgressController`, `StudentMockBoardController` (+ a `Student/` namespaced variant) |
| Admin | `AdminDashboardController`, `AdminUserController`, `Adminapprovalcontroller`, `MockBoardApprovalController` |
| Analytics | `PerformanceController`, `BatchAnalyticsController`, `MockBoardAnalyticsController` |
| Platform | `AiSettingsController`, `ChatController`, `TestAiController` |

`routes/web.php` is essentially one large `Route::middleware('auth')` group with a role-aware dashboard resolver, plus dedicated prefixes for `mock-boards/batch-analytics`, `student/mock-boards`, and `admin/mock-boards`.

---

## 6. Running It Locally

```bash
composer install
npm install

cp .env.example .env      # see the note below — .env.example is currently missing
php artisan key:generate

# point .env at your MySQL database, then:
php artisan migrate
php artisan db:seed        # AdminSeeder / DemoUsersSeeder give you accounts to log in with

php artisan serve
npm run dev                # or `composer run dev` for server + queue + logs + vite
```

Environment values the app actually reads:

- `DB_*` — MySQL connection
- `CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN`, and optionally a gateway id — required for every AI feature; `CloudflareAI` throws a `RuntimeException` if they are absent
- Gmail API credentials for `GmailService`
- Standard `MAIL_*` / `APP_*` Laravel settings

Tests and formatting:

```bash
php artisan test --compact
vendor/bin/pint --format agent
```

---

## 7. Conventions Worth Knowing

These are enforced by `AGENTS.md` / `CLAUDE.md` and reflected throughout the code:

- **Laravel 10 structure stays.** Middleware in `app/Http/Kernel.php`, exceptions in `app/Exceptions/Handler.php`. Do not migrate to the Laravel 11+ skeleton.
- **Eloquent over raw SQL.** Prefer `Model::query()` and relationship methods with return type hints; avoid `DB::`; eager-load to dodge N+1.
- **Form Requests for validation**, not inline `$request->validate()`.
- **`config()`, never `env()`** outside of config files.
- **Explicit return types** on every method; PHP 8 constructor property promotion; PHPDoc over inline comments.
- **PHPUnit, not Pest.** Most tests are feature tests. Run Pint before finalizing any PHP change.
- **Documentation files only when asked** — which is why this file exists only because it was requested.

---

## 8. Current State — Honest Notes

Reading the repository as it stands, a few things are worth flagging to anyone getting oriented:

1. **`.env` is committed to this public repository.** `git ls-files` confirms it is tracked. Any credential it contains — database, Cloudflare API token, Gmail keys — should be treated as compromised, rotated, and the file removed from tracking (`git rm --cached .env`, add to `.gitignore`, and purge from history). There is also no `.env.example` to replace it, so onboarding has nothing to copy from.
2. **`vendor/` and `node_modules/` are committed** (47,738 tracked files). This is why the clone is ~450 MB. Both should be gitignored and restored via `composer install` / `npm install`.
3. **`README.md` is still the stock Argon Dashboard readme** from Creative Tim — it describes a Bootstrap theme, not Reviso. `package.json` is likewise still Argon's, listing gulp devDependencies while the project actually builds with Vite and Tailwind.
4. **Documentation has drifted.** `Reviso_Project_Description.md` and `docs/CODEBASE_CONTEXT_REPORT.md` are useful but predate current code: they list fewer controllers and models than exist, omit the board-exam topic tree, the mock board approval workflow, and `AssessmentAttemptGrant`. Treat the code as the source of truth.
5. **Version statements disagree.** `composer.json` requires PHP `^8.2`; the Boost guidelines say 8.5.
6. **Loose files at the repo root** — `check.php`, `check_attempts.php`, `dump_schema.php`, `backupclassmanage/`, `quizcreatebladebackup`, `app/Http/Controllers/backupcopilot`, `backupgrok`, and several scratch notes (`notes.md`, `notes2.md`, `old.md`, `systemnotes.md`) — look like development leftovers rather than shipped code.
7. **Test coverage is thin relative to surface area**: 20 test files against 24 controllers, 20 models and a heavy statistics service. The statistical functions in `MockBoardStatisticsService` in particular are the highest-value candidates for unit tests, since a silent error there produces plausible-looking but wrong analytics.
8. **Livewire v4 and `spatie/laravel-permission` are installed** but the application is predominantly classic Blade with `role`-column checks; there is no `app/Livewire` directory.

None of these block the product working — they are the cleanup list a new contributor should know about before touching anything.

---

## 9. Where to Start Reading

If you are new to this codebase, in order:

1. `routes/web.php` — the whole application surface in one file.
2. `app/Models/MockBoard.php`, `MockBoardPhase.php`, `Module.php` — the domain core.
3. `app/Services/MockBoardStatisticsService.php` — the analytics that justify the product.
4. `app/Http/Controllers/ClassManagerController.php` — the largest controller, and where most teacher workflows live.
5. `docs/CODEBASE_CONTEXT_REPORT.md` — deeper context, with the caveat that it lags the code.
