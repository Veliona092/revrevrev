# Reviso LMS — Mock Defense Technical Reviewer

> **Scope:** Technical questions only (architecture, database, security, AI, features).
> Your friend covers document/methodology questions.
> Each Q has a suggested answer angle — expand in your own words.

---

## Table of Contents
1. [System Overview & Architecture](#1-system-overview--architecture)
2. [Technology Stack Justification](#2-technology-stack-justification)
3. [Database & Data Model](#3-database--data-model)
4. [Authentication & Authorization](#4-authentication--authorization)
5. [Core Features — How They Work](#5-core-features--how-they-work)
6. [AI Integration](#6-ai-integration)
7. [Email System](#7-email-system)
8. [Security](#8-security)
9. [Testing](#9-testing)
10. [Scalability & Limitations](#10-scalability--limitations)
11. [Tough Follow-Up Questions](#11-tough-follow-up-questions)

---

## 1. System Overview & Architecture

---

**Q: What is Reviso and what problem does it solve?**

> Reviso is a web-based Learning Management System (LMS) built for academic institutions with multiple student tracks (Accountancy, Education, Psychology). It solves the problem of scattered course delivery — centralizing lecture modules, quizzes, announcements, teacher analytics, and AI-generated insights in one role-aware system. The core pain point it addresses is that existing generic LMS platforms do not reflect the specific academic structure of program-track-based institutions.

---

**Q: Describe the overall architecture of Reviso.**

> Reviso follows an MVC (Model-View-Controller) architecture via Laravel. The frontend is server-side rendered with Blade templates, enhanced with AJAX for dynamic interactions (quiz submission, chat, announcements). There is no SPA — all routing is server-side. The backend is organized into Controllers (HTTP request handling), Models (Eloquent ORM for database access), and Services (business logic like email and AI). A role-based middleware layer guards every protected route.

---

**Q: What is the role hierarchy and why is it designed that way?**

> `superadmin → admin → teacher → student`. The hierarchy mirrors how academic institutions actually operate. Superadmins govern the platform (manage admins, global AI settings). Admins govern users (approve signups, manage teacher/student accounts). Teachers govern learning content (create classes, modules, quizzes). Students consume content. Each role has its own dashboard, layout, and navigation — they never share UI surfaces.

---

**Q: Why did you use a monolithic application rather than a microservices approach?**

> For a capstone-scale application with a small team, a monolith is the appropriate choice. It reduces deployment complexity, keeps the codebase in one repository, and makes debugging straightforward. Microservices introduce network latency, distributed transaction complexity, and operational overhead that is not justified for this scale. If Reviso were to scale to thousands of institutions, service decomposition (e.g., separating the AI service, email service, and quiz engine) would be a natural evolution.

---

**Q: What are the layouts in your system and why separate them by role?**

> There are role-specific layouts: `appAdmin`, `appTeach`, `appEduc`, `appPsych`, `appAcc`, and `app`. They are separated because each role has fundamentally different navigation, sidebar items, branding emphasis, and available actions. Mixing them into one layout with conditional rendering would have produced brittle, complex Blade logic. Separate layouts are clean and enforce the principle that roles operate in separate UX contexts.

---

## 2. Technology Stack Justification

---

**Q: Why Laravel specifically?**

> Laravel provides a structured MVC framework with built-in features that directly address LMS requirements: Eloquent ORM for clean relationships (classes → modules → quizzes → attempts), Blade templating for server-side rendering, built-in authentication scaffolding, form request validation, queued jobs, and artisan CLI for rapid development. Laravel 12 also provides first-class Vite integration for asset bundling.

Laravel is widely used for building Learning Management Systems (LMS) because it provides a robust PHP framework with modularity, scalability, and built-in tools that make developing eLearning platforms faster and more maintainable.

---

**Q: Why PHP over Node.js or Python for the backend?**

> PHP remains the dominant server-side language for web applications in traditional academic/enterprise environments with known hosting stacks. Laravel specifically provides a mature ecosystem for exactly this type of CRUD-heavy, role-gated application. Python/Django or Node.js/Express would have required building similar patterns from scratch or relying on less mature ecosystems for this use case.

---

**Q: Why MySQL/MariaDB and not PostgreSQL or NoSQL?**

> The Reviso data model is highly relational — classes have modules, modules have questions, questions have answers, users have attempts, attempts have answers. This deeply nested relational structure fits a relational database naturally. MySQL/MariaDB is the most common production database in the Philippines' shared hosting environment where academic institutions typically deploy. NoSQL (like MongoDB) would actually be a poor fit here because we need transactional integrity across related quiz submissions.

---

**Q: What is Livewire and did you use it?**

> Livewire is a full-stack component library for Laravel that allows building reactive interfaces in PHP without JavaScript. It is installed in the project (Livewire v4) but the current Reviso implementation primarily uses AJAX + Blade rather than Livewire components. The AJAX approach was chosen because the dynamic interactions (quiz submission, chat messages, announcement reads) are straightforward request-response patterns that do not require two-way reactive state management.

---

**Q: Why Cloudflare Workers AI over OpenAI?**

> Cloudflare Workers AI was chosen because it runs on Cloudflare's global edge network — meaning low-latency inference without provisioning a dedicated GPU instance. It is also cost-effective at scale compared to OpenAI's token-based pricing, and does not require sending student data to a third-party US-based AI provider, which is a data governance consideration for academic institutions. The API surface is also simple — a single `run(model, payload)` call.

---

**Q: What is Vite and why use it over Webpack?**

> Vite is a modern frontend build tool that uses native ES modules in development for near-instant hot module replacement, and Rollup under the hood for production builds. It replaced Webpack in the Laravel ecosystem (via `laravel/vite-plugin`) because it is significantly faster in development — cold starts are under 300ms versus several seconds with Webpack. For a Blade + Bootstrap app, Vite handles CSS and JS bundling cleanly.

---

## 3. Database & Data Model

---

**Q: Walk me through what happens in the database when a student submits a quiz.**

> 1. A `QuizAttempt` record is created: `user_id`, `module_id`, `score`, `total`, `percentage`, `passed`.
> 2. For each answer the student selected, a `QuizAnswer` record is created: `attempt_id`, `question_id`, `selected_option`, `is_correct`.
> 3. The controller computes the score by comparing `selected_option` to `QuizQuestion.correct_option`.
> 4. `ModuleProgress` is updated to reflect completion if the attempt passes.
> 5. If AI insights are enabled and the module has no cached insight, a request is dispatched to `CloudflareAI` and the result is stored back in `quiz_attempts` (`ai_strong`, `ai_weak`, `ai_recommendation`).

---

**Q: What is the `module_user_visibility` pivot table for? Why not just use the module's own `visibility` column?**

> The module `visibility` column on the `modules` table is a class-wide default — it controls whether a module is visible to all students in the class. The `module_user_visibility` pivot table is a per-student override — a teacher can hide a specific module for a specific student without affecting visibility for others. This two-tier visibility system allows fine-grained access control that mirrors real classroom scenarios (e.g., a student on a different learning pace).

---

**Q: Why is there a `signups` table separate from the `users` table?**

> The `signups` table acts as a staging area for pre-registered accounts. When a student signs up, their record goes into `signups` first, not into `users`. They must: (1) verify their email via a token, and (2) be approved by an admin. Only after both conditions are met does a `User` record get created. This prevents unverified or unapproved records from polluting the `users` table and keeps the auth system clean — `users` only contains accounts that are legitimately active.

---

**Q: Why is the model named `ClassModel` instead of `Class`?**

> `Class` is a reserved keyword in PHP — you cannot name a class `Class`. The Eloquent model is named `ClassModel` but its `$table` property is set to `'classes'` so the database table name is still semantically correct. This is a standard PHP workaround.

---

**Q: What are the `ai_strong`, `ai_weak`, `ai_recommendation` columns on `quiz_attempts`?**

> These are cached AI-generated insights. After a student completes a quiz, the system can analyze their answer patterns and call Cloudflare Workers AI to generate: a strength summary, a weak area summary, and a study recommendation. The results are stored directly on the `quiz_attempts` row as text columns to avoid calling the AI API on every view — it is a read-heavy cache strategy. If the columns are null, the insight has not been generated yet.

---

**Q: How does the sequential module lock work at the database level?**

> The lock is implemented via `ModuleProgress`. Each module has an `order` integer. When a student tries to access module N, the controller queries `ModuleProgress` to check if the module with `order = N-1` has `completed = true` for that student. If not, the controller returns a 403. The UI also checks this and visually locks the module (greyed out, pointer-events disabled, lock icon) — but the server-side 403 is the real enforcement because a determined student could inspect-element around the UI lock.

---

**Q: You have 41 migrations. Why so many?**

> Migrations in Laravel are append-only — each change to the schema is a new migration file. 41 migrations reflect the iterative development of the system: initial table creation, then columns added as features were built (e.g., adding `is_pinned` to announcements, adding `ai_strong/ai_weak` to quiz_attempts, adding `program_locked` to users). This is correct practice — you never edit a migration that has already been run in a shared environment because other developers or the production database would be out of sync.

---

## 4. Authentication & Authorization

---

**Q: What is the signup-to-login flow in Reviso?**

> 1. Student fills out the signup form → a `Signup` record is created with `status = pending`.
> 2. A verification email with a unique token is sent via `GmailSender`.
> 3. Student clicks the link → `verified_at` is set on the `Signup` record.
> 4. An admin sees the new pending signup in the approvals queue.
> 5. Admin approves → a `User` record is created from the `Signup` data with `status = active`.
> 6. An approval email is sent to the student.
> 7. Student can now log in using their ID number and password.
> If an admin rejects at step 5, the student sees `/account-rejected` on the next login attempt.

---

**Q: Why does Reviso use ID number as the login identifier instead of email?**

> Academic institutions issue unique ID numbers to every enrolled student. Using ID number as the username mirrors how students already identify themselves in their institution (e.g., for library cards, registrar records). It is also more memorable for students than an email they may change. The `User` model overrides Laravel's default `username()` method to return `'idnumber'` instead of `'email'`.

---

**Q: What prevents an admin from accessing the chat/messaging system?**

> Two layers of enforcement: (1) The Messages nav item was removed from the admin navigation (`navbarAdmin.blade.php`) so admins never see the link. (2) `ChatController` has a private guard method `ensureAdminMessagingDisabled()` that is called at the top of every public method — it checks `Auth::user()->role` and aborts with 403 if the role is `admin` or `superadmin`. This defense-in-depth means even a direct URL attack is blocked at the controller level.

---

**Q: How does Laravel's CSRF protection work in Reviso?**

> Every HTML form in Reviso includes a `@csrf` Blade directive that renders a hidden `_token` input. On form submission, Laravel's `VerifyCsrfToken` middleware compares the submitted token against a session-stored token. If they do not match, the request is rejected with a 419 error. AJAX requests use a meta-tag CSRF token in the request header. This prevents cross-site request forgery attacks where a malicious site tricks an authenticated user into submitting a forged request.

---

**Q: How is the login throttled?**

> The login POST route has `throttle:10,1` middleware, which allows **10 attempts per minute per IP address** (not per username/account). Importantly, this is **per-IP throttling, not per-account**. If 100 students try to log in from the same classroom/IP at the same time, they share the same 10-attempt budget. When the limit is exceeded, Laravel returns a 429 "Too Many Requests" HTTP response. The user must wait 1 minute before the counter resets — there is no permanent 30-minute account lockout. This is a known limitation: a more robust system would implement username-based lockouts after N failed attempts (to prevent brute force on a single account) rather than IP-based throttling alone.

---

**Q: What is Spatie and why is it used?**

> `spatie/laravel-permission` is a Laravel package for role and permission management. It provides helpers like `$user->hasRole('admin')`, `$user->assignRole('teacher')`, and Blade directives like `@role('admin')`. It manages role storage in `model_has_roles` and `roles` tables. It was used in Reviso to simplify role assignment during signup/admin approval and to provide clean role-checking helpers throughout the application.

---

## 5. Core Features — How They Work

---

**Q: How does the pre-assessment vs. formal assessment distinction work?**

> Modules have a boolean column `is_formal_assessment`. When a teacher creates a module, they designate whether it is a pre-assessment (practice quiz, retake-allowed) or a formal assessment. In the UI, pre-assessments appear under the module/lecture content, and formal assessments appear under the dedicated "Assessment" tab. Students can reset/retake pre-assessments. Formal assessments have attempt limits. The `StudentAssessmentController` filters by `is_formal_assessment = true` to populate the assessment tab.

---

**Q: How do AI-generated quiz questions work?**

> A teacher can trigger AI quiz generation from the class management page. The `ClassManagerController::generateQuizAi()` method collects the module content (or a prompt) and sends it to `CloudflareAI::run(model, payload)`. The AI returns a structured JSON response with questions, options (A/B/C/D), and correct answers. The controller parses this response and inserts `QuizQuestion` records into the database. The quality depends on the AI model and the prompt — the `AiSettingsResolver` provides the configured model, token limit, and system prompt.

---

**Q: How does the announcement read-tracking work?**

> When a student opens the announcements page, the controller calls a method that records an `AnnouncementRead` row for each announcement they view (if one does not already exist). The unread badge count is computed as: total announcements in the class minus the number of `AnnouncementRead` rows for that student in that class. The `reads()` relation on `Announcement` (with `AnnouncementRead`) and the `hasMany AnnouncementReads` on `User` are what make this efficient with a single query.

---

**Q: How is class performance tracked for the teacher?**

> The `PerformanceController` powers three teacher analytics views:
> - **Class Progress Tracker** — aggregates `ModuleProgress` records for all enrolled students to show completion percentages per module and across the class.
> - **Student Performance Detail** — shows individual `QuizAttempt` scores for a specific student.
> - **Assessment Analysis** — aggregates quiz answer data to identify which questions had the highest wrong-answer rate (item analysis).
> The `CloudflareAI::generateSummary(stats)` method can generate a natural-language summary of these stats on demand.

---

**Q: What happens when a student's account program is locked? Why does that exist?**

> The `program_locked` boolean on `users` prevents a student from changing their program track (Accountancy, Education, Psychology) after it has been set. This exists because the program determines which dashboard layout, class filters, and content the student sees. If a student could freely switch tracks, it could allow access to content intended for another program, break class enrollment filtering, or undermine the institutional program assignment. Admins can unlock it if there is a legitimate transfer.

---

## 6. AI Integration

---

**Q: What AI features does Reviso have?**

> Three: (1) **AI Quiz Generation** — teachers can generate quiz questions from module content. (2) **Quiz Insights** — after a student submits a quiz, AI analyzes their answer patterns and returns strong areas, weak areas, and a study recommendation. (3) **Class Performance Summary** — a teacher can generate a natural-language AI summary of aggregated class performance statistics.

---

**Q: How is the AI gated? Can any user trigger it any time?**

> No. Every AI call first passes through `AiSettingsResolver::isFeatureEnabled()`. This resolver reads the `ai_settings` table for global settings and the `classes.ai_settings` JSON column for class-specific overrides. If AI is disabled globally, no call goes out regardless of class settings. This allows a superadmin to kill AI features system-wide (e.g., cost control) or an admin to disable AI for a specific class.

---

**Q: What model does Cloudflare Workers AI use?**

> The specific model is configurable via `AiSettings` — a teacher or admin can configure it through the AI settings page. The system is not hardcoded to one model. Cloudflare Workers AI supports several text-generation models (e.g., `@cf/meta/llama-3.1-8b-instruct`, `@cf/mistral/mistral-7b-instruct-v0.1`). The `AiSettingsResolver` resolves which model to pass into `CloudflareAI::run()`.

---

**Q: What are the limitations of your AI implementation?**

> Honest answer: (1) Response quality depends entirely on the AI model and the prompt — malformed JSON from the AI can break quiz generation. (2) There is no retry/fallback mechanism if the Cloudflare API is unavailable. (3) Cached insights are stored as plain text — there is no re-generation trigger if the underlying quiz data changes. (4) The system does not implement responsible AI guardrails (content filtering on AI output). These are areas for improvement in a production version.

---

## 7. Email System

---

**Q: How does the email system work in Reviso?**

> Reviso uses the Gmail API with OAuth2 — not SMTP. The `GmailService` class authenticates using a service account or OAuth2 credentials stored in the environment, then calls the Gmail API's `messages.send` endpoint with a base64-encoded MIME message. This avoids SMTP port restrictions common in shared hosting. The `sendMail()` method is an alias for `send()` for backward compatibility with older callers in the codebase.

---

**Q: Why Gmail API instead of a transactional email provider like Mailgun or SendGrid?**

> The Gmail API integration was chosen because the institution likely already has a Google Workspace account, which means no additional third-party email subscription cost. In a production deployment, a dedicated transactional provider (Mailgun, Postmark) would be preferred for delivery reliability, bounce handling, and analytics. The current implementation is appropriate for the capstone scope.

---

**Q: What emails does Reviso send?**

> (1) Email verification on signup. (2) Admin approval notification. (3) Admin rejection notification. (4) Temporary password for password reset. The emails are sent at the appropriate controller points using `GmailService::sendMail()`.

---

## 8. Security

---

**Q: What security measures did you implement?**

> - **CSRF protection** on all state-changing forms and AJAX requests.
> - **Rate limiting** on login (10 attempts/minute via Laravel throttle).
> - **Role-based access control** — every protected route is guarded by middleware and/or inline role checks.
> - **Double enrollment verification** — email verification + admin approval before login is allowed.
> - **Sequential module lock** — enforced server-side (403) not just client-side.
> - **Mass assignment protection** — all Eloquent models use `$fillable` to whitelist assignable attributes.
> - **Defense-in-depth on admin chat block** — both UI removal and controller-level 403.
> - **Environment variables** for secrets (Gmail credentials, AI keys, DB credentials) — never hardcoded.

---

**Q: What is mass assignment and how does Laravel prevent it?**

> Mass assignment is a vulnerability where an attacker sends extra fields in an HTTP request (e.g., `role=admin`) that get directly written to the database via `Model::create($request->all())`. Laravel prevents this by requiring developers to declare a `$fillable` array on every model, listing only the fields that are safe to mass-assign. Any field not in `$fillable` is silently ignored during creation/update.

---

**Q: Is there any SQL injection risk in Reviso?**

> No, because Reviso uses Laravel's Eloquent ORM and Query Builder exclusively (no raw SQL strings with user input). Eloquent uses PDO prepared statements under the hood, which parameterize all values before execution. Raw queries are avoided (`DB::` is not used) per the project's coding standards. If raw queries were ever needed, `DB::select('...', [$binding])` with bound parameters would be the safe approach.

---

**Q: What about XSS (Cross-Site Scripting)?**

> Laravel's Blade templating auto-escapes all output rendered with `{{ }}` using `htmlspecialchars()`. This means if a user submits a message containing `<script>alert(1)</script>`, it is displayed as escaped text, not executed. Unescaped output (`{!! !!}`) is used sparingly and only for trusted admin-controlled content (e.g., sanitized HTML in certain views). TinyMCE (bundled in public/) provides a rich text editor with its own client-side sanitization for any WYSIWYG fields.

---

## 9. Testing

---

**Q: How is Reviso tested?**

> Using PHPUnit v11 through `php artisan test`. There are 15 feature test files and 1 unit test. Feature tests cover the HTTP request/response cycle — they simulate actual browser requests, check HTTP status codes, verify database state, and assert view content. The test database uses transactions or `RefreshDatabase` so tests do not pollute each other. Factories (e.g., `UserFactory`) are used to seed test data.

---

**Q: What specifically do the tests cover?**

> The test suite covers the core happy paths and failure paths: authentication flows (login, logout, registration, pending/rejected states), sequential module locks, announcement CRUD and validation, quiz submission and scoring, admin approval actions, role-based access (403 for unauthorized roles), and email notifications. Edge cases like submitting an empty quiz or accessing a locked module by direct URL are also tested.

---

**Q: What does a feature test for the sequential lock look like conceptually?**

> A test would: (1) Create a teacher and a class with two modules. (2) Create a student and enroll them. (3) Simulate a GET request to module 2's URL as the unapproved student. (4) Assert the response is 403. (5) Mark module 1 as completed in `ModuleProgress`. (6) Simulate the same GET request again. (7) Assert the response is 200.

---

**Q: What is not tested / what would you improve in the test suite?**

> Areas that could benefit from more coverage: (1) AI integration tests are difficult to write without mocking the Cloudflare API — currently they rely on manual testing. (2) End-to-end tests (browser automation via Laravel Dusk or Playwright) are not implemented — only HTTP-level tests. (3) Performance/load testing is not done. These would be natural additions in a production project.

---

## 10. Scalability & Limitations

---

**Q: What are Reviso's known limitations?**

> - **Single institution scope:** The current data model does not support multi-tenancy (multiple institutions in one deployment). Everything lives in one database with one `users` table.
> - **File storage:** Uploaded lecture files go to `storage/app` (local disk). In production at scale, this should move to a cloud object store (S3, Google Cloud Storage).
> - **No job queue for email:** Gmail API calls are synchronous in the request-response cycle. If the Gmail API is slow, the user waits. A queue (Redis/database-backed) would decouple this.
> - **AI is synchronous:** AI insight generation blocks the HTTP request. Long AI inference times directly affect UI responsiveness.
> - **Chat is polling-based (AJAX)**, not WebSocket-based — there can be a delay of several seconds before a new message appears.

---

**Q: If you had to scale Reviso to 10,000 concurrent users, what would you change?**

> - Move email and AI calls into queued jobs (Laravel Horizon with Redis).
> - Move file storage to S3/equivalent.
> - Add a caching layer (Redis) for frequently-read data like class lists, module lists, and AI-generated summaries.
> - Implement a WebSocket server (Laravel Reverb or Pusher) to replace AJAX polling in chat.
> - Add database read replicas for heavy analytics queries.
> - Consider horizontal scaling behind a load balancer (stateless sessions via Redis).

---

**Q: Does Reviso support mobile?**

> The application is not a native mobile app. It is a responsive web application — Bootstrap 5's grid system provides basic responsiveness so it is usable on mobile browsers. A dedicated mobile app (React Native, Flutter) is outside the current scope but would naturally follow from an API-first refactor of the backend.

---

## 11. Tough Follow-Up Questions

> These are the hardest ones a technical panelist might throw. Prepare these well.

---

**Q: Your system uses ID number as the username. What happens if an ID number is reassigned to a new student?**

> This is a valid edge case. Currently the `idnumber` field has a unique constraint in the database, so a new signup with a reused ID number would fail at the database level. The correct resolution in a production system would be: (1) students are never permanently deleted — their account is archived, and (2) institutional policy should prevent ID reuse. If reuse is unavoidable, the admin would need to archive the old account before a new one with that ID can be created.

---

**Q: You said admin approval is required — what prevents a student from enrolling in any class after approval?**

> Class enrollment is not self-service for students in the direct sense. Enrollment is typically managed by the teacher (who adds students to their class) or triggered by a join-class flow. The `class_user` pivot is only written by controller methods that verify the teacher role or appropriate authorization. A student cannot directly insert themselves into an arbitrary class via the UI — the enrollment endpoints are guarded by role checks.

---

**Q: What happens to quiz attempt data if a teacher deletes a module?**

> This depends on whether a cascade delete is defined. In the current schema, `quiz_attempts` and `module_progress` have a `module_id` foreign key. If the module is deleted without a cascade, the orphaned attempt rows would have a `module_id` pointing to a non-existent record — queries on those rows would either produce null relationships or break. The safe production answer is: modules should be soft-deleted (Laravel `SoftDeletes` trait) so historical quiz data is preserved and the foreign key remains valid.

---

**Q: The AI stores quiz insights in the quiz_attempts table. What if two students take the same quiz simultaneously and both trigger AI insight generation for the first time?**

> This is a race condition. Both requests check if the cached insight is null, both find null, and both dispatch an AI call. Both write back their result — the second write overwrites the first. In this case the result is not harmful (both AI calls for the same attempt return effectively the same insight), but it wastes two API calls. The robust solution is to use a database-level update with a check (`UPDATE ... WHERE ai_strong IS NULL`) or a queued job with a unique job key so only one AI call fires per attempt.

---

**Q: Your password reset sends a temporary password via email. What is the security risk of this approach?**

> Temporary passwords sent over email have two risks: (1) email is not an encrypted channel by default — if the email account is compromised, the temporary password is exposed. (2) If the user does not change the temporary password immediately after login, it remains valid. The industry-standard approach is a time-limited password reset link (as Laravel's built-in reset mechanism uses) rather than a plain temporary password. This is an acknowledged limitation of the current implementation that would be addressed before production deployment.

---

**Q: Why does Reviso not use Laravel's built-in `Auth::routes()` for all authentication?**

> Laravel's default `Auth::routes()` assumes email + password authentication with email-based password reset. Reviso has non-standard requirements: ID number as username, admin approval gate before login, staged signup (pre-registration table), and Gmail API for email. These requirements required custom authentication logic in `RegisterController`, `LoginController`, and the custom `PasswordResetLinkController`, which diverge enough from the defaults that using `Auth::routes()` would have required overriding most of it anyway.

---

**Q: Can a teacher see another teacher's class data?**

> No. The `PerformanceController` and `ClassManagerController` always scope queries to the authenticated teacher's own classes (`where('created_by', Auth::id())`). Route model binding and scoped queries ensure a teacher cannot access a class they did not create, even if they guess the class ID in the URL. Violations return a 403 or 404.

---

**Q: The system has a `last_seen_at` column on users. What is it used for?**

> It is populated by the `UpdateLastSeen` middleware that runs on every authenticated request. It enables features like showing whether a user is "online/recently active" and is the basis for activity tracking in the admin user management view. It can also be used to identify inactive accounts.

---

**Q: Your login throttle is per-IP, not per-account. What does this mean for security?**

> The `throttle:10,1` middleware means 10 failed login attempts per IP per minute. This is a blunt instrument with two problems: (1) **Shared IP vulnerability** — if 100 students share a classroom IP and take turns trying to log in, they collectively hit the limit quickly even with valid credentials. (2) **Brute-force weakness** — an attacker can target one specific account (e.g., `idnumber=12345`) with 10 attempts per minute from a single IP. A more robust system would track failed attempts per **username** (not IP) and lock the account after, say, 5 failed attempts for 30 minutes. This is a known gap in the current implementation that would be addressed in production (e.g., using a custom rate limiter or the `illuminate.auth.lock_attempts` pattern).

---

**Q: Does Reviso have per-account login attempt tracking or account lockout?**

> No. Currently there is no `failed_login_attempts` counter, no per-account lockout, and no time-based unlock mechanism. The system relies entirely on IP-based rate limiting. In a production environment, you would add: (1) a `failed_login_attempts` integer column to `users`, (2) a `locked_until` timestamp, (3) increment the counter on failed auth, (4) lock the account after 5 failures, and (5) unlock via admin action or after 30 minutes. This is a standard security hardening that has not yet been implemented in the capstone scope.

---

*Good luck on the mock defense. Know your own code — if you built it, you can defend it.*
