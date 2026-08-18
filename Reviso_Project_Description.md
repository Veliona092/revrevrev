# Reviso Project Description

## Overview
Reviso is a comprehensive Laravel-based educational platform designed for managing mock board examinations and assessments. It supports multiple academic programs (Education, Accountancy, Psychology) with role-based access for teachers, students, and administrators. The system enables teachers to create mock boards, build quizzes, track student performance, and generate analytics across different program tabs.

## Technology Stack
- **Framework**: Laravel 12
- **PHP Version**: 8.5
- **Database**: MySQL (via Eloquent ORM)
- **Frontend**: Blade templates with custom CSS/SCSS, JavaScript
- **Authentication**: Laravel Sanctum/Breeze
- **Email**: Gmail API integration via custom service
- **File Storage**: Laravel Filesystem (local/public)
- **Queue System**: Laravel Queues
- **Testing**: PHPUnit
- **Code Quality**: Laravel Pint
- **Development Tools**: Laravel Sail, Vite for asset compilation

## Application Structure

### Core Directories
- `app/` - Application logic
  - `Auth/` - Custom authentication components
  - `Http/Controllers/` - Request handlers
  - `Http/Middleware/` - HTTP middleware
  - `Http/Requests/` - Form request validation classes
  - `Mail/` - Email templates and logic
  - `Models/` - Eloquent models
  - `Services/` - Business logic services
  - `Providers/` - Service providers
- `bootstrap/` - Laravel bootstrap files
- `config/` - Configuration files
- `database/` - Migrations, factories, seeders
- `public/` - Public assets (CSS, JS, images)
- `resources/` - Blade views, raw assets
- `routes/` - Route definitions
- `storage/` - File storage, logs, cache
- `tests/` - PHPUnit test files

## Database Schema

### Core Tables
- `users` - User accounts with roles (student, teacher, admin, superadmin) and program assignments
- `classes` - Class groups created by teachers
- `mock_boards` - Mock board examinations with review periods and visibility settings
- `mock_board_phases` - Pre-test and pre-boards phases within mock boards
- `modules` - Quiz/assessment modules
- `quiz_questions` - Individual questions with multiple choice options
- `quiz_answers` - Student responses to questions
- `quiz_attempts` - Complete quiz submission records
- `announcements` - Teacher announcements
- `chats` - Messaging system
- `user_progress` - Student learning progress tracking

### Key Relationships
- Users belong to classes and have programs (psych, educ, accountancy)
- Mock boards belong to classes and teachers, contain phases
- Phases link to modules (quizzes)
- Modules contain quiz questions
- Quiz attempts link users to modules with answers

## Models

### User Model (`app/Models/User.php`)
- Handles authentication and authorization
- Fields: id, name, email, role, program, program_locked, status
- Relationships: classes (many-to-many), mockBoards (hasMany), quizAttempts (hasMany)
- Methods: role checks, program validation

### ClassModel (`app/Models/ClassModel.php`)
- Represents class groups
- Fields: id, name, description, created_by (teacher)
- Relationships: users (belongsToMany), mockBoards (hasMany), modules (hasMany)

### MockBoard (`app/Models/MockBoard.php`)
- Core mock board entity
- Fields: class_id, teacher_id, title, description, program, review_period_start/end, passing_percentage, visibility, visible_to
- Relationships: class (belongsTo), teacher (belongsTo), phases (hasMany), attempts (hasMany through phases)

### MockBoardPhase (`app/Models/MockBoardPhase.php`)
- Pre-test and pre-boards phases
- Fields: mock_board_id, phase_type, title, module_id, question_ids, is_same_questions

### Module (`app/Models/Module.php`)
- Quiz/assessment containers
- Fields: title, is_quiz, is_formal_assessment, is_mock_board, class_id, passing_percentage, time_limit, created_by

### QuizQuestion (`app/Models/QuizQuestion.php`)
- Individual questions
- Fields: module_id, question_text, options (JSON), correct_answer, explanation, points

