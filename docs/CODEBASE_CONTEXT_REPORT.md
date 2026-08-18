# Reviso LMS - Codebase Context Report

**Generated:** June 29, 2026  
**Purpose:** Comprehensive documentation for external AI agents to understand the Reviso Learning Management System codebase.

---

## 1. Project Overview

### What the System Does
Reviso is a Learning Management System (LMS) designed for educational institutions to manage classes, deliver course content, administer assessments (quizzes and mock board exams), track student progress, and provide AI-powered analytics. The system supports multiple academic programs (Psychology, Education, Accountancy) and includes features for teachers, students, and administrators.

### Target Users
- **Students:** View course materials, take quizzes/assessments, track progress, participate in class chats
- **Teachers:** Create and manage classes, upload lecture materials, create quizzes, view student performance, manage mock board exams
- **Administrators:** Approve user registrations, manage system-wide settings, view analytics
- **Superadmins:** Full system access including global AI settings management

### Tech Stack
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade templates, Vite, TailwindCSS, Argon Dashboard (Bootstrap 4 theme)
- **Database:** MySQL
- **Real-time:** Livewire v4 for reactive components
- **AI Integration:** Cloudflare Workers AI (Llama 3.1-8B-instruct model)
- **Email:** Gmail API via custom service
- **Authentication:** Laravel's built-in auth with custom role-based access control
- **Authorization:** Laravel Gates + Spatie Laravel Permission package
- **Testing:** PHPUnit 11

### How to Run Locally

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Configure database in .env
DB_DATABASE=reviso
DB_USERNAME=root
DB_PASSWORD=

# 4. Run migrations
php artisan migrate

# 5. Start development server
php artisan serve

# 6. (Optional) Start Vite for asset compilation
npm run dev

# 7. (Optional) Full dev stack with queue and logs
composer run dev
```

**Environment Variables Required:**
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN` (for AI features)
- `MAIL_*` settings for Gmail integration

---

## 2. Folder Structure

```
myproject/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # All HTTP controllers
│   │   ├── Middleware/          # Custom middleware (UpdateLastSeen)
│   │   ├── Requests/            # Form request validation classes
│   │   └── Kernel.php           # HTTP kernel defining middleware groups
│   ├── Models/                  # Eloquent models
│   ├── Providers/               # Service providers (AuthServiceProvider)
│   ├── Services/                # Business logic services
│   ├── Auth/                    # Authentication-related classes
│   ├── Mail/                    # Mailable classes
│   └── notifications/           # Notification classes
├── bootstrap/                   # Framework bootstrap files
├── config/                      # Configuration files
├── database/
│   ├── migrations/              # Database migrations
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories
├── docs/                        # Documentation files
├── public/                      # Public web root
├── resources/
│   ├── views/                   # Blade templates
│   │   ├── layouts/             # Layout templates (appTeach, appAdmin, etc.)
│   │   ├── pages/               # Page-specific views
│   │   │   ├── admin/           # Admin pages
│   │   │   ├── student/         # Student pages
│   │   │   ├── teacher/         # Teacher pages
│   │   │   └── mock-boards/     # Mock board specific pages
│   │   ├── reusable/           # Reusable components (navbars)
│   │   ├── auth/                # Auth pages
│   │   └── emails/             # Email templates
│   ├── sass/                    # SASS/SCSS files
│   └── js/                      # JavaScript files
├── routes/
│   ├── web.php                  # Web routes (main route file)
│   ├── api.php                  # API routes
│   └── console.php              # Console routes
├── storage/                     # Storage files (logs, uploads)
├── tests/                       # PHPUnit tests
├── vendor/                      # Composer dependencies
└── node_modules/               # NPM dependencies
```

### Non-Standard Organization
- **Multiple role-specific layouts:** `appTeach.blade.php`, `appAdmin.blade.php`, `appPsych.blade.php`, `appEduc.blade.php`, `appAcc.blade.php` - each tailored for specific user roles/programs
- **Program-based student dashboards:** Separate views for Psychology, Education, and Accountancy programs
- **Custom middleware:** `UpdateLastSeen` middleware tracks user online status
- **Service layer:** Business logic extracted to `app/Services/` (CloudflareAI, GmailService, MockBoardStatisticsService)

---

## 3. Key Files

### Routes
- **`routes/web.php`** (34KB): Main web routes file containing all application routes. Includes:
  - Public routes (login, signup)
  - Authenticated routes grouped by role
  - Mock Board routes (batch analytics, student, teacher)
  - Class management routes
  - Chat routes
  - Assessment routes

### Controllers
- **`app/Http/Controllers/ClassManagerController.php`** (59KB): Handles class CRUD, module management, student enrollment, lecture uploads
- **`app/Http/Controllers/PerformanceController.php`** (29KB): Student performance analytics, class progress tracker
- **`app/Http/Controllers/MockBoardController.php`** (18KB): Mock board CRUD, phase management, class analysis
- **`app/Http/Controllers/BatchAnalyticsController.php`** (6KB): Program-level batch analytics for mock boards
- **`app/Http/Controllers/StudentMockBoardController.php`** (9KB): Student-facing mock board functionality
- **`app/Http/Controllers/QuizController.php`** (10KB): Quiz creation and management
- **`app/Http/Controllers/ChatController.php`** (11KB): Real-time chat functionality
- **`app/Http/Controllers/AnnouncementController.php`** (9KB): Class announcements
- **`app/Http/Controllers/AdminApprovalController.php`** (7KB): User registration approval
- **`app/Http/Controllers/AiSettingsController.php`** (3KB): AI configuration management

### Models
- **`app/Models/User.php`**: User authentication, relationships to classes, modules, chats, quiz attempts. Has `isOnline()` and `lastSeenLabel()` helper methods
- **`app/Models/ClassModel.php`**: Class model with relationships to users (students), modules, announcements. Has `students()` alias for enrolled students
- **`app/Models/Module.php`**: Course content (lectures, quizzes, assignments). Supports visibility controls and quiz questions
- **`app/Models/QuizQuestion.php`**: Multiple choice questions with JSON options
- **`app/Models/QuizAttempt.php`**: Student quiz attempts with score tracking
- **`app/Models/QuizAnswer.php`**: Individual question answers within attempts
- **`app/Models/MockBoard.php`**: Mock board exam definitions with phases and visibility
- **`app/Models/MockBoardPhase.php`**: Pre-test and Pre-boards phases
- **`app/Models/MockBoardAttempt.php`**: Student mock board attempts
- **`app/Models/MockBoardStatistic.php`**: Statistical analysis results (ANOVA, means, std dev)
- **`app/Models/ModuleProgress.php`**: Student progress tracking per module
- **`app/Models/Chat.php`**: Chat rooms/conversations
- **`app/Models/ChatMessage.php**`: Individual chat messages
- **`app/Models/Announcement.php`**: Class announcements
- **`app/Models/AiSetting.php`**: AI configuration key-value store

### Services
- **`app/Services/CloudflareAI.php`**: Cloudflare Workers AI integration for quiz generation and insights
- **`app/Services/GmailService.php`**: Gmail API integration for email sending
- **`app/Services/AiSettingsResolver.php`**: Resolves AI prompts and model settings from database
- **`app/Services/MockBoardStatisticsService.php`**: Statistical calculations (ANOVA, paired t-test, item analysis, hierarchical batch stats)

### Views
**Layouts:**
- **`resources/views/layouts/appTeach.blade.php`**: Teacher dashboard layout with sidebar navigation
- **`resources/views/layouts/appAdmin.blade.php`**: Admin dashboard layout
- **`resources/views/layouts/appPsych.blade.php`**: Psychology program student layout
- **`resources/views/layouts/appEduc.blade.php`**: Education program student layout
- **`resources/views/layouts/appAcc.blade.php`**: Accountancy program student layout
- **`resources/views/layouts/quiz.blade.php`**: Quiz-taking layout

**Reusable Components:**
- **`resources/views/reusable/navbarTeacher.blade.php`**: Teacher navigation bar
- **`resources/views/reusable/navbarPsych.blade.php`**: Psychology program navbar
- **`resources/views/reusable/navbarEducation.blade.php`**: Education program navbar
- **`resources/views/reusable/navbarAccountancy.blade.php`**: Accountancy program navbar
- **`resources/views/reusable/navbarAdmin.blade.php`**: Admin navbar

**Key Pages:**
- **`resources/views/pages/student/modules.blade.php`** (47KB): Student module list with quiz taking interface
- **`resources/views/pages/student/assessment-take.blade.php`** (27KB): Assessment taking interface with anti-cheat
- **`resources/views/pages/teacher/manageclass.blade.php`** (76KB): Class management interface
- **`resources/views/pages/teacher/student-performance.blade.php`** (63KB): Student performance analytics
- **`resources/views/pages/teacher/mock-boards/batch-dashboard.blade.php`**: Mock board batch analytics dashboard
- **`resources/views/pages/teacher/mock-boards/batch-analysis.blade.php`**: Detailed mock board analysis

