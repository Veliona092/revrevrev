# Reviso — Project Documentation

## What this project does

Reviso is a Laravel 12 web application providing a role-based education platform. Teachers and admins manage classes and modules; students enroll and consume content. It includes:

- **Authentication + signup flows** with role routing (roles: `psych`, `educ`, `teacher`, `admin`, `accountancy`) and custom password reset via Gmail API.
- **Classes** that students can join via signed invite links, with teacher-managed enrollment.
- **Modules** inside a class — uploaded documents (converted to PDF via LibreOffice) and quizzes.
- **Student module viewing** with scroll-based progress tracking and a redesigned Cisco-inspired quiz interface.
- **Quiz taking** — multiple choice, per-question answer submission, anti-cheat tab-switch detection, optional timer, AI-generated insights per attempt via Cloudflare Workers AI.
- **Class performance dashboard** for teachers — class average, pass/fail doughnut chart, per-question breakdown, student ranking, and AI-generated class summaries.
- **Direct messaging (chat)** — real-time polling, online/last-seen status, conversation list with auto-start from user search.
- **User search** — debounced real-time search, role-based visibility (admin sees all, others see shared-class users only).
- **Last seen tracking** — middleware updates `last_seen_at` on every authenticated request (cached to once per minute).
- **Modern UI** — Argon Dashboard fully replaced with a custom design system: dark sidebar, `DM Sans` + `Instrument Serif` typography, `zoom: 1.25` content scaling, drawer-based modals, consistent color-coded feedback across all pages.

---

## Design system

All layouts now use a shared custom CSS design system (no Argon dependency):

- **Font stack**: `DM Sans` (UI) + `Instrument Serif` (headings/numbers)
- **Sidebar**: `#0f0f0f` dark background, active nav bar accent
  - Teacher layout (`appTeach`): green accent `#6ee7b7`
  - Student layout (`app`): blue accent `#3b82f6`
- **Content area**: `#f5f5f4` warm off-white, `zoom: 1.25` scaling
- **Canvas fix**: `.rv-content canvas { zoom: 0.8; }` counteracts zoom for Chart.js elements
- **Drawers**: slide-in right-panel drawers replace Bootstrap modals (global `RvDrawer.open/close` helpers)
- **Color coding**: green ≥75%, amber ≥50%, red <50% — applied consistently to score pills, progress bars, question breakdown items, and the class average card

---

## Main components

### Routing / entrypoint
- **Server entry**: `public/index.php`
- **Web routes**: `routes/web.php` — consolidated into a single `auth` middleware group, fallback route moved to bottom, duplicate routes removed

### Controllers
| Controller | Responsibility |
|---|---|
| `ClassManagerController` | Class/module management, document upload/conversion, invite generation, quiz drafting, user search |
| `QuizController` | Serve quiz questions, handle per-question answer submission, generate AI insights |
| `PerformanceController` | Compute class performance stats, refresh/store AI summaries |
| `ChatController` | Direct message conversations, message sending/fetching, `last_seen_at` included in payloads |
| `LectureController` | Teacher lecture CRUD + file handling |
| `ProfileController` | Profile view, password update, email change |
| `AdminUserController` | Admin user listing + password reset |
| `TestAiController` | Temporary AI test endpoint |

### Domain models
| Model | Purpose |
|---|---|
| `User` | Role-based users; relationships to classes, chat, quiz attempts; `last_seen_at`, `isOnline()`, `lastSeenLabel()` helpers |
| `ClassModel` | Classes, enrollment via `class_user`, relationships to modules/announcements, `ai_summary` column |
| `Module` | Modules within a class — `is_quiz`, `time_limit`, `file_path`, `is_assignment` flags |
| `ModuleProgress` | Student progress per module (progress %, completed, completed_at) |
| `QuizQuestion` | Options (JSON), correct option, difficulty, order |
| `QuizAttempt` | Per-user/per-module attempt — score, total, percentage, passed |
| `QuizAnswer` | Submitted answers per attempt/question, `is_correct` flag |
| `Chat` | Direct message conversations, `kind = 'direct'`, participants pivot |
| `ChatMessage` | Individual messages with sender relationship |
| `Lecture` | Teacher lecture uploads with `file_url` accessor |

### Middleware
| Middleware | Purpose |
|---|---|
| `UpdateLastSeen` | Updates `users.last_seen_at` on every authenticated web request, cached once per minute via Laravel Cache to avoid DB hammering |

### Services
| Service | Purpose |
|---|---|
| `CloudflareAI` | Calls Cloudflare Workers AI `/run/{model}` — used for quiz insights (`generateInsights`), class summaries (`generateSummary`), and quiz question generation (`generateQuizAi`). Currently uses `llama-3.2-3b-instruct` (free tier). `array_slice` enforces question count cap post-generation. |
| `GmailService` | Sends transactional email via Google Gmail API using OAuth tokens stored at `storage/app/google/tokens.json` |