### QuizAttempt (`app/Models/QuizAttempt.php`)
- Student quiz submissions
- Fields: user_id, module_id, score, passed, started_at, completed_at, answers (JSON)

## Controllers

### Authentication Controllers
- `LoginController` - User login
- `RegisterController` - User registration with program selection
- `ProfileController` - Profile management, program updates

### Teacher Controllers
- `MockBoardController` - CRUD operations for mock boards
- `ClassManagerController` - Class and student management
- `QuizController` - Quiz creation and editing
- `BatchAnalyticsController` - Program-wise analytics and reporting

### Student Controllers
- `StudentDashboardController` - Program-specific dashboards
- `StudentAssessmentController` - Quiz taking and results
- `StudentProgressController` - Learning progress tracking

### Admin Controllers
- `AdminUserController` - User management and bulk operations
- `Adminapprovalcontroller` - User approval workflow

### Utility Controllers
- `AnnouncementController` - Announcement management
- `ChatController` - Messaging system
- `PerformanceController` - Detailed performance analytics

## Routes

### Web Routes (`routes/web.php`)
- `/` - Landing/dashboard based on role
- `/login`, `/register` - Authentication
- `/profile` - Profile management
- `/classes` - Class management (teachers)
- `/mock-boards` - Mock board CRUD
- `/quiz` - Quiz building and taking
- `/analytics` - Performance analytics
- `/announcements` - Announcement system
- `/chat` - Messaging

### API Routes (`routes/api.php`)
- RESTful endpoints for AJAX operations
- Quiz submission, analytics data, file uploads

## Services

### Business Logic Services
- `MockBoardStatisticsService` - Analytics calculations for mock boards
- `AiSettingsResolver` - AI integration settings
- `CloudflareAI` - AI-powered features
- `GmailService` - Email sending via Gmail API
- `GmailSender` - Alternative email service

### Validation Services
- Form Request classes in `app/Http/Requests/` for complex validation rules

## Views and Frontend

### Blade Templates Structure
- `resources/views/layouts/` - Base layouts (app, teacher, student variants)
- `resources/views/pages/` - Page-specific templates
  - `teacher/` - Teacher interface pages
  - `student/` - Student interface pages
  - `admin/` - Admin interface pages
- `resources/views/components/` - Reusable Blade components

### Key Pages
- **Teacher Dashboard**: Class overview, mock board management
- **Mock Board Index**: List/create/edit mock boards with program display
- **Quiz Builder**: Drag-and-drop question creation interface
- **Student Dashboard**: Program-specific content (Education/Accountancy/Psychology)
- **Quiz Taking**: Interactive assessment interface
- **Analytics Dashboard**: Charts and reports by program tabs

### Frontend Assets
- `public/css/` - Compiled stylesheets
- `public/js/` - JavaScript files
- `resources/css/` - SCSS source files
- `resources/js/` - JavaScript source files (compiled via Vite)

## Features

### Mock Board System
- Teachers create mock boards with review periods
- Automatic program assignment from teacher profile
- Pre-test and pre-boards phases
- Visibility controls (all students, selected, except)
- Quiz building with multiple choice questions

### Quiz Management
- Question bank with explanations
- Time limits and passing percentages
- Real-time quiz taking with progress tracking
- Automated scoring and results

### Analytics and Reporting
- Batch analytics by program (Education/Accountancy/Psychology)
- Individual student performance tracking
- Statistical analysis (ANOVA, passing rates)
- Progress visualization

### User Management
- Role-based access control
- Program assignment and locking
- Bulk user operations for admins
- Profile management with email verification

### Communication
- Announcement system
- Real-time chat/messaging
- Email notifications via Gmail integration

## User Roles and Permissions

### Student
- Access program-specific dashboard
- Take assigned quizzes and mock boards
- View personal progress and results
- Participate in chat

### Teacher
- Create and manage classes
- Build mock boards and quizzes
- View analytics for their classes
- Send announcements
- Program automatically assigned from profile

### Admin
- User management and approval
- System-wide analytics
- Bulk operations
- Unlock user programs