### Config Files
- **`config/services.php`**: Third-party service configuration (Cloudflare AI credentials)
- **`.env`**: Environment variables (database, mail, AI credentials)

### Middleware
- **`app/Http/Middleware/UpdateLastSeen.php`**: Updates user's `last_seen_at` timestamp (cached to once per minute)

### Migration Files
- **`database/migrations/0001_01_01_000000_create_users_table.php`**: Users table with custom `idnumber` login field
- **`database/migrations/2026_03_04_223009_create_classes_table.php`**: Classes table
- **`database/migrations/2026_03_04_223010_create_class_user_table.php`**: Class-user many-to-many pivot
- **`database/migrations/2026_03_15_063224_create_modules_table.php`**: Modules/lectures table
- **`database/migrations/2026_03_17_123505_create_quiz_questions_table.php`**: Quiz questions with JSON options
- **`database/migrations/2026_03_19_185728_create_quiz_attempts_table.php`**: Quiz attempts tracking
- **`database/migrations/2026_03_19_211009_create_quiz_answers_table.php`**: Individual question answers
- **`database/migrations/2026_05_10_101436_create_mock_boards_table.php`**: Mock board definitions
- **`database/migrations/2026_03_28_104028_create_ai_settings_table.php`**: AI settings with default prompts

---

## 4. Database Schema

### Core Tables

#### `users`
- **id** (bigint, primary, auto-increment)
- **idnumber** (string, unique) - Custom login field (student ID)
- **name** (string, nullable)
- **email** (string, unique, nullable)
- **email_verified_at** (timestamp, nullable)
- **password** (string, hashed)
- **role** (string) - Values: `student`, `teacher`, `admin`, `superadmin`, `psych`, `educ`, `accountancy` (legacy program roles)
- **program** (string) - Values: `psychology`, `education`, `accountancy`, `computer`, `engineering`
- **program_locked** (boolean) - Whether program can be changed
- **status** (string) - Account status: `pending`, `approved`, `rejected`
- **rejection_reason** (string, nullable)
- **last_seen_at** (timestamp, nullable) - Online status tracking
- **remember_token** (string, nullable)
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsToMany: `classes()` via `class_user` pivot
- HasMany: `moduleProgress()`, `quizAttempts()`, `chatMessages()`
- BelongsToMany: `chats()` via `chat_user` pivot

#### `classes`
- **id** (bigint, primary)
- **name** (string, 100)
- **code** (string, 20, unique, nullable)
- **school_year** (year, nullable)
- **description** (text, nullable)
- **program** (string, nullable) - Program track: psychology, education, accountancy
- **created_by** (foreign key → users.id, cascade delete)
- **ai_summary** (text, nullable) - AI-generated class summary
- **assessment_ai_summary** (text, nullable) - AI assessment summary
- **ai_settings** (json, nullable) - Class-specific AI configuration
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `creator()` → User
- BelongsToMany: `users()`, `students()` (filtered to role=student) via `class_user`
- HasMany: `modules()`, `announcements()`, `mockBoards()`

#### `class_user` (Pivot Table)
- **id** (primary)
- **class_id** (foreign key → classes.id, cascade delete)
- **user_id** (foreign key → users.id, cascade delete)
- **joined_at** (timestamp, default current)
- **assessment_ai_analysis** (text, nullable) - Per-student AI analysis
- **created_at**, **updated_at** (timestamps)
- **Unique constraint:** (class_id, user_id)

#### `modules`
- **id** (primary)
- **class_id** (foreign key → classes.id, cascade delete)
- **title** (string)
- **description** (text, nullable)
- **file_path** (string, nullable) - Path to lecture file
- **file_type** (string, nullable) - pdf, pptx, docx, quiz, assignment
- **order** (integer, default 0) - Sort order
- **is_quiz** (boolean, default false)
- **is_assignment** (boolean, default false)
- **is_formal_assessment** (boolean, default false) - Enables anti-cheat for assessments
- **is_mock_board** (boolean, default false) - Marks as mock board module
- **time_limit** (integer, nullable) - Time limit in minutes
- **passing_grade** (integer, nullable) - Passing percentage
- **visibility** (string) - `all`, `selected`, `except`
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `class()` → ClassModel
- HasMany: `progress()`, `quizQuestions()`, `attempts()`
- BelongsToMany: `visibleTo()` via `module_user_visibility`

#### `module_progress`
- **id** (primary)
- **module_id** (foreign key → modules.id, cascade delete)
- **user_id** (foreign key → users.id, cascade delete)
- **progress** (decimal 5,2, default 0.00) - 0.00 to 100.00
- **scroll_position** (integer, nullable) - Reading position
- **completed** (boolean, default false)
- **completed_at** (timestamp, nullable)
- **created_at**, **updated_at** (timestamps)
- **Unique constraint:** (module_id, user_id)

#### `quiz_questions`
- **id** (primary)
- **module_id** (foreign key → modules.id, cascade delete)
- **question_text** (text) - TinyMCE content (HTML)
- **options** (json) - `{"A": "Paris", "B": "Florida", "C": "Tokyo", "D": "London"}`
- **correct_option** (string) - "A", "B", "C", or "D"
- **points** (integer, default 1)
- **order** (integer, default 0)
- **difficulty** (string, default "Normal") - Easy/Normal/Hard
- **topic** (string, nullable) - Question topic for analytics
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `module()` → Module
- HasMany: `answers()`

#### `quiz_attempts`
- **id** (primary)
- **user_id** (foreign key → users.id, cascade delete)
- **module_id** (foreign key → modules.id, cascade delete)
- **mock_board_id** (foreign key → mock_boards.id, nullable) - Links to mock board
- **mock_board_phase_type** (string, nullable) - `pre_test` or `pre_boards`
- **score** (integer) - Number of correct answers
- **total** (integer) - Total questions
- **percentage** (integer) - Score percentage (0-100)
- **passed** (boolean, default false)
- **time_taken** (integer, nullable) - Time in minutes
- **attempt_count** (integer, default 1) - Number of retakes
- **ai_strong** (text, nullable) - AI insights: strong areas
- **ai_weak** (text, nullable) - AI insights: weak areas
- **ai_recommendation** (text, nullable) - AI insights: recommendations
- **attempted_at** (timestamp, default current)
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `user()` → User, `module()` → Module
- HasMany: `answers()`

#### `quiz_answers`
- **id** (primary)
- **attempt_id** (foreign key → quiz_attempts.id, cascade delete)
- **question_id** (foreign key → quiz_questions.id, cascade delete)
- **selected_option** (string) - A, B, C, or D
- **is_correct** (boolean, default false)
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `attempt()` → QuizAttempt, `question()` → QuizQuestion

### Mock Board Tables

#### `mock_boards`
- **id** (primary)
- **class_id** (foreign key → classes.id, cascade delete)
- **teacher_id** (foreign key → users.id, cascade delete) - Creator
- **program** (string, nullable) - Program track for batch analytics
- **title** (string)
- **description** (text, nullable)
- **review_period_start** (date)
- **review_period_end** (date)
- **passing_percentage** (integer, default 75)
- **visibility** (enum) - `all`, `selected`, `except`
- **visible_to** (json, nullable) - Array of user IDs
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `class()` → ClassModel, `teacher()` → User
- HasMany: `phases()`, `attempts()`, `statistics()`

#### `mock_board_phases`
- **id** (primary)
- **mock_board_id** (foreign key → mock_boards.id, cascade delete)
- **phase_type** (enum) - `pre_test`, `pre_boards`
- **title** (string)
- **module_id** (foreign key → modules.id, cascade delete) - Links to quiz module
- **question_ids** (json, nullable) - Array of question IDs
- **is_same_questions** (boolean, default false) - Whether phases use same questions
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `mockBoard()` → MockBoard, `module()` → Module

#### `mock_board_attempts`
- **id** (primary)
- **user_id** (foreign key → users.id, cascade delete)
- **mock_board_id** (foreign key → mock_boards.id, cascade delete)
- **phase_type** (enum) - `pre_test`, `pre_boards`
- **quiz_attempt_id** (foreign key → quiz_attempts.id, nullable)
- **score** (integer, nullable)
- **total** (integer, nullable)
- **percentage** (decimal, nullable)
- **passed** (boolean, nullable)
- **attempt_count** (integer, default 1)
- **ai_strong** (text, nullable)
- **ai_weak** (text, nullable)
- **ai_recommendation** (text, nullable)
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `user()` → User, `mockBoard()` → MockBoard, `quizAttempt()` → QuizAttempt

#### `mock_board_statistics`
- **id** (primary)
- **mock_board_id** (foreign key → mock_boards.id, unique)
- **class_id** (foreign key → classes.id)
- **total_students** (integer, nullable)
- **pre_test_count** (integer, nullable)
- **pre_test_mean** (decimal, nullable)
- **pre_test_std_dev** (decimal, nullable)
- **pre_boards_count** (integer, nullable)
- **pre_boards_mean** (decimal, nullable)
- **pre_boards_std_dev** (decimal, nullable)
- **anova_f_statistic** (decimal, nullable)
- **anova_p_value** (decimal, nullable)
- **anova_significant** (boolean, nullable)
- **improvement_percentage** (decimal, nullable)
- **computed_at** (timestamp, nullable)
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `mockBoard()` → MockBoard, `class()` → ClassModel