---

## Frontend views

### Layouts
| Layout | Used by | Accent color |
|---|---|---|
| `layouts/appTeach.blade.php` | Teacher role | Green `#6ee7b7` |
| `layouts/app.blade.php` | Student roles (psych, educ, accountancy) | Blue `#3b82f6` |
| `layouts/appAdmin.blade.php` | Admin role | — |
| `layouts/appAcc.blade.php` | Accountancy role | — |
| `layouts/domain.blade.php` | Module/quiz viewer (full-screen, no sidebar) | — |

### Key pages
| View | Description |
|---|---|
| `pages/teacher/teacher.blade.php` | Teacher dashboard — stat cards (students, pending quizzes, avg score), class cards with per-class avg, recent activity feed, messages, announcements |
| `pages/teacher/manageclass.blade.php` | Class management — class cards grid, slide-in drawers for student management and module upload/quiz creation |
| `pages/teacher/student-performance.blade.php` | Performance dashboard — color-coded class avg card, pass/fail doughnut chart (Chart.js, manually sized for DPI), per-question breakdown with color-coded bars, student ranking with medals, AI insights with markdown rendering |
| `pages/chat/index.blade.php` | Direct messages — conversation list with online indicator, message bubbles, auto-start from `?user=ID`, polling paused when tab hidden |
| `pages/users/search.blade.php` | User search — debounced real-time search (300ms), program filter, click-to-chat row navigation |
| `pages/educ/educ.blade.php` | Student dashboard — enrolled classes count, pending assignments, overall avg, schedule, assignments, progress, announcements, messages |
| `pages/student/my-classes.blade.php` | Enrolled classes — stat row, class cards with teacher name, description, school year badge, animated entry |
| `pages/student/modules.blade.php` | Module/quiz viewer — dark navy sidebar with module list and progress bars, PDF iframe viewer, redesigned quiz interface with pill navigation, card-style radio options, polished result screen with SVG gauge and AI insights |

---

## HTTP endpoints

### Public (no auth)
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/` | closure | `home` |
| GET | `/login` | closure | `login` |
| POST | `/login` | closure | `login.post` |
| POST | `/logout` | closure | `logout` |
| GET | `/forgot-password` | closure | `password.request` |
| POST | `/forgot-password` | closure (GmailService) | `password.email` |
| GET | `/reset-password/{token}` | closure | `password.reset` |
| POST | `/reset-password` | closure | `password.update` |
| POST | `/signup` | closure (GmailService) | `signup.post` |
| POST | `/email/resend` | closure (GmailService) | `verification.resend` |
| GET | `/email/verify/{token}` | closure | `verification.verify` |
| GET | `/authorize-gmail` | closure | `gmail.authorize` |
| GET | `/oauth2callback` | closure | `oauth2callback` |
| GET | `/classes/{class}/join` | closure (signed) | `class.join` |

### Protected (auth middleware — single consolidated group)
#### Dashboards
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/dashboard` | closure (role redirect) | `dashboard` |
| GET | `/teacher-dashboard` | view | `teacherDashboard` |
| GET | `/psych-dashboard` | view | `psychDashboard` |
| GET | `/educ-dashboard` | view | `educDashboard` |
| GET | `/admin-dashboard` | view | `adminDashboard` |
| GET | `/accountancy-dashboard` | view | `accountancyDashboard` |

#### Profile
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/profile` | `ProfileController@show` | `profile` |
| POST | `/profile/password` | `ProfileController@updatePassword` | `profile.password.update` |
| POST | `/profile/email` | `ProfileController@updateEmail` | `profile.email.update` |
| POST | `/profile/email/verify` | `ProfileController@verifyEmailChange` | `profile.email.verify` |

#### Class management
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/manageclass` | `ClassManagerController@index` | `manageclass` |
| POST | `/classes` | `ClassManagerController@store` | `classes.store` |
| GET | `/users/search-students` | `ClassManagerController@searchStudents` | `students.search` |
| POST | `/classes/{class}/students` | `ClassManagerController@addStudents` | `classes.students.add` |
| DELETE | `/classes/{class}/students/{student}` | `ClassManagerController@removeStudent` | `classes.students.remove` |
| GET | `/classes/{class}/students` | `ClassManagerController@getClassStudents` | `classes.students.get` |
| POST | `/classes/{class}/invite` | `ClassManagerController@generateInvite` | `classes.invite` |
| POST | `/classes/{class}/modules` | `ClassManagerController@storeModule` | `classes.modules.store` |
| GET | `/classes/{class}/modules/list` | `ClassManagerController@listModulesJson` | `classes.modules.list` |
| GET | `/classes/{class}/modules` | `ClassManagerController@listModules` | `classes.modules.index` |
| GET | `/classes/{class}/modules/show` | `ClassManagerController@showModules` | `manage.modules` |