### Superadmin
- All admin permissions
- Additional system configuration

## Security Features
- CSRF protection on all forms
- Input validation and sanitization
- Role-based authorization gates
- Program locking to prevent unauthorized changes
- Secure file uploads
- Email verification for account changes

## Testing
- PHPUnit test suite in `tests/`
- Feature tests for critical workflows
- Unit tests for services and models
- Test factories for data seeding

## Deployment and Development
- Laravel Sail for local development
- Vite for asset compilation
- Composer for PHP dependencies
- NPM for frontend dependencies
- Database migrations for schema management
- Queue workers for background jobs

## Configuration
- Environment-based configuration in `config/`
- Database connections, mail settings, queue drivers
- Custom settings in `config/services.php`
- AI and Gmail API configurations

## Controllers

### List of Controllers (`app/Http/Controllers/`)
- `AdminApprovalController.php` - Handles user approval workflow for admins
- `AdminDashboardController.php` - Admin dashboard management
- `AdminUserController.php` - User management operations for admins
- `AiSettingsController.php` - AI settings configuration
- `AnnouncementController.php` - Announcement management
- `BatchAnalyticsController.php` - Program-wise batch analytics and reporting
- `ChatController.php` - Real-time messaging system
- `ClassManagerController.php` - Class and student management for teachers
- `Controller.php` - Base controller class
- `LectureController.php` - Lecture content management
- `MockBoardController.php` - Mock board CRUD operations
- `PerformanceController.php` - Student performance analytics
- `ProfileController.php` - User profile management
- `QuizController.php` - Quiz creation and submission handling
- `RegisterController.php` - User registration
- `StudentAssessmentController.php` - Student quiz taking interface
- `StudentDashboardController.php` - Program-specific student dashboards
- `StudentMockBoardController.php` - Student mock board interactions
- `StudentProgressController.php` - Learning progress tracking
- `TeacherDashboardController.php` - Teacher dashboard
- `TestAiController.php` - AI testing utilities

## Form Request Classes (`app/Http/Requests/`)
- `StoreClassAnnouncementRequest.php` - Validation for class announcements
- `UpdateClassAiSettingsRequest.php` - Validation for class AI settings updates
- `UpdateGlobalAiSettingsRequest.php` - Validation for global AI settings updates

## Service Providers (`app/Providers/`)
- `AppServiceProvider.php` - Main application service provider
- `AuthServiceProvider.php` - Authentication and authorization services

## Database Migrations (`database/migrations/`)

### User and Authentication
- `0001_01_01_000000_create_users_table.php` - Base users table
- `2024_01_01_000001_add_last_seen_at_to_users_table.php` - User activity tracking
- `2026_02_17_164624_rename_username_to_idnumber_in_users_table.php` - ID number field
- `2026_02_24_101418_add_email_verified_at_to_users_table.php` - Email verification
- `2026_02_24_101828_add_email_and_verified_at_to_users_table.php` - Email fields
- `2026_02_24_102252_create_signups_table.php` - User registration queue

### Classes and Content
- `2026_03_04_223009_create_classes_table.php` - Class groups
- `2026_03_04_223010_create_class_user_table.php` - Class-user relationships
- `2026_03_05_000000_create_lectures_table.php` - Lecture content
- `2026_03_15_063224_create_modules_table.php` - Quiz/assessment modules
- `2026_03_15_065535_create_announcements_table.php` - Announcements
- `2026_03_15_065535_create_module_progress_table.php` - Student progress
- `2026_03_16_184139_add_content_to_modules_table.php` - Module content
- `2026_03_16_185419_add_scroll_position_to_module_progress_table.php` - Progress tracking

### Quiz System
- `2026_03_17_123505_create_quiz_questions_table.php` - Question bank
- `2026_03_19_185728_create_quiz_attempts_table.php` - Quiz submissions
- `2026_03_19_203107_add_time_limit_to_modules_table.php` - Time limits
- `2026_03_19_203641_add_time_limit_to_modules_table.php` - Duplicate time limit
- `2026_03_19_211009_create_quiz_answers_table.php` - Student answers
- `2026_03_19_232622_add_topic_to_questions.php` - Question topics