### Communication Tables

#### `announcements`
- **id** (primary)
- **class_id** (foreign key → classes.id, cascade delete)
- **user_id** (foreign key → users.id, cascade delete) - Poster
- **message** (text)
- **is_pinned** (boolean, default false)
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `class()` → ClassModel, `user()` → User
- HasMany: `reads()`

#### `announcement_reads`
- **id** (primary)
- **announcement_id** (foreign key → announcements.id, cascade delete)
- **user_id** (foreign key → users.id, cascade delete)
- **read_at** (timestamp, default current)
- **created_at**, **updated_at** (timestamps)

#### `chats`
- **id** (primary)
- **kind** (string, default "direct") - "direct" for 1-on-1
- **created_by** (foreign key → users.id, cascade delete)
- **class_id** (foreign key → classes.id, nullable, null on delete)
- **title** (string, nullable) - For future group chats
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `creator()` → User, `class()` → ClassModel
- BelongsToMany: `users()` via `chat_user`
- HasMany: `messages()`

#### `chat_user` (Pivot Table)
- **id** (primary)
- **chat_id** (foreign key → chats.id, cascade delete)
- **user_id** (foreign key → users.id, cascade delete)
- **created_at**, **updated_at** (timestamps)
- **Unique constraint:** (chat_id, user_id)

#### `chat_messages`
- **id** (primary)
- **chat_id** (foreign key → chats.id, cascade delete)
- **sender_id** (foreign key → users.id, cascade delete)
- **body** (longText)
- **created_at**, **updated_at** (timestamps)

**Relationships:**
- BelongsTo: `chat()` → Chat, `sender()` → User

### AI Configuration

#### `ai_settings`
- **id** (primary)
- **key** (string, unique) - Configuration key
- **value** (longText, nullable) - JSON-encoded value
- **created_at**, **updated_at** (timestamps)

**Default Keys:**
- `feature.quiz_generation_enabled` - Enable/disable quiz generation
- `feature.quiz_insights_enabled` - Enable/disable quiz insights
- `feature.class_summary_enabled` - Enable/disable class summaries
- `prompt.quiz_generation.system` - System prompt for quiz generation
- `prompt.quiz_generation.user_template` - User template for quiz generation
- `prompt.quiz_insights.system` - System prompt for quiz insights
- `prompt.quiz_insights.user_template` - User template for quiz insights
- `prompt.class_summary.system` - System prompt for class summaries
- `prompt.class_summary.user_template` - User template for class summaries

### Other Tables

#### `signups`
- **id** (primary)
- **name** (string, nullable)
- **idnumber** (string, unique)
- **email** (string, unique)
- **password** (string)
- **role** (string, nullable) - Applied role
- **status** (string, default "pending") - pending, approved, rejected
- **rejection_reason** (string, nullable)
- **created_at**, **updated_at** (timestamps)

#### `lectures`
- **id** (primary)
- **class_id** (foreign key → classes.id, cascade delete)
- **title** (string)
- **description** (text, nullable)
- **file_path** (string, nullable)
- **file_type** (string, nullable)
- **order** (integer, default 0)
- **created_at**, **updated_at** (timestamps)

#### `module_user_visibility`
- **id** (primary)
- **module_id** (foreign key → modules.id, cascade delete)
- **user_id** (foreign key → users.id, cascade delete)
- **created_at**, **updated_at** (timestamps)

#### `jobs` - Laravel queue jobs table
#### `cache` - Laravel cache table
#### `sessions` - Laravel session storage
#### `password_reset_tokens` - Laravel password reset tokens

---

## 5. Features & Modules

### 1. Authentication & User Management
**Files:** `routes/web.php`, `RegisterController.php`, `AdminApprovalController.php`, `User.php`

**Description:** Custom authentication system using `idnumber` as login field instead of email. Supports role-based access control with admin approval workflow for new registrations.

**Data Flow:**
1. User signs up → creates `signup` record with status `pending`
2. Admin reviews pending signups → approves/rejects
3. Approved signup → creates `users` record with role
4. User logs in with `idnumber` and password
5. Middleware `UpdateLastSeen` tracks online status

**Roles:**
- `student` - Can view enrolled classes, take assessments
- `teacher` - Can create/manage classes, upload content, create quizzes
- `admin` - Can approve users, manage system settings
- `superadmin` - Full access including global AI settings
- Legacy: `psych`, `educ`, `accountancy` (now stored in `program` field)

### 2. Class Management
**Files:** `ClassManagerController.php`, `ClassModel.php`, `manageclass.blade.php`

**Description:** Teachers can create classes, enroll students, manage class settings. Classes can be assigned to specific programs (psychology, education, accountancy) for batch analytics.

**Data Flow:**
1. Teacher creates class → `classes` record with `created_by`
2. Students join class → `class_user` pivot record
3. Teacher uploads modules/lectures → `modules` records linked to class
4. Teacher creates quizzes → `quiz_questions` linked to modules
5. Students view content → filtered by enrollment in `class_user`

### 3. Module & Content Delivery
**Files:** `Module.php`, `modules.blade.php`, `docx-viewer.blade.php`, `pdfjs-viewer.blade.php`

**Description:** Teachers upload lecture materials (PDF, DOCX, PPTX) and create quiz modules. Students view content and track reading progress.

**Data Flow:**
1. Teacher uploads file → `modules.file_path` and `file_type` set
2. Student views module → `module_progress` record created/updated
3. Progress tracking → `progress` field (0-100), `scroll_position` saved
4. Completion → `completed` set to true when 100% progress

**Module Types:**
- Regular lectures (file_path set)
- Quizzes (`is_quiz = true`)
- Assignments (`is_assignment = true`)
- Formal assessments (`is_formal_assessment = true` - enables anti-cheat)
- Mock board modules (`is_mock_board = true`)

### 4. Quiz System
**Files:** `QuizController.php`, `QuizQuestion.php`, `QuizAttempt.php`, `QuizAnswer.php`, `quiz-create.blade.php`, `assessment-take.blade.php`

**Description:** Teachers create multiple-choice quizzes with TinyMCE editor. Students take quizzes with time limits and passing grades. System tracks attempts and provides AI-powered insights.

**Data Flow:**
1. Teacher creates quiz → `modules.is_quiz = true`
2. Teacher adds questions → `quiz_questions` with JSON options
3. Student starts quiz → `quiz_attempts` record created
4. Student submits → `quiz_answers` records for each question
5. Score calculated → `percentage`, `passed` fields updated
6. AI insights generated → Cloudflare AI analyzes answers

**Quiz Features:**
- Multiple choice (A/B/C/D)
- Question difficulty levels
- Time limits
- Passing grades
- Retake attempts
- AI-powered weak/strong area analysis

### 5. Anti-Cheat System
**Files:** `assessment-take.blade.php`, `modules.blade.php`

**Description:** JavaScript-based anti-cheat for formal assessments (`is_formal_assessment = true`). Monitors tab switching and browser focus.

**Data Flow:**
1. Student launches assessment → `isFormalAssessment = true` in JS
2. Anti-cheat starts → Monitors visibilitychange and blur events
3. Tab switch detected → Warning counter increments
4. 3rd violation → Auto-submit as failed
5. Pre-assessments skip anti-cheat → `isFormalAssessment = false`

**Warning Levels:**
- Warning 1: "You have switched tabs 1 time"
- Warning 2: "You have switched tabs 2 times"
- Warning 3: Auto-submit, mark as failed

### 6. Mock Boards
**Files:** `MockBoardController.php`, `StudentMockBoardController.php`, `MockBoard.php`, `MockBoardPhase.php`, `MockBoardAttempt.php`, `MockBoardStatisticsService.php`, `batch-dashboard.blade.php`, `batch-analysis.blade.php`

**Description:** Comprehensive mock board exam system with Pre-Test and Pre-Boards phases. Includes statistical analysis (ANOVA, paired t-test) to measure student improvement between phases.

**Data Flow:**
1. Teacher creates mock board → `mock_boards` record with review period
2. System creates phases → `mock_board_phases` (pre_test, pre_boards)
3. Each phase creates module → `modules` with `is_mock_board = true`, `is_formal_assessment = true`
4. Students take pre-test → `mock_board_attempts` with phase_type = 'pre_test'
5. Students take pre-boards → `mock_board_attempts` with phase_type = 'pre_boards'
6. Statistics computed → `mock_board_statistics` with ANOVA results
7. Batch analytics → Program-level aggregation across classes