#### Student class/module views
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/my-classes` | `ClassManagerController@myClasses` | `student.classes` |
| GET | `/my-classes/{class}/modules` | `ClassManagerController@studentModules` | `student.modules` |
| GET | `/modules/{module}/view` | `ClassManagerController@viewModuleFile` | `module.view` |
| GET | `/modules/{module}/pdfjs` | `ClassManagerController@pdfjsViewer` | `module.pdfjs` |
| GET | `/modules/{module}/view-pdf` | `ClassManagerController@viewPdf` | `module.view.pdf` |
| DELETE | `/modules/{module}` | `ClassManagerController@deleteModule` | `modules.delete` |
| POST | `/modules/{module}/progress` | `ClassManagerController@updateProgress` | `modules.progress.update` |

#### Quiz
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/modules/{module}/quiz/create` | `ClassManagerController@createQuiz` | `quiz.create` |
| POST | `/modules/{module}/quiz/generate` | `ClassManagerController@generateQuizAi` | `quiz.generate` |
| POST | `/modules/{module}/quiz/store` | `ClassManagerController@storeQuizManual` | `quiz.store` |
| GET | `/modules/{module}/quiz/questions` | `QuizController@getQuestions` | `quiz.get.questions` |
| POST | `/modules/{module}/quiz/submit` | `QuizController@submitQuiz` | `quiz.submit` |
| POST | `/modules/{module}/quiz/insights` | `QuizController@generateInsights` | `quiz.insights` |
| POST | `/quiz/{module}/answer` | `QuizController@submitAnswer` | `quiz.answer` |
| POST | `/quiz/create-draft/{class}` | `QuizController@createQuizDraft` | `quiz.create.draft` |

#### Performance
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/student-performance/{class}` | `PerformanceController@studentPerformance` | `student.performance` |
| POST | `/student-performance/{class}/refresh-ai` | `PerformanceController@refreshAiSummary` | `student.performance.refresh` |

#### Chat + user search
| Method | URI | Handler | Route name |
|---|---|---|---|
| GET | `/chat` | `ChatController@index` | `chat.index` |
| GET | `/chat/conversations` | `ChatController@conversations` | `chat.conversations` |
| GET | `/chat/conversations/{chat}/messages` | `ChatController@messages` | `chat.messages` |
| POST | `/chat/conversations` | `ChatController@start` | `chat.start` |
| POST | `/chat/conversations/{chat}/messages` | `ChatController@sendMessage` | `chat.send_message` |
| POST | `/chat/conversations/{chat}/remove` | `ChatController@remove` | `chat.conversations.remove` |
| GET | `/users/search` | `ClassManagerController@searchUsers` | `users.search.api` |
| GET | `/users/search-page` | `ClassManagerController@searchPage` | `users.search` |

---

## Key dependencies

### PHP (composer.json)
| Package | Purpose |
|---|---|
| `laravel/framework ^12.0` | Core framework |
| `spatie/laravel-permission` | Role/permission middleware |
| `livewire/livewire ^4.1` | Component support |
| `google/apiclient 2.19` | Gmail API for email sending |
| `firebase/php-jwt ^7.0` | JWT support |
| `smalot/pdfparser ^2.12` | PDF text extraction for AI quiz generation context |
| `phpseclib/phpseclib ^3.0` | Cryptography support |
| `laravel/tinker ^2.10.1` | REPL |

### Node / front-end
- Build tooling via `gulp` (Argon Dashboard legacy — not used for new pages)
- New pages use Google Fonts CDN (`DM Sans`, `Instrument Serif`), Font Awesome, Chart.js via CDN, Select2 via CDN

---

## Known issues / things to fix before deployment

- `GET /test-ai-laravel` is unprotected — should be removed or gated behind admin middleware
- `ClassManagerController` is very large — quiz generation logic (`generateQuizAi`) should be moved to `QuizController`
- `llama-3.2-3b-instruct` over-generates quiz questions — mitigated with `array_slice` but upgrading to `llama-3.1-8b` would fix it properly
- `layouts/domain.blade.php` needs `<!DOCTYPE html>` added to fix Quirks Mode warning
- PDF iframe OpaqueResponseBlocking — serve PDFs through a controller route with proper `X-Frame-Options: SAMEORIGIN` and `Cross-Origin-Resource-Policy: same-origin` headers instead of direct `/storage/` URLs
- Assessment and Progress Tracker nav items in student sidebar both point to `route('assessment')` — Progress Tracker needs its own route and page
- `ai_summary` on `ClassModel` stores raw AI markdown — the blade parses it client-side with JS; consider storing pre-parsed HTML or using a proper markdown library server-side