### AI and Settings
- `2026_03_20_020000_create_ai_settings_table.php` - AI configuration
- `2026_03_20_020001_add_ai_settings_to_classes_table.php` - Class AI settings
- `2026_03_29_120001_add_is_pinned_to_announcements_table.php` - Pinned announcements
- `2026_03_29_120002_create_announcement_reads_table.php` - Read tracking
- `2026_03_29_185046_add_visibility_to_modules_table.php` - Module visibility
- `2026_03_29_185047_create_module_user_visibility_table.php` - User visibility

### User Management
- `2026_03_30_113552_add_assessment_ai_summary_to_classes_table.php` - AI summaries
- `2026_03_30_120849_add_assessment_ai_analysis_to_class_user_table.php` - User AI analysis
- `2026_03_30_124447_remove_unused_columns_from_tables.php` - Cleanup
- `2026_04_05_103538_add_attempt_count_to_quiz_attempts_table.php` - Attempt counting
- `2026_04_05_104300_add_ai_insights_to_quiz_attempts_table.php` - AI insights

### Mock Boards
- `2026_05_10_101436_create_mock_boards_table.php` - Mock board entities
- `2026_05_10_101504_create_mock_board_phases_table.php` - Pre-test/pre-boards phases
- `2026_05_10_101536_create_mock_board_attempts_table.php` - Student attempts
- `2026_05_10_101603_create_mock_board_statistics_table.php` - Statistics
- `2026_05_10_101633_add_is_mock_board_to_modules_table.php` - Mock board flag
- `2026_05_10_101701_add_mock_board_fields_to_quiz_attempts_table.php` - Mock board attempts
- `2026_05_12_120000_add_program_to_mock_boards_table.php` - Program assignment

### Communication
- `2026_03_20_140000_add_name_to_signups_table.php` - Signup names
- `2026_03_20_141000_add_users_search_indexes.php` - Search optimization
- `2026_03_20_142000_create_chats_tables.php` - Chat system

### Program and Status
- `2026_03_26_082258_programtousertable.php` - User programs
- `2026_03_26_172200_add_status_and_rejection_to_users_table.php` - User status
- `2026_03_26_172207_make_signup_role_nullable.php` - Flexible roles
- `2026_03_26_172208_add_is_formal_assessment_to_modules_table.php` - Assessment flag
- `2026_03_27_161347_normalize_legacy_roles_to_student_with_program.php` - Role normalization

## Views (`resources/views/`)

### Layouts
- `layouts/app.blade.php` - Main application layout
- `layouts/appAcc.blade.php` - Accountancy program layout
- `layouts/appAdmin.blade.php` - Admin layout
- `layouts/appEduc.blade.php` - Education program layout
- `layouts/appPsych.blade.php` - Psychology program layout
- `layouts/appTeach.blade.php` - Teacher layout
- `layouts/appTeachh.blade.php` - Alternative teacher layout
- `layouts/domain.blade.php` - Domain-specific layout
- `layouts/guest.blade.php` - Guest user layout
- `layouts/quiz.blade.php` - Quiz interface layout
- `layouts/sidebar.blade.php` - Sidebar component

### Authentication
- `auth/forgot-password.blade.php` - Password reset request
- `auth/show-new-password.blade.php` - New password form
- `emails/verify-text.blade.php` - Email verification (text)
- `emails/verify.blade.php` - Email verification (HTML)
- `login.blade.php` - Login form
- `signup.blade.php` - Registration form

### Program-Specific Pages
- `pages/accountancy/` - Accountancy student views
- `pages/admin/` - Admin interface pages
- `pages/educ/` - Education student views
- `pages/psych/` - Psychology student views
- `pages/student/` - General student pages
- `pages/teacher/` - Teacher interface pages
- `pages/users/` - User search and management