**Mock Board Features:**
- Two-phase structure (Pre-Test, Pre-Boards)
- Visibility controls (all students, selected, except)
- Review period dates
- Passing percentage
- Statistical analysis (ANOVA, means, standard deviations)
- Batch analytics by program
- AI insights per attempt

**Statistical Calculations (MockBoardStatisticsService.php):**
- One-way ANOVA between pre-test and pre-boards scores
- Paired t-test for same students
- Item analysis (difficulty, discrimination index)
- Hierarchical batch stats (individual → class → batch)
- Passing rates per phase

### 7. Performance Analytics
**Files:** `PerformanceController.php`, `student-performance.blade.php`, `class-progress-tracker.blade.php`

**Description:** Teacher dashboard showing student performance across modules and assessments. Includes class progress tracker and individual student analysis.

**Data Flow:**
1. Teacher views class → Aggregates `module_progress` and `quiz_attempts`
2. Progress calculated → Combines module completion and quiz scores
3. Performance displayed → Charts and tables per student
4. Weak areas identified → Based on quiz question performance

**Analytics Features:**
- Per-student module completion
- Quiz scores and passing rates
- Class-wide averages
- Weak topic identification
- AI-generated class summaries

### 8. Batch Analytics
**Files:** `BatchAnalyticsController.php`, `batch-dashboard.blade.php`, `batch-analysis.blade.php`

**Description:** Program-level analytics for mock boards across multiple classes. Aggregates data by program (psychology, education, accountancy) to show batch-wide performance.

**Data Flow:**
1. Admin/teacher views batch analytics → Filtered by program
2. Mock boards fetched → All boards with students in program
3. Statistics aggregated → Pre-test vs pre-boards comparison
4. Hierarchical display → Program → Classes → Students
5. ANOVA results → Statistical significance of improvement

**Batch Analytics Features:**
- Program tabs (Psychology, Education, Accountancy)
- Mock board cards per program
- Summary statistics (participants, passing rates)
- Detailed analysis per mock board
- Student improvement tracking
- ANOVA significance testing

### 9. Chat System
**Files:** `ChatController.php`, `Chat.php`, `ChatMessage.php`, `chat.blade.php`

**Description:** Real-time messaging between users. Currently supports direct 1-on-1 chats. Designed for future group chat support.

**Data Flow:**
1. User initiates chat → `chats` record created (kind = 'direct')
2. Users added → `chat_user` pivot records
3. Messages sent → `chat_messages` records
4. Messages displayed → Ordered by created_at

**Chat Features:**
- Direct messaging
- Online status indicators
- Message timestamps
- Class-linked chats (optional)

### 10. Announcements
**Files:** `AnnouncementController.php`, `Announcement.php`, `AnnouncementRead.php`

**Description:** Teachers post announcements to classes. System tracks which students have read announcements.

**Data Flow:**
1. Teacher posts announcement → `announcements` record
2. Students view class → Announcement displayed
3. Read tracking → `announcement_reads` record created
4. Pinned announcements → Displayed at top

**Announcement Features:**
- Class-specific announcements
- Read/unread tracking
- Pinning for important messages
- Timestamps

### 11. AI Integration
**Files:** `CloudflareAI.php`, `AiSettingsResolver.php`, `GmailService.php`, `ai_settings` table

**Description:** Cloudflare Workers AI (Llama 3.1-8B-instruct) integration for:
- Quiz question generation from lecture content
- Quiz insights (strong/weak areas, recommendations)
- Class performance summaries

**Data Flow:**
1. Teacher requests quiz generation → Content sent to AI
2. AI generates questions → Returns JSON array of questions
3. Questions parsed → Created as `quiz_questions` records
4. Student completes quiz → Answers sent to AI for analysis
5. AI returns insights → Stored in `quiz_attempts.ai_*` fields
6. Class summary requested → Aggregated data sent to AI
7. AI returns summary → Stored in `classes.ai_summary`

**AI Features:**
- Configurable prompts via `ai_settings` table
- Model selection (Llama 3.1-8B-instruct)
- Template-based prompt rendering
- Error handling and logging
- Class-specific AI settings override

### 12. Email System
**Files:** `GmailService.php`, `GmailSender.php`, `ResetPasswordNotification.php`

**Description:** Custom Gmail API integration for sending emails (password resets, notifications). Uses app-specific password for authentication.

**Data Flow:**
1. Email triggered → `GmailService` called
2. Message constructed → Using Gmail API
3. Sent via Gmail → Using credentials from .env
4. Status logged → Success/failure tracking

**Email Features:**
- Password reset emails
- Custom notification support
- Gmail API integration
- App password authentication

---

## 6. UI & Design

### Overall UI Structure
The application uses a **dashboard-based layout** with:
- **Sidebar navigation** (role-specific)
- **Top header** with user info and actions
- **Main content area** with cards and tables
- **Responsive design** for different screen sizes

### Design System
- **Theme:** Argon Dashboard (Bootstrap 4 based)
- **CSS Framework:** Bootstrap 4 + Custom SASS
- **Build Tool:** Vite + TailwindCSS
- **Icons:** Font Awesome (via CDN)
- **Color Palette:** Earth tones (browns, beiges, oranges) - Reviso branding
- **Typography:** System fonts with custom weights

### Layout Files
Each role/program has a dedicated layout:
- **`appTeach.blade.php`** - Teacher dashboard with sidebar
- **`appAdmin.blade.php`** - Admin dashboard
- **`appPsych.blade.php`** - Psychology program students
- **`appEduc.blade.php`** - Education program students
- **`appAcc.blade.php`** - Accountancy program students
- **`quiz.blade.php`** - Quiz-taking layout (minimal, focused)

### Reusable Components
**Navigation Bars:**
- `navbarTeacher.blade.php` - Teacher navigation (Dashboard, Classes, Students, Mock Boards, Chat)
- `navbarPsych.blade.php` - Psychology program navigation
- `navbarEducation.blade.php` - Education program navigation
- `navbarAccountancy.blade.php` - Accountancy program navigation
- `navbarAdmin.blade.php` - Admin navigation

**Styling Conventions:**
- `.rv-btn` - Custom button styles (primary, secondary, danger)
- `.rv-nav-item` - Navigation items with active states
- `.rv-card` - Card containers
- `.rv-table` - Styled tables
- Color-coded badges for status (pass/fail, active/inactive)

### View Organization
```
resources/views/
├── layouts/           # Master layouts
├── pages/
│   ├── admin/        # Admin pages
│   ├── student/      # Student pages (modules, progress, assessments)
│   ├── teacher/      # Teacher pages (class management, analytics)
│   ├── accountancy/  # Accountancy-specific pages
│   ├── educ/         # Education-specific pages
│   └── psych/        # Psychology-specific pages
├── reusable/         # Shared components (navbars)
├── auth/             # Login/signup pages
└── emails/           # Email templates
```

### Anti-Cheat UI
- Warning modal for tab switching
- Fullscreen enforcement for formal assessments
- Timer display
- Auto-submit notification

### Chat UI
- Real-time message display
- Online status indicators
- Message bubbles (sent/received)
- Timestamp formatting

---

## 7. Authentication & Authorization

### Authentication Implementation
- **Driver:** Laravel's built-in session-based authentication
- **Login Field:** Custom `idnumber` field instead of email
- **Password Hashing:** Laravel's bcrypt
- **Session Storage:** Database (`sessions` table)
- **Remember Me:** Laravel's remember token

### User Roles
- **student** - Can view enrolled classes, take assessments, view progress
- **teacher** - Can create/manage classes, upload content, create quizzes, view analytics
- **admin** - Can approve/reject user registrations, manage system settings
- **superadmin** - Full system access including global AI settings
- **Legacy roles** (now stored in `program` field): `psych`, `educ`, `accountancy`

### Authorization Gates (AuthServiceProvider.php)
```php
Gate::define('manage-global-ai-settings') - superadmin only
Gate::define('manage-class-ai-settings') - admin, superadmin
Gate::define('manage-mock-board') - mock board creator, class creator, admin, superadmin
Gate::define('view-mock-board') - admin, superadmin, teacher (all), students (enrolled + visible)
Gate::define('view-batch-analytics') - admin, superadmin, specific programs (psychology, education, accountancy)
```

### Middleware
- **`auth`** - Requires authenticated user
- **`guest`** - Requires unauthenticated user
- **`UpdateLastSeen`** - Updates `last_seen_at` timestamp (cached to once per minute)
- **`can`** - Gate-based authorization
- **Spatie Permission middleware** - `role`, `permission`, `role_or_permission`

### Access Control by Role
- **Students:** Can only access enrolled classes, visible modules, their own progress
- **Teachers:** Can only manage classes they created, view their students' data
- **Admins:** Can approve users, view all data, manage class AI settings
- **Superadmins:** Full access including global AI configuration

### Program-Based Access
Students are assigned to programs (`psychology`, `education`, `accountancy`). Some features (like batch analytics) are filtered by program to show relevant data.

---

## 8. Third-Party Integrations

### Cloudflare Workers AI
**Purpose:** AI-powered quiz generation and insights  
**Files:** `CloudflareAI.php`, `AiSettingsResolver.php`, `config/services.php`  
**Configuration:**
- `CLOUDFLARE_ACCOUNT_ID` - Account ID from Cloudflare dashboard
- `CLOUDFLARE_API_TOKEN` - API token with Workers AI permissions
- `CLOUDFLARE_AI_GATEWAY` - Optional gateway URL

**Usage:**
- Quiz generation: `CloudflareAI::run('@cf/meta/llama-3.1-8b-instruct', $payload)`
- Class summaries: `CloudflareAI::generateSummary($stats)`
- Prompts stored in `ai_settings` table for easy customization

**Model:** Llama 3.1-8B-instruct

### Gmail API
**Purpose:** Email sending (password resets, notifications)  
**Files:** `GmailService.php`, `GmailSender.php`  
**Configuration:**
- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.gmail.com`
- `MAIL_PORT=587`
- `MAIL_USERNAME` - Gmail address
- `MAIL_PASSWORD` - App-specific password
- `MAIL_ENCRYPTION=tls`

**Usage:**
- Password reset emails via custom notification
- Custom notification support via `GmailService`

### Google Client Library
**Purpose:** Gmail API integration  
**Package:** `google/apiclient:2.19`  
**Files:** `routes/web.php` (Google_Client instantiation)

### Firebase JWT
**Purpose:** JWT token handling (if needed for API)  
**Package:** `firebase/php-jwt:^7.0`

### Spatie Laravel Permission
**Purpose:** Role/permission management (installed but gates primarily used)  
**Package:** `spatie/laravel-permission:^7.2`  
**Middleware:** `role`, `permission`, `role_or_permission`

### Livewire
**Purpose:** Reactive components (v4)  
**Package:** `livewire/livewire:^4.1`  
**Usage:** For dynamic UI components without full page reloads

---

## 9. Known Patterns & Conventions

### Naming Conventions
- **Files:** PascalCase (e.g., `ClassManagerController.php`)
- **Classes:** PascalCase (e.g., `MockBoardStatisticsService`)
- **Methods:** camelCase (e.g., `computeClassStatistics`)
- **Variables:** camelCase (e.g., `$mockBoard`)
- **Database tables:** snake_case (e.g., `mock_board_phases`)
- **Foreign keys:** snake_case with `_id` suffix (e.g., `class_id`, `user_id`)
- **Routes:** kebab-case (e.g., `mock-boards.batch.dashboard`)
- **Blade views:** kebab-case (e.g., `batch-dashboard.blade.php`)

### Model Conventions
- **Fillable arrays:** Explicitly define mass-assignable fields
- **Casts:** Use `$casts` property for type conversion (boolean, datetime, json, array)
- **Relationships:** Use return type hints (BelongsTo, HasMany, BelongsToMany)
- **Scopes:** Use `scope{MethodName}()` convention (e.g., `scopeOngoing`)
- **Accessors:** Use `get{AttributeName}Attribute()` convention

### Controller Conventions
- **Resource controllers:** Standard CRUD methods (index, store, show, update, destroy)
- **Form requests:** Use separate Form Request classes for validation (not consistently applied)
- **Authorization:** Use Gates via `Gate::allows()` or `can()` middleware
- **Redirects:** Use `redirect()->route()` for named routes
- **JSON responses:** Use `response()->json()` for API endpoints

### Route Conventions
- **Named routes:** Always use `->name('route.name')` for easy reference
- **Route groups:** Group related routes with common middleware/prefixes
- **Resource routes:** Use `Route::resource()` for standard CRUD
- **Route ordering:** Specific routes before parameterized routes to avoid conflicts (e.g., `batch-analytics` before `{mock_board}`)

### View Conventions
- **Layouts:** Use `@extends('layouts.name')` and `@section` directives
- **Components:** Use `@include('reusable.component')` for shared UI
- **Conditionals:** Use Blade directives (`@if`, `@foreach`, `@forelse`)
- **Escaping:** Always use `{{ }}` for escaped output, `{!! !!}` for raw HTML
- **CSS:** Use scoped styles in `@section('head')` for page-specific CSS

### Service Layer Pattern
Business logic extracted to service classes:
- **`CloudflareAI`** - AI API interactions
- **`GmailService`** - Email sending
- **`AiSettingsResolver`** - AI prompt resolution
- **`MockBoardStatisticsService`** - Statistical calculations

### Database Conventions
- **Migrations:** Use descriptive names with timestamps
- **Foreign keys:** Always use `->constrained()` for referential integrity
- **Indexes:** Add indexes for frequently queried columns
- **Soft deletes:** Not used (hard deletes)
- **Timestamps:** Always include `created_at`, `updated_at`

### Anti-Cheat Pattern
- **Formal assessments:** Set `is_formal_assessment = true` on module
- **JavaScript check:** Check `isFormalAssessment` variable before enabling anti-cheat
- **Pre-assessments:** Set `is_formal_assessment = false` to skip anti-cheat
- **Debounce:** Use debounce on event handlers to prevent false positives

### Program-Based Routing
Students are routed to program-specific dashboards:
- Psychology → `/psych-dashboard`
- Education → `/educ-dashboard`
- Accountancy → `/accountancy-dashboard`

This is handled by the `$resolveStudentDashboardPath` closure in `web.php`.

### Custom Login Field
The system uses `idnumber` instead of `email` for login:
- **Model:** `username()` method returns `'idnumber'`
- **Validation:** Custom validation on `idnumber` field
- **Uniqueness:** `idnumber` has unique constraint in database

### AI Prompt Template Pattern
AI prompts use template variables:
```php
{num_questions} - Number of questions to generate
{difficulty} - Question difficulty level
{module_title} - Module title
{module_description} - Module description
{combined_text} - Combined lecture content
{score} - Student's quiz score
{class_average} - Class average score
```

Templates are stored in `ai_settings` table and rendered by `AiSettingsResolver`.

### Unconventional/Project-Specific Notes
- **Multiple layouts per role:** Instead of a single admin layout, each role/program has its own layout file
- **Legacy role handling:** Old roles (`psych`, `educ`, `accountancy`) now stored in `program` field but still referenced in some places
- **Custom online status:** Uses `last_seen_at` with 3-minute window instead of standard online status
- **Mock board phases:** Two-phase structure (pre-test, pre-boards) with separate modules for each
- **ANOVA in PHP:** Statistical calculations implemented in PHP rather than using a statistical library
- **Visibility controls:** Three-tier visibility system (all, selected, except) for modules and mock boards
- **Program-based batch analytics:** Aggregates data by program across multiple classes

---

## 10. Complete Route List

### Public Routes (No Authentication)
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/` | Closure | `home` | - |
| GET | `/login` | Closure | `login` | `guest` |
| POST | `/login` | Closure | `login.post` | `guest`, `throttle:10,1` |
| POST | `/logout` | Closure | `logout` | `auth` |
| GET | `/signup` | Closure | `signup` | `guest` |
| POST | `/signup` | Closure | `signup.post` | - |
| GET | `/pending-approval` | Closure | `login.pending` | - |
| GET | `/account-rejected` | Closure | `login.rejected` | - |
| GET | `/forgot-password` | Closure | `password.request` | `guest` |
| POST | `/forgot-password` | Closure | `password.email` | `guest` |
| GET | `/reset-password/{token}` | Closure | `password.reset` | `guest` |
| POST | `/reset-password` | Closure | `password.update` | `guest` |
| GET | `/email/verify/{token}` | Closure | `verification.verify` | - |
| POST | `/email/resend` | Closure | `verification.resend` | `guest` |
| GET | `/authorize-gmail` | Closure | `gmail.authorize` | - |
| GET | `/oauth2callback` | Closure | `oauth2callback` | - |
| GET | `/classes/{class}/join` | Closure | `class.join` | `signed` |
| GET | `/join/{class}` | Closure | `class.join.permanent` | `signed` |
| POST | `/join/{class}/confirm` | Closure | `class.join.confirm` | `signed` |

### Authenticated Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/dashboard` | Closure | `dashboard` | `auth` |
| GET | `/psych-dashboard` | StudentDashboardController@index | `psychDashboard` | `auth` |
| GET | `/educ-dashboard` | StudentDashboardController@index | `educDashboard` | `auth` |
| GET | `/teacher-dashboard` | TeacherDashboardController@index | `teacherDashboard` | `auth` |
| GET | `/admin-dashboard` | AdminDashboardController@index | `adminDashboard` | `auth` |
| GET | `/accountancy-dashboard` | StudentDashboardController@index | `accountancyDashboard` | `auth` |
| GET | `/assessment` | StudentAssessmentController@index | `assessment` | `auth` |
| GET | `/assessment/{module}` | StudentAssessmentController@take | `assessment.take` | `auth` |
| GET | `/progress` | StudentProgressController@index | `progress` | `auth` |
| GET | `/tables` | Closure | `tables` | `auth` |
| GET | `/register` | Closure | `register` | `auth` |
| GET | `/assessmentexams` | Closure | `assessmentexams` | `auth` |
| GET | `/profile` | ProfileController@show | `profile` | `auth` |
| POST | `/profile/password` | ProfileController@updatePassword | `profile.password.update` | `auth` |
| POST | `/profile/email` | ProfileController@updateEmail | `profile.email.update` | `auth` |
| POST | `/profile/email/new` | ProfileController@requestNewEmailVerification | `profile.email.new` | `auth` |
| POST | `/profile/email/verify` | ProfileController@verifyEmailChange | `profile.email.verify` | `auth` |
| POST | `/profile/program` | ProfileController@updateProgram | `profile.program.update` | `auth` |