### Key Pages
- `pages/announcements/index.blade.php` - Announcement listing
- `pages/chat/` - Chat interface variants
- `pages/teacher/mock-boards/` - Mock board management
- `reusable/` - Reusable components (navbars, etc.)

## Routes (`routes/web.php`)

### Public Routes (No Auth Required)
- `GET /` - Home/redirect to login
- `GET /login` - Login page
- `POST /login` - Process login
- `POST /logout` - Logout
- `GET /forgot-password` - Password reset request
- `POST /forgot-password` - Send reset email
- `GET /reset-password/{token}` - Password reset form
- `POST /reset-password` - Process password reset
- `POST /signup` - User registration
- `GET /email/verify/{token}` - Email verification
- `POST /email/resend` - Resend verification email
- `GET /authorize-gmail` - Gmail OAuth authorization
- `GET /oauth2callback` - OAuth callback
- `GET /classes/{class}/join` - Class join via signed link
- `GET /join/{class}` - Class join confirmation
- `POST /join/{class}/confirm` - Process class join

### Protected Routes (Auth Required)
- `GET /dashboard` - Role-based dashboard redirect
- `GET /{program}-dashboard` - Program-specific dashboards
- `GET /assessment` - Student assessments
- `GET /assessment/{module}` - Take assessment
- `GET /progress` - Student progress
- `GET /tables` - Program-specific tables
- `GET /register` - Program-specific register
- `GET /assessmentexams` - Assessment exams

### Profile Routes
- `GET /profile` - Profile view
- `POST /profile/password` - Update password
- `POST /profile/email` - Change email
- `POST /profile/email/new` - Request new email verification
- `POST /profile/email/verify` - Verify email change
- `POST /profile/program` - Update program

### Admin Routes
- `GET /admin/users` - User management
- `GET /admin/users/export` - Export users CSV
- `POST /admin/users/{id}/reset-password` - Reset user password
- `POST /admin/users/{id}/toggle-status` - Toggle user status
- `GET /admin/approvals` - User approvals
- `POST /admin/approvals/{user}/approve` - Approve user
- `POST /admin/approvals/approve-many` - Bulk approve
- `POST /admin/approvals/approve-all` - Approve all
- `POST /admin/approvals/{user}/reject` - Reject user
- `POST /admin/users/{user}/unlock-program` - Unlock program

### Superadmin Routes
- `GET /superadmin/admins` - Manage admin accounts
- `POST /superadmin/admins/{id}/toggle-status` - Toggle admin status
- `POST /superadmin/approvals/{user}/override` - Override approval

### Class Management
- `GET /manageclass` - Class management dashboard
- `POST /classes` - Create class
- `DELETE /classes/{class}` - Delete class
- `GET /users/search-students` - Search students
- `POST /classes/{class}/students` - Add students to class
- `DELETE /classes/{class}/students/{student}` - Remove student
- `GET /classes/{class}/students` - Get class students
- `POST /classes/{class}/invite` - Generate invite
- `POST /classes/{class}/join-link` - Generate join link

### Content Management
- `GET /lectures` - Lecture listing
- `GET /teacher/create` - Create lecture
- `POST /teacher` - Store lecture
- `GET /teacher/{id}/edit` - Edit lecture
- `PUT /teacher/{id}` - Update lecture
- `DELETE /teacher/{id}` - Delete lecture
- `GET /teacher/lectures/{id}/file` - Download lecture file

### Module Management
- `POST /classes/{class}/modules` - Create module
- `GET /classes/{class}/modules/list` - List modules (JSON)
- `GET /classes/{class}/modules` - List modules (HTML)
- `GET /classes/{class}/modules/show` - Show modules
- `GET /modules/{module}/view` - View module file
- `DELETE /modules/{module}` - Delete module
- `POST /modules/{module}/progress` - Update progress