### Admin Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/admin/lectures` | AdminUserController@index | `admin.lectures` | `auth` |
| GET | `/admin/users` | AdminUserController@index | `admin.users` | `auth` |
| GET | `/admin/users/export` | AdminUserController@exportUsersCsv | `admin.users.export` | `auth` |
| POST | `/admin/users/{id}/reset-password` | AdminUserController@resetPassword | `admin.users.reset` | `auth` |
| POST | `/admin/users/{id}/toggle-status` | AdminUserController@toggleStatus | `admin.users.toggle-status` | `auth` |
| POST | `/admin/users/{user}/unlock-program` | ProfileController@unlockProgram | `admin.unlock.program` | `auth` |
| GET | `/superadmin/admins` | Closure | `superadmin.admins` | `auth` |
| POST | `/superadmin/admins/{id}/toggle-status` | AdminUserController@toggleStatus | `superadmin.admins.toggle` | `auth` |
| POST | `/superadmin/approvals/{user}/override` | Closure | `superadmin.approvals.override` | `auth` |
| GET | `/admin/approvals` | AdminApprovalController@index | `admin.approvals` | `auth` |
| POST | `/admin/approvals/{user}/approve` | AdminApprovalController@approve | `admin.approvals.approve` | `auth` |
| POST | `/admin/approvals/approve-many` | AdminApprovalController@approveMany | `admin.approvals.approve-many` | `auth` |
| POST | `/admin/approvals/approve-all` | AdminApprovalController@approveAll | `admin.approvals.approve-all` | `auth` |
| POST | `/admin/approvals/{user}/reject` | AdminApprovalController@reject | `admin.approvals.reject` | `auth` |

### Class Management Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/manageclass` | ClassManagerController@index | `manageclass` | `auth` |
| POST | `/classes` | ClassManagerController@store | `classes.store` | `auth` |
| DELETE | `/classes/{class}` | ClassManagerController@destroy | `classes.destroy` | `auth` |
| GET | `/users/search-students` | ClassManagerController@searchStudents | `students.search` | `auth` |
| POST | `/classes/{class}/students` | ClassManagerController@addStudents | `classes.students.add` | `auth` |
| DELETE | `/classes/{class}/students/{student}` | ClassManagerController@removeStudent | `classes.students.remove` | `auth` |
| GET | `/classes/{class}/students` | ClassManagerController@getClassStudents | `classes.students.get` | `auth` |
| GET | `/classes/{class}/students/search` | ClassManagerController@searchClassStudents | `classes.students.search` | `auth` |
| POST | `/classes/{class}/invite` | ClassManagerController@generateInvite | `classes.invite` | `auth` |
| POST | `/classes/{class}/join-link` | ClassManagerController@generate24HourJoinLink | `classes.join-link` | `auth` |
| POST | `/classes/{class}/modules` | ClassManagerController@storeModule | `classes.modules.store` | `auth` |
| GET | `/classes/{class}/modules/list` | ClassManagerController@listModulesJson | `classes.modules.list` | `auth` |
| GET | `/classes/{class}/modules` | ClassManagerController@listModules | `classes.modules.index` | `auth` |
| GET | `/classes/{class}/modules/show` | ClassManagerController@showModules | `manage.modules` | `auth` |
| GET | `/my-classes` | ClassManagerController@myClasses | `student.classes` | `auth` |
| GET | `/my-classes/{class}/modules` | ClassManagerController@studentModules | `student.modules` | `auth` |

### Module Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/modules/{module}/view` | ClassManagerController@viewModuleFile | `module.view` | `auth` |
| GET | `/modules/{module}/pdfjs` | ClassManagerController@pdfjsViewer | `module.pdfjs` | `auth` |
| GET | `/modules/{module}/public-view` | ClassManagerController@publicModuleFile | `module.view.public` | `auth`, `signed` |
| GET | `/modules/{module}/view-pdf` | ClassManagerController@viewPdf | `module.view.pdf` | `auth` |
| DELETE | `/modules/{module}` | ClassManagerController@deleteModule | `modules.delete` | `auth` |
| POST | `/modules/{module}/progress` | ClassManagerController@updateProgress | `modules.progress.update` | `auth` |

### Quiz Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/modules/{module}/quiz/create` | ClassManagerController@createQuiz | `quiz.create` | `auth` |
| POST | `/modules/{module}/quiz/generate` | ClassManagerController@generateQuizAi | `quiz.generate` | `auth` |
| POST | `/modules/{module}/quiz/store` | ClassManagerController@storeQuizManual | `quiz.store` | `auth` |
| GET | `/modules/{module}/quiz/questions` | QuizController@getQuestions | `quiz.get.questions` | `auth` |
| POST | `/modules/{module}/quiz/submit` | QuizController@submitQuiz | `quiz.submit` | `auth` |
| POST | `/modules/{module}/quiz/insights` | QuizController@generateInsights | `quiz.insights` | `auth` |
| POST | `/quiz/{module}/answer` | QuizController@submitAnswer | `quiz.answer` | `auth` |
| POST | `/quiz/create-draft/{class}` | QuizController@createQuizDraft | `quiz.create.draft` | `auth` |
| DELETE | `/modules/{module}/quiz/attempts` | QuizController@resetAttempts | `quiz.attempts.reset` | `auth` |
| DELETE | `/modules/{module}/quiz/my-attempt` | QuizController@resetMyAttempt | `quiz.my.attempt.reset` | `auth` |

### Lecture Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/lectures` | LectureController@index | `lectures` | `auth` |
| GET | `/teacher/create` | LectureController@create | `teacher.create` | `auth` |
| POST | `/teacher` | LectureController@store | `teacher.store` | `auth` |
| GET | `/teacher/{id}/edit` | LectureController@edit | `teacher.edit` | `auth` |
| PUT | `/teacher/{id}` | LectureController@update | `teacher.update` | `auth` |
| DELETE | `/teacher/{id}` | LectureController@destroy | `teacher.destroy` | `auth` |
| GET | `/teacher/lectures/{id}/file` | LectureController@download | `teacher.lecture.file` | `auth` |

### Performance Analytics Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/student-performance/{class}` | PerformanceController@studentPerformance | `student.performance` | `auth` |
| GET | `/student-performance/{class}/progress` | PerformanceController@classProgressTracker | `student.progress.tracker` | `auth` |
| GET | `/student-performance/{class}/students/{student}` | PerformanceController@studentItemAnalysis | `student.performance.student-item-analysis` | `auth` |
| POST | `/student-performance/{class}/refresh-ai` | PerformanceController@refreshAiSummary | `student.performance.refresh` | `auth` |
| POST | `/student-performance/{class}/assessment-refresh-ai` | PerformanceController@refreshAssessmentAiSummary | `student.performance.assessment.refresh` | `auth` |
| GET | `/student-performance/{class}/assessment-analysis/{student}` | PerformanceController@studentAssessmentAnalysis | `student.assessment.analysis` | `auth` |
| POST | `/student-performance/{class}/assessment-analysis/{student}/generate-ai` | PerformanceController@generateAssessmentAiAnalysis | `student.assessment.analysis.generate-ai` | `auth` |

### Chat Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/chat` | ChatController@index | `chat.index` | `auth` |
| GET | `/chat/conversations` | ChatController@conversations | `chat.conversations` | `auth` |
| GET | `/chat/conversations/{chat}/messages` | ChatController@messages | `chat.messages` | `auth` |
| POST | `/chat/conversations` | ChatController@start | `chat.start` | `auth` |
| POST | `/chat/conversations/{chat}/messages` | ChatController@sendMessage | `chat.send_message` | `auth` |
| POST | `/chat/conversations/{chat}/remove` | ChatController@remove | `chat.conversations.remove` | `auth` |

### Announcement Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/announcements` | AnnouncementController@index | `announcements.index` | `auth` |
| POST | `/classes/{class}/announcements` | AnnouncementController@store | `announcements.store` | `auth` |
| PATCH | `/announcements/{announcement}` | AnnouncementController@update | `announcements.update` | `auth` |
| DELETE | `/announcements/{announcement}` | AnnouncementController@destroy | `announcements.destroy` | `auth` |
| POST | `/announcements/mark-read` | AnnouncementController@markRead | `announcements.mark-read` | `auth` |
| GET | `/classes/{class}/announcements/feed` | AnnouncementController@classFeed | `announcements.class-feed` | `auth` |

### AI Settings Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/admin/ai-settings/classes` | AiSettingsController@classes | `admin.class-ai-settings` | `auth` |
| POST | `/admin/ai-settings/classes/{class}` | AiSettingsController@updateClass | `admin.class-ai-settings.update` | `auth` |
| GET | `/superadmin/ai-settings` | AiSettingsController@global | `superadmin.ai-settings` | `auth` |
| POST | `/superadmin/ai-settings` | AiSettingsController@updateGlobal | `superadmin.ai-settings.update` | `auth` |

### Mock Board Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/mock-boards/batch-analytics` | BatchAnalyticsController@dashboard | `mock-boards.batch.dashboard` | `auth` |
| GET | `/mock-boards/batch-analytics/{program}/{mock_board}` | BatchAnalyticsController@mockBoardsAnalysis | `mock-boards.batch.analysis` | `auth` |
| POST | `/mock-boards/batch-analytics/{program}/{mock_board}/compute-anova` | BatchAnalyticsController@computeANOVA | `mock-boards.batch.anova.compute` | `auth` |
| GET | `/student/mock-boards` | StudentMockBoardController@index | `student.mock-boards.index` | `auth` |
| GET | `/student/mock-boards/{mock_board}` | StudentMockBoardController@show | `student.mock-boards.show` | `auth` |
| GET | `/student/mock-boards/{mock_board}/{phase}/take` | StudentMockBoardController@take | `student.mock-boards.take` | `auth` |
| POST | `/student/mock-boards/{mock_board}/{phase}/submit` | StudentMockBoardController@store | `student.mock-boards.store` | `auth` |
| GET | `/student/mock-boards/{mock_board}/results` | StudentMockBoardController@results | `student.mock-boards.results` | `auth` |
| GET | `/classes/{class}/mock-boards` | MockBoardController@index | `mock-boards.index` | `auth` |
| POST | `/classes/{class}/mock-boards` | MockBoardController@store | `mock-boards.store` | `auth` |
| PUT | `/mock-boards/{mock_board}` | MockBoardController@update | `mock-boards.update` | `auth` |
| DELETE | `/mock-boards/{mock_board}` | MockBoardController@destroy | `mock-boards.destroy` | `auth` |
| POST | `/mock-boards/{mock_board}/phases` | MockBoardController@updatePhases | `mock-boards.phases.update` | `auth` |
| POST | `/mock-boards/{mock_board}/{phase}/questions/generate` | MockBoardController@generateQuestions | `mock-boards.questions.generate` | `auth` |
| POST | `/mock-boards/{mock_board}/{phase}/questions/approve` | MockBoardController@approveGeneratedQuestions | `mock-boards.questions.approve` | `auth` |
| GET | `/mock-boards/{mock_board}/analysis` | MockBoardController@classAnalysis | `mock-boards.analysis` | `auth` |
| POST | `/mock-boards/{mock_board}/compute-anova` | MockBoardController@computeANOVA | `mock-boards.anova.compute` | `auth` |
| GET | `/mock-boards/{mock_board}/student/{student}` | MockBoardController@studentAnalysis | `mock-boards.student-analysis` | `auth` |

### Misc Routes
| Method | URI | Controller/Method | Route Name | Middleware |
|--------|-----|-------------------|------------|------------|
| GET | `/users/search` | ClassManagerController@searchUsers | `users.search.api` | `auth` |
| GET | `/users/search-page` | ClassManagerController@searchPage | `users.search` | `auth` |
| GET | `/teacher-dashboard-teachh` | Closure | `teacherDashboardTeachh` | `auth` |
| GET | `/test-ai-laravel` | TestAiController@test | `test.ai.laravel` | `auth` |

---

## 11. Environment Variables (.env Keys)

### Application Configuration
- `APP_NAME` - Application name (default: Laravel)
- `APP_ENV` - Environment (local, production)
- `APP_KEY` - Application encryption key
- `APP_DEBUG` - Debug mode (true/false)
- `APP_URL` - Application base URL
- `APP_TIMEZONE` - Default timezone (Asia/Manila)
- `APP_LOCALE` - Default locale (en)
- `APP_FALLBACK_LOCALE` - Fallback locale
- `APP_FAKER_LOCALE` - Faker locale for seeders

### Maintenance
- `APP_MAINTENANCE_DRIVER` - Maintenance mode driver (file)
- `APP_MAINTENANCE_STORE` - Maintenance mode store (optional, database)

### Security
- `BCRYPT_ROUNDS` - Bcrypt hashing rounds (12)
- `PHP_CLI_SERVER_WORKERS` - PHP server workers (optional)

### Logging
- `LOG_CHANNEL` - Log channel (stack)
- `LOG_STACK` - Log stack (single)
- `LOG_DEPRECATIONS_CHANNEL` - Deprecations log channel (null)
- `LOG_LEVEL` - Log level (debug)

### Database
- `DB_CONNECTION` - Database connection (mysql)
- `DB_HOST` - Database host (127.0.0.1)
- `DB_PORT` - Database port (3306)
- `DB_DATABASE` - Database name (reviso)
- `DB_USERNAME` - Database username (root)
- `DB_PASSWORD` - Database password

### Session
- `SESSION_DRIVER` - Session driver (database)
- `SESSION_LIFETIME` - Session lifetime in minutes (120)
- `SESSION_ENCRYPT` - Encrypt session data (false)
- `SESSION_PATH` - Session path (/)
- `SESSION_DOMAIN` - Session domain (null)

### Queue & Cache
- `BROADCAST_CONNECTION` - Broadcast driver (log)
- `FILESYSTEM_DISK` - Default filesystem disk (local)
- `QUEUE_CONNECTION` - Queue driver (database)
- `CACHE_STORE` - Cache driver (file)
- `CACHE_PREFIX` - Cache prefix (optional)

### Memcached
- `MEMCACHED_HOST` - Memcached host (127.0.0.1)

### Redis
- `REDIS_CLIENT` - Redis client (phpredis)
- `REDIS_HOST` - Redis host (127.0.0.1)
- `REDIS_PASSWORD` - Redis password (null)
- `REDIS_PORT` - Redis port (6379)

### Mail (Default - Log)
- `MAIL_MAILER` - Mail driver (log)
- `MAIL_SCHEME` - Mail scheme (null)
- `MAIL_HOST` - Mail host (127.0.0.1)
- `MAIL_PORT` - Mail port (2525)
- `MAIL_USERNAME` - Mail username (null)
- `MAIL_PASSWORD` - Mail password (null)
- `MAIL_FROM_ADDRESS` - From email (hello@example.com)
- `MAIL_FROM_NAME` - From name (${APP_NAME})

### AWS S3 (Optional)
- `AWS_ACCESS_KEY_ID` - AWS access key
- `AWS_SECRET_ACCESS_KEY` - AWS secret key
- `AWS_DEFAULT_REGION` - AWS region (us-east-1)
- `AWS_BUCKET` - S3 bucket name
- `AWS_USE_PATH_STYLE_ENDPOINT` - Use path style endpoint (false)

### Vite
- `VITE_APP_NAME` - Vite app name (${APP_NAME})

### Cloudflare AI
- `CLOUDFLARE_ACCOUNT_ID` - Cloudflare account ID
- `CLOUDFLARE_API_TOKEN` - Cloudflare API token
- `CLOUDFLARE_AI_GATEWAY` - Cloudflare AI gateway (optional)

### Mail (Gmail SMTP Override)
- `MAIL_MAILER` - Mail driver (smtp) - overrides default
- `MAIL_HOST` - SMTP host (smtp.gmail.com) - overrides default
- `MAIL_PORT` - SMTP port (587) - overrides default
- `MAIL_USERNAME` - Gmail username (revisoassist@gmail.com)
- `MAIL_PASSWORD` - Gmail app password
- `MAIL_ENCRYPTION` - Encryption (tls)
- `MAIL_FROM_ADDRESS` - From address (revisoassist@gmail.com) - overrides default
- `MAIL_FROM_NAME` - From name (Reviso) - overrides default

---

## 12. Incomplete Features & Known Issues

### TODO Items
1. **Restore `can:view-batch-analytics` middleware** - Currently commented out for testing (line 696 in web.php). Authorization gate needs to be fixed before re-enabling.
2. **AI Question Generation** - Placeholder implementation in `MockBoardController@generateQuestions`. Needs integration with actual CloudflareAI service.
3. **Fullscreen Enforcement** - Not implemented for formal assessments (mentioned in docs as optional).
4. **Group Chat Support** - Chat system designed for group chats but only direct 1-on-1 is currently implemented.
5. **Email Verification Flow** - Signup verification flow exists but may need refinement for production use.

### Known Issues
1. **Mock Board Authorization Gate Bug** - `manage-mock-board` gate checks `$mockBoard->teacher_id` but MockBoard model uses `created_by` field. Fixed in AuthServiceProvider but may need verification.
2. **Legacy Role Handling** - Old roles (`psych`, `educ`, `accountancy`) still referenced in some places despite migration to `program` field.
3. **Route Order Sensitivity** - Mock board batch analytics routes must be placed before parameterized routes to avoid 404 errors.
4. **No Livewire Usage** - Livewire v4 is installed but not actually used anywhere in the codebase. All interactivity is via vanilla JavaScript.
5. **Missing JavaScript Directory** - `resources/js/` directory does not exist. All JavaScript is embedded in Blade views via `<script>` tags.

### Incomplete Features
1. **Item Analysis UI** - `MockBoardStatisticsService@getItemAnalysis` exists but no UI to display per-question breakdown (which got created then deleted).
2. **Student Mock Board Dashboard** - Student-facing mock board dashboard needs full implementation.
3. **Assessment AI Analysis** - AI analysis for individual student assessments exists but UI may need refinement.
4. **Program Locking** - `program_locked` field exists on users but enforcement logic not fully implemented.
5. **Rejection Reason Display** - `rejection_reason` field exists but UI for displaying rejection reasons to users may be missing.

### Performance Considerations
1. **N+1 Query Risk** - Some controller methods may have N+1 query issues (e.g., loading students without eager loading).
2. **Large File Uploads** - No explicit file size limits configured for lecture uploads.
3. **Cache Usage** - Only basic file cache configured; could benefit from Redis for production.

---

## 13. Seeder Data

### DemoUsersSeeder
Creates test users for development:

**Teachers (3 users):**
- ID: `TCH001`, Name: Prof. Maria Smith, Email: teacher1@school.edu
- ID: `TCH002`, Name: Prof. John Johnson, Email: teacher2@school.edu
- ID: `TCH003`, Name: Prof. Sarah Williams, Email: teacher3@school.edu
- Role: `teacher`, Program: null
- Password: `2004-02-07` (hashed)

**Students (7 users):**
- ID: `STU001` to `STU007`
- Names: Alice Anderson, Bob Brown, Charlie Chen, Diana Davis, Eve Edwards, Frank Foster, Grace Garcia
- Emails: student1@school.edu to student7@school.edu
- Roles: Randomly assigned `psych`, `educ`, or `accountancy` (legacy roles)
- Programs: Corresponding `psychology`, `education`, or `accountancy`
- Password: `2004-02-07` (hashed)

### SignupsTableSeeder
Legacy signup data (migrated from old system):
- 10 users with IDs `23-0811` to `23-0810`
- Mixed roles (student, teacher)
- All verified with `verified_at` timestamps
- Password: `2004-02-07` (hashed)

### AdminSeeder
Creates admin users (not fully documented in code, likely creates superadmin accounts).

### Running Seeders
```bash
php artisan db:seed
# Or specific seeder:
php artisan db:seed --class=DemoUsersSeeder
```

---

## 14. JavaScript Coverage

### JavaScript Files
**Note:** The `resources/js/` directory does not exist. All JavaScript is embedded directly in Blade views.

### Embedded JavaScript by View

**Anti-Cheat System (`assessment-take.blade.php`, `assessmentexams.blade.php`):**
- Tab switching detection using `visibilitychange` and `blur` events
- Warning counter (max 3 violations)
- Auto-submit on 3rd violation
- Fullscreen enforcement (optional, not fully implemented)
- LocalStorage for exam state (`exam_started`, `exam_warn_count`)

**Quiz Timer (`modules.blade.php`):**
- Countdown timer for timed quizzes
- Auto-submit when timer expires
- Timer display in MM:SS format

**Module Progress Tracking (`modules.blade.php`):**
- Scroll position tracking for PDF/DOCX viewers
- Progress percentage calculation
- AJAX progress updates to server
- Module locking based on completion

**PDF.js Viewer (`pdfjs-viewer.blade.php`):**
- PDF.js library integration (CDN)
- Page navigation
- Zoom controls
- Loading status display

**DOCX Viewer (`docx-viewer.blade.php`):**
- Mammoth.js library for DOCX conversion
- JSZip for file handling
- HTML rendering of DOCX content

**Office Viewer (`office-viewer.blade.php`):**
- iframe-based Office Online viewer
- Time tracking for reading duration

**Chat System (`chat.blade.php`):**
- Real-time message polling (not WebSocket)
- Online status indicators
- Message bubble rendering
- Auto-scroll to latest message

**Charts (`student-performance.blade.php`):**
- Chart.js integration for pass/fail charts
- Performance trend visualization
- Dynamic data loading

**Modals & Toasts (`manageclass.blade.php`, `quiz-create.blade.php`, `modules-list.blade.php`):**
- Modal open/close logic
- Toast notification system
- Confirmation dialogs
- Form validation feedback

**Program Tab Switching (`batch-dashboard.blade.php`):**
- Tab switching for program selection
- Dynamic content loading
- Active state management

**TinyMCE Integration (`quiz-create.blade.php`):**
- Rich text editor for question content
- Dynamic initialization
- Content sanitization

### External JavaScript Libraries (CDN)
- jQuery
- Bootstrap 4 JS
- Chart.js
- PDF.js
- Mammoth.js
- JSZip
- Select2
- TrackJS (error tracking)
- Google Maps API

---

## 15. Livewire Components

**Status:** Livewire v4 is installed (`composer.json`) but **not used** anywhere in the codebase.

**Search Results:** No `@livewire` directives found in any Blade files.

**Recommendation:** Consider either:
1. Removing Livewire dependency if not needed, or
2. Implementing Livewire components for reactive features (chat, real-time updates) to reduce custom JavaScript.

---

## 16. File Upload Paths

### Storage Configuration (`config/filesystems.php`)

**Local Disk (Private):**
- Path: `storage/app/private`
- Used for: Sensitive files, not publicly accessible
- Serve: true (via authenticated routes)

**Public Disk:**
- Path: `storage/app/public`
- URL: `/storage` (symlinked from `public/storage`)
- Used for: Publicly accessible files
- Visibility: public

**S3 Disk (Optional):**
- Configured but not actively used
- For cloud file storage

### Actual Upload Paths

**Lecture Files:**
- Path: `storage/app/lectures/`
- Stored via: `ClassManagerController@storeModule`
- File types: PDF, DOCX, PPTX
- Access: Via authenticated routes (`/modules/{module}/view`, `/modules/{module}/pdfjs`)

**Gmail OAuth Tokens:**
- Path: `storage/app/google/tokens.json`
- Stored via: OAuth callback handler
- Used for: Gmail API authentication

**Gmail Credentials:**
- Path: `storage/app/google/credentials.json`
- Required for: Gmail OAuth flow
- Must be manually configured

### File Serving Strategy

**Private Files (Lectures):**
- Stored in `storage/app/lectures/`
- Served via authenticated controller methods
- Routes: `/modules/{module}/view`, `/modules/{module}/pdfjs`
- Middleware: `auth` required
- No direct public access

**Public Files:**
- Stored in `storage/app/public/`
- Symlinked to `public/storage/`
- Direct URL access: `http://localhost/storage/filename`
- Currently not actively used for uploads

### File Upload Validation
- File size limits: Not explicitly configured (uses PHP defaults)
- Allowed types: PDF, DOCX, PPTX (validated in controller)
- Storage limits: None (uses disk space)

### Recommended Improvements
1. Add explicit file size limits in validation
2. Consider using S3 for production file storage
3. Implement file cleanup for deleted modules
4. Add virus scanning for uploaded files
5. Use unique filenames to prevent conflicts

---

## Appendix: Quick Reference

### Common Commands
```bash
php artisan serve              # Start development server
php artisan migrate            # Run migrations
php artisan migrate:rollback   # Rollback last migration
php artisan route:list         # List all routes
php artisan tinker              # Interactive PHP shell
php artisan cache:clear        # Clear application cache
php artisan config:clear       # Clear config cache
php artisan route:clear        # Clear route cache
php artisan view:clear         # Clear compiled views
composer run dev               # Full dev stack (server, queue, logs, vite)
npm run dev                    # Vite dev server
npm run build                  # Build for production
vendor/bin/pint                # Code formatter
php artisan test               # Run tests
```

### Key Environment Variables
```env
DB_DATABASE=reviso
CLOUDFLARE_ACCOUNT_ID=your_account_id
CLOUDFLARE_API_TOKEN=your_token
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
```

### Important Route Patterns
- `/login` - Login page
- `/signup` - Signup page
- `/teacher-dashboard` - Teacher dashboard
- `/admin-dashboard` - Admin dashboard
- `/psych-dashboard` - Psychology student dashboard
- `/educ-dashboard` - Education student dashboard
- `/accountancy-dashboard` - Accountancy student dashboard
- `/mock-boards/batch-analytics` - Batch analytics dashboard
- `/classes/{class}/mock-boards` - Class mock boards
- `/mock-boards/{mock_board}/analysis` - Mock board analysis

### Database Connection
```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reviso
DB_USERNAME=root
DB_PASSWORD=
```

---

**End of Report**