### Quiz System
- `GET /modules/{module}/quiz/create` - Create quiz
- `POST /modules/{module}/quiz/generate` - Generate AI quiz
- `POST /modules/{module}/quiz/store` - Store manual quiz
- `GET /modules/{module}/quiz/questions` - Get questions
- `POST /modules/{module}/quiz/submit` - Submit quiz
- `POST /modules/{module}/quiz/insights` - Generate insights
- `POST /quiz/{module}/answer` - Submit answer
- `POST /quiz/create-draft/{class}` - Create draft quiz
- `DELETE /modules/{module}/quiz/attempts` - Reset attempts
- `DELETE /modules/{module}/quiz/my-attempt` - Reset my attempt

### Announcements
- `GET /announcements` - Announcement index
- `POST /classes/{class}/announcements` - Create announcement
- `PATCH /announcements/{announcement}` - Update announcement
- `DELETE /announcements/{announcement}` - Delete announcement
- `POST /announcements/mark-read` - Mark as read
- `GET /classes/{class}/announcements/feed` - Class feed

### Student Routes
- `GET /my-classes` - My classes
- `GET /my-classes/{class}/modules` - Class modules

### Performance Analytics
- `GET /student-performance/{class}` - Student performance
- `GET /student-performance/{class}/progress` - Class progress tracker
- `GET /student-performance/{class}/students/{student}` - Student item analysis
- `POST /student-performance/{class}/refresh-ai` - Refresh AI summary
- `POST /student-performance/{class}/assessment-refresh-ai` - Refresh assessment AI
- `GET /student-performance/{class}/assessment-analysis/{student}` - Assessment analysis
- `POST /student-performance/{class}/assessment-analysis/{student}/generate-ai` - Generate AI analysis

### Chat System
- `GET /chat` - Chat index
- `GET /chat/conversations` - Get conversations
- `GET /chat/conversations/{chat}/messages` - Get messages
- `POST /chat/conversations` - Start conversation
- `POST /chat/conversations/{chat}/messages` - Send message
- `POST /chat/conversations/{chat}/remove` - Remove conversation

### AI Settings
- `GET /admin/ai-settings/classes` - Class AI settings
- `POST /admin/ai-settings/classes/{class}` - Update class AI settings
- `GET /superadmin/ai-settings` - Global AI settings
- `POST /superadmin/ai-settings` - Update global AI settings

### Mock Boards
- `GET /classes/{class}/mock-boards` - List mock boards
- `POST /classes/{class}/mock-boards` - Create mock board
- `PUT /mock-boards/{mock_board}` - Update mock board
- `DELETE /mock-boards/{mock_board}` - Delete mock board
- `POST /mock-boards/{mock_board}/phases` - Update phases
- `POST /mock-boards/{mock_board}/{phase}/questions/generate` - Generate questions
- `POST /mock-boards/{mock_board}/{phase}/questions/approve` - Approve questions
- `GET /mock-boards/{mock_board}/analysis` - Class analysis
- `POST /mock-boards/{mock_board}/compute-anova` - Compute ANOVA
- `GET /mock-boards/{mock_board}/student/{student}` - Student analysis

### Batch Analytics
- `GET /mock-boards/batch-analytics/` - Batch dashboard
- `GET /mock-boards/batch-analytics/{program}/{mock_board}` - Program analysis
- `POST /mock-boards/batch-analytics/{program}/{mock_board}/compute-anova` - Compute ANOVA

### Student Mock Boards
- `GET /mock-boards/` - Mock board dashboard
- `GET /mock-boards/{mock_board}` - Show mock board
- `GET /mock-boards/{mock_board}/{phase}/take` - Take phase
- `POST /mock-boards/{mock_board}/{phase}/submit` - Submit phase
- `POST /mock-boards/{mock_board}/{phase}/insights` - Get insights

### Utility Routes
- `GET /users/search` - Search users (API)
- `GET /users/search-page` - Search page
- `GET /teacher-dashboard-teachh` - Alternative teacher dashboard
- `GET /test-ai-laravel` - AI test endpoint

### Fallback
- `Route::fallback` - Redirect to dashboard or home</content>
<parameter name="filePath">c:\Users\De Adenip\Documents\revisonewtwo\myproject\Reviso_Project_Description.md