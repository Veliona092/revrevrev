# Mock Boards Feature Implementation Prompt

**Date:** May 10, 2026  
**Project:** Reviso LMS - Mock Boards Module  
**Objective:** Implement a complete Mock Boards (board exam prep) feature with Pre-Test and Pre-Boards assessments, item analysis, and statistical comparison using ANOVA.

---

## 1. CONTEXT & SYSTEM OVERVIEW

### 1.1 Existing System (Reviso LMS)

**Tech Stack:**
- Laravel v12, PHP 8.2+, MySQL, Blade templating
- Role-based access: superadmin → admin → teacher → student
- AI integration via Cloudflare Workers AI
- Email via Gmail OAuth2 API

**Core Learning Flow:**
- Teachers create Classes
- Teachers add Modules (documents + quizzes) to Classes
- Students enroll in Classes, access Modules sequentially
- Module types: Document (PDF/video) + Quiz (Pre-Assessment or Formal Assessment)

**Current Assessment System:**
- **Pre-Assessments** (`is_formal_assessment = 0`): Practice quizzes, no anti-cheat, multiple attempts, graded
- **Formal Assessments** (`is_formal_assessment = 1`): Monitored quizzes, anti-cheat, one attempt, graded (50% passing threshold)
- Item analysis exists: question performance per student, per class
- AI insights: strong areas, weak areas, recommendations

**Key Tables:**
- `users` — roles, tracks (accountancy, educ, psych)
- `classes` — teacher-owned, has many modules and students
- `modules` — document or quiz, belongs to class
- `quiz_questions` — question bank for modules
- `quiz_attempts` — tracks student quiz attempts (score, percentage, attempt_count)
- `quiz_answers` — individual question responses
- `module_progress` — tracks user progress through documents (0-100)

**Existing Routes & Controllers:**
- `ClassManagerController` — module CRUD, file delivery
- `QuizController` — quiz draft/store, answer submission, scoring, insights generation
- `PerformanceController` — class progress tracker, student analysis, AI summaries
- `StudentAssessmentController` — student assessment listing and take views
- Layout: `domain.blade.php` for immersive quiz experience (no sidebar)

---

## 2. MOCK BOARDS FEATURE SPECIFICATION

### 2.1 Overview

Mock Boards is a specialized assessment module for board exam preparation. Unlike regular quizzes, Mock Boards:
- Span a **6+ month Review Period**
- Have two explicit phases: **Pre-Test** (start of review) and **Pre-Boards** (end of review)
- Both are **graded and monitored** (anti-cheat enabled)
- Require **75% passing threshold** (adjustable by teacher)
- Support same or different question sets (teacher chooses)
- Track performance at **individual, class, and batch levels**
- Include **item analysis** (which questions students struggled with)
- Include **ANOVA statistical comparison** (Pre-Test vs Pre-Boards improvement)

### 2.2 Data Model

#### New Tables

```sql
-- Mock Board master record
CREATE TABLE mock_boards (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    class_id BIGINT NOT NULL,
    teacher_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,                -- e.g., "Board Exam Review 2026"
    description TEXT,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    passing_percentage INT DEFAULT 75,          -- 75% default
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (class_id, teacher_id)
);

-- Pre-Test & Pre-Boards phases
CREATE TABLE mock_board_phases (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mock_board_id BIGINT NOT NULL,
    phase_type ENUM('pre_test', 'pre_boards') NOT NULL,
    title VARCHAR(255) NOT NULL,               -- "Pre-Test" or "Pre-Boards"
    module_id BIGINT,                          -- Links to actual quiz module
    question_ids JSON,                         -- JSON array of quiz_question IDs (if custom)
    is_same_questions BOOLEAN DEFAULT 0,       -- 1 if pre_test and pre_boards share questions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mock_board_id) REFERENCES mock_boards(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    INDEX (mock_board_id)
);

-- Mock board attempts (extends quiz_attempts tracking)
CREATE TABLE mock_board_attempts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    mock_board_id BIGINT NOT NULL,
    phase_type ENUM('pre_test', 'pre_boards') NOT NULL,
    quiz_attempt_id BIGINT,                    -- Links to quiz_attempts table
    score INT,
    total INT,
    percentage INT,
    passed BOOLEAN DEFAULT 0,
    attempt_count INT DEFAULT 1,
    ai_strong TEXT,                            -- Cached AI analysis
    ai_weak TEXT,
    ai_recommendation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mock_board_id) REFERENCES mock_boards(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_attempt_id) REFERENCES quiz_attempts(id) ON DELETE SET NULL,
    INDEX (user_id, mock_board_id, phase_type)
);

-- For ANOVA statistical comparison
CREATE TABLE mock_board_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mock_board_id BIGINT NOT NULL UNIQUE,
    class_id BIGINT NOT NULL,
    pre_test_count INT DEFAULT 0,              -- Total attempts
    pre_test_mean DECIMAL(5,2),                -- Mean score
    pre_test_std_dev DECIMAL(5,2),             -- Standard deviation
    pre_boards_count INT DEFAULT 0,
    pre_boards_mean DECIMAL(5,2),
    pre_boards_std_dev DECIMAL(5,2),
    anova_f_statistic DECIMAL(10,4),           -- F-statistic result
    anova_p_value DECIMAL(10,6),               -- p-value (significance)
    anova_significant BOOLEAN,                 -- true if p < 0.05
    improvement_percentage DECIMAL(5,2),       -- (pre_boards_mean - pre_test_mean) / pre_test_mean * 100
    computed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mock_board_id) REFERENCES mock_boards(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX (class_id)
);
```

#### Model Changes

**New Models:**
- `MockBoard` — hasManyThrough phases, hasMany attempts, hasOne statistics
- `MockBoardPhase` — belongsTo MockBoard, belongsTo Module
- `MockBoardAttempt` — belongsTo MockBoard, User, QuizAttempt
- `MockBoardStatistic` — belongsTo MockBoard

**Modified Models:**
- `Module` — add `is_mock_board BOOLEAN DEFAULT 0` column
- `QuizAttempt` — add `mock_board_id` and `mock_board_phase_type` for linking

---

## 3. FEATURE REQUIREMENTS

### 3.1 Teacher Interface (Class Management)

**Location:** Modules drawer in Class Management page (new tab)

**Tab: "Mock Boards"**

Form to create Mock Board:
- **Title** (required) — e.g., "Board Exam Review 2026"
- **Description** (optional)
- **Review Period Start** (date picker)
- **Review Period End** (date picker)
- **Passing Percentage** (number, default 75)
- **Visibility** (all students / selected / except) — reuse existing visibility picker

After creation, show:
- List of Mock Boards for this class
- Each card shows:
  - Title, review period dates
  - Pre-Test & Pre-Boards status (created/pending/published)
  - Edit & delete buttons
  
**Edit Mock Board:**
- Form to update review period, passing %, description
- Subsection to manage Pre-Test phase:
  - **Option 1: Manual Question Entry**
    - Form to add questions (like existing quiz creation)
    - Text field for question, options A-D, correct answer
    - Add/edit/delete individual questions
  - **Option 2: AI Generation**
    - File upload (PDF, DOCX) — same as existing quiz generation
    - Button: "Generate Questions with AI"
    - Specify number of questions (e.g., 50)
    - AI generates board-level difficulty questions
    - Review & approve before publishing
  - **Option 3: Link Existing Module**
    - Dropdown to select existing quiz module
  - **Checkbox: "Use same questions for Pre-Boards"** (if checked, Pre-Boards will use same questions as Pre-Test)
  
- Subsection to manage Pre-Boards phase:
  - If "Use same questions" is checked → auto-populated, read-only, shows Pre-Test questions
  - If unchecked → same three options (manual entry, AI generation, link existing)

### 3.2 Student Interface

**Navigation:** Add "Mock Boards" navbar button

**Mock Boards Dashboard** (`/mock-boards`)
- Show list of enrolled Mock Boards (by class)
- For each Mock Board:
  - Title, review period, status (ongoing/ended)
  - Pre-Test phase:
    - Status: not started / in progress / completed
    - Latest score (if attempted)
    - Button: "Take Pre-Test" or "View Results"
  - Pre-Boards phase:
    - Status: not started / in progress / completed (locked until Pre-Test taken)
    - Latest score (if attempted)
    - Button: "Take Pre-Boards" or "View Results"

**Taking Pre-Test / Pre-Boards:**
- Use existing assessment-take interface (`domain.blade.php`)
- Both phases have anti-cheat enabled
- Pre-Test: allow multiple attempts
- Pre-Boards: allow one attempt only (or mark as reviewed after completion)
- Show 75% passing threshold in start screen
- After completion:
  - Show score gauge (0-100%)
  - Show pass/fail status (75% threshold)
  - Show AI insights (strong/weak areas, recommendations)
  - Option to return to Mock Boards dashboard

### 3.3 Teacher Analytics (Student Assessment Analysis)

**Location:** Existing Student Performance page → Assessment Analysis tab

**New Mock Boards Analysis Section** (below existing Assessment Analysis)

Show per-student Mock Boards results:
- List of Mock Boards in this class
- For each Mock Board:
  - Pre-Test attempt: score, date, passed/failed
  - Pre-Boards attempt: score, date, passed/failed
  - **Improvement:** (Pre-Boards % - Pre-Test %) — show as +/- value with color
  - **Item Analysis:** Question performance across both phases
    - Show questions and % correct for Pre-Test
    - Show questions and % correct for Pre-Boards
    - Highlight questions with biggest improvement/regression

### 3.4 Teacher Analytics (Class-Level Statistical Report)

**Location:** New page `/mock-boards/{mock_board}/analysis` (class view)

**Class Mock Boards Analysis Dashboard**

Section 1: **Attempt Summary**
- Total students in class
- Students who completed Pre-Test
- Students who completed Pre-Boards
- Overall pass rate (both phases combined)

Section 2: **ANOVA Statistical Comparison**
- **Pre-Test Statistics:**
  - Mean score
  - Standard deviation
  - Count of attempts
  - Distribution (histogram or box plot if UI allows)
  
- **Pre-Boards Statistics:**
  - Mean score
  - Standard deviation
  - Count of attempts
  - Distribution

- **ANOVA Results:**
  - F-statistic
  - p-value
  - **Interpretation Box:**
    - If p < 0.05: "✅ Statistically significant improvement detected. The review period had a measurable impact on class performance."
    - If p >= 0.05: "⚠️ No statistically significant improvement. Consider reviewing curriculum or instructional strategies."
  - Improvement percentage: "(Pre-Boards mean - Pre-Test mean) / Pre-Test mean × 100"

Section 3: **Item Analysis (Class-Level)**
- Table showing:
  - Question text
  - % correct on Pre-Test (class average)
  - % correct on Pre-Boards (class average)
  - Improvement/regression indicator
  - Difficulty classification
- Sort by: Biggest improvement, biggest regression, most common mistakes

Section 4: **Student Performance Grid** (Optional)
- Student name
- Pre-Test score
- Pre-Boards score
- Improvement
- Passed both phases: yes/no

---

## 4. IMPLEMENTATION PLAN

### Phase 1: Database & Models (Foundation)

**Tasks:**
1. Create migrations:
   - `create_mock_boards_table`
   - `create_mock_board_phases_table`
   - `create_mock_board_attempts_table`
   - `create_mock_board_statistics_table`
   - Add `is_mock_board` to `modules` table
   - Add `mock_board_id`, `mock_board_phase_type` to `quiz_attempts` table

2. Create Eloquent Models:
   - `MockBoard` with relationships
   - `MockBoardPhase` with relationships
   - `MockBoardAttempt` with relationships
   - `MockBoardStatistic` with relationships

3. Update existing Models:
   - `Module` — add mock board relationship
   - `QuizAttempt` — add mock board relationship
   - `User` — add hasMany MockBoardAttempts

---

### Phase 2: Teacher Interface (Creation & Management)

**Tasks:**
1. Add "Mock Boards" tab to Modules drawer in Class Management page
   - Form to create Mock Board (title, description, dates, passing %)
   - Form to manage Pre-Test phase with three options:
     - **Manual Entry:** Form to add questions one by one
     - **AI Generation:** File upload (PDF/DOCX) → call AI to generate questions → review before approval
     - **Link Existing:** Dropdown to select existing quiz module
   - Form to manage Pre-Boards phase (same three options OR reuse Pre-Test questions if checkbox selected)
   - List of Mock Boards with edit/delete buttons

2. Create/Update Routes:
   - `POST /classes/{class}/mock-boards` — store new Mock Board
   - `PUT /mock-boards/{mock_board}` — update Mock Board
   - `DELETE /mock-boards/{mock_board}` — delete Mock Board
   - `POST /mock-boards/{mock_board}/phases` — create/update phases
   - `POST /mock-boards/{mock_board}/{phase}/questions/generate` — upload file & generate questions with AI
   - `POST /mock-boards/{mock_board}/{phase}/questions/approve` — approve generated questions & create quiz module
   - `GET /classes/{class}/mock-boards/list` — list Mock Boards (AJAX)

3. Create Controller:
   - `MockBoardController` (or extend `ClassManagerController`)
   - Methods: 
     - `store()`, `update()`, `delete()` — CRUD for Mock Boards
     - `updatePhases()` — create/update phase definitions
     - `generateQuestions()` — handle file upload, call AI, return preview
     - `approveGeneratedQuestions()` — create quiz module from approved questions
     - `listForClass()` — AJAX endpoint to list Mock Boards

4. Reuse existing patterns:
   - Use existing visibility picker (all/selected/except students)
   - Use existing PDF parsing (smalot/pdfparser) for file uploads
   - Reuse `CloudflareAI` service for question generation
   - Call existing `QuizController::generateQuizAi()` logic or extract to shared service
   - Gate AI generation by `AiSettingsResolver::isFeatureEnabled('quiz_generation', $class)`

---

### Phase 3: Student Interface (Taking Assessments)

**Tasks:**
1. Create Routes:
   - `GET /mock-boards` — dashboard
   - `GET /mock-boards/{mock_board}` — detail view
   - `GET /mock-boards/{mock_board}/{phase}/take` — take pre-test/pre-boards
   - `POST /mock-boards/{mock_board}/{phase}/submit` — submit attempt
   - `POST /mock-boards/{mock_board}/{phase}/insights` — get AI analysis

2. Create/Update Controller:
   - `StudentMockBoardController` — dashboard, take, submit, insights
   - Reuse logic from `StudentAssessmentController` and `QuizController`

3. Create Views:
   - `student/mock-boards-dashboard.blade.php` — list all enrolled Mock Boards
   - `student/mock-board-detail.blade.php` — detail with pre-test & pre-boards status
   - `student/mock-board-take.blade.php` — reuse assessment-take flow (anti-cheat enabled)

4. JavaScript:
   - Reuse existing assessment-taking logic (anti-cheat, timer, navigation)
   - Add phase tracking (pre_test vs pre_boards)
   - Enforce one-attempt limit for Pre-Boards

---

### Phase 4: Teacher Analytics - Per-Student Analysis

**Tasks:**
1. Create Routes:
   - `GET /teacher/mock-boards/{mock_board}/student/{student}` — per-student analysis

2. Create View:
   - `teacher/mock-board-student-analysis.blade.php`
   - Show Pre-Test & Pre-Boards attempts
   - Show improvement metrics
   - Show item analysis (question performance across phases)

3. Create Controller Method:
   - `MockBoardController::studentAnalysis()` or `PerformanceController` extension
   - Fetch attempts, calculate improvement, fetch question performance

### Phase 4b: AI Question Generation Review Interface (Part of Teacher Interface)

**Tasks:**
1. Create Modal/Dialog for AI-generated question review:
   - Show generated questions preview
   - Ability to edit/delete individual questions before approval
   - Show question text, options, correct answer
   - Button to "Approve & Publish" (creates quiz module for phase)
   - Button to "Regenerate" (upload file again, re-run AI)
   - Cancel button (discard generated questions)

2. Create View:
   - `teacher/mock-board-question-review.blade.php` (or modal in modules drawer)
   - Show preview of all generated questions
   - Edit form for each question (inline or modal)
   - Approve button triggers `approveGeneratedQuestions()` which creates quiz module

3. Create Controller Method:
   - `MockBoardController::approveGeneratedQuestions()`
   - Create `Module` record for the phase
   - Create `QuizQuestion` records for all approved questions
   - Link to MockBoardPhase

---

### 3.4 Teacher Analytics (Class-Level Statistical Report)

**Location:** New page `/mock-boards/{mock_board}/analysis` (class view)

**Class Mock Boards Analysis Dashboard**

Section 1: **Attempt Summary**
- Total students in class
- Students who completed Pre-Test
- Students who completed Pre-Boards
- Overall pass rate (both phases combined)

Section 2: **ANOVA Statistical Comparison**
- **Pre-Test Statistics:**
  - Mean score
  - Standard deviation
  - Count of attempts
  - Distribution (histogram or box plot if UI allows)
  
- **Pre-Boards Statistics:**
  - Mean score
  - Standard deviation
  - Count of attempts
  - Distribution

- **ANOVA Results:**
  - F-statistic
  - p-value
  - **Interpretation Box:**
    - If p < 0.05: "✅ Statistically significant improvement detected. The review period had a measurable impact on class performance."
    - If p >= 0.05: "⚠️ No statistically significant improvement. Consider reviewing curriculum or instructional strategies."
  - Improvement percentage: "(Pre-Boards mean - Pre-Test mean) / Pre-Test mean × 100"

Section 3: **Item Analysis (Class-Level)**
- Table showing:
  - Question text
  - % correct on Pre-Test (class average)
  - % correct on Pre-Boards (class average)
  - Improvement/regression indicator
  - Difficulty classification
- Sort by: Biggest improvement, biggest regression, most common mistakes

Section 4: **Student Performance Grid** (Optional)
- Student name
- Pre-Test score
- Pre-Boards score
- Improvement
- Passed both phases: yes/no

### 3.5 Batch-Level Analytics (NEW - Admin/Superadmin/Track Teachers)

**Location:** New page `/batch-analytics/mock-boards` (accessible from Student Performance)

**Batch Mock Boards Analysis Dashboard**

*Visible to:*
- **Admin & Superadmin:** View any batch (Psychology, Accountancy, Education) with full analytics
- **Track Teacher** (e.g., Psychology teacher): View only their batch with **passing rates only**

---

#### For Track Teachers (Psychology, Accountancy, Education):

**Simple Batch Passing Rates View**

Show per Mock Board:
- Mock Board title
- Review period dates
- **Pre-Test Passing Rate:** X% (of students who scored ≥ 75%)
- **Pre-Boards Passing Rate:** Y% (of students who scored ≥ 75%)
- **Improvement:** (Y% - X%) — shown as +/- value with color indicator
  - Green if positive improvement
  - Red if negative
  - Grey if no change

Example display:
```
Board Exam Review 2026
Mar 2026 - Sep 2026

Pre-Test:     65%
Pre-Boards:   82%
Improvement:  +17% ✓
```

**That's it.** No ANOVA, no class breakdown, no item analysis, no trends.

---

#### For Admin & Superadmin (Full Analytics):

**Batch Mock Boards Analysis Dashboard**

Section 1: **Batch Overview**
- Batch name (e.g., "Psychology Program")
- Number of classes in batch
- Total students in batch
- Mock Boards available in this batch

Section 2: **Batch Passing Rates Summary** (Same as above but for all batches)
- **Per Mock Board:**
  - Mock Board title
  - Pre-Test passing rate (% of students who passed 75%)
  - Pre-Boards passing rate
  - Improvement %
  - Class-by-class breakdown:
    - Class name → Pre-Test pass rate → Pre-Boards pass rate → Improvement
  - Overall batch pass rate (aggregate of all classes)

Section 3: **Batch ANOVA Comparison (Pre-Test vs Pre-Boards)**
- **Batch Pre-Test Statistics:**
  - Mean score (across all students in batch)
  - Standard deviation
  - Total attempts
  
- **Batch Pre-Boards Statistics:**
  - Mean score (across all students in batch)
  - Standard deviation
  - Total attempts

- **ANOVA Results:**
  - F-statistic (for entire batch)
  - p-value
  - **Interpretation Box:**
    - Significant improvement or not
    - Improvement percentage
  - **Comparison to individual classes:** Show which classes had significant improvement vs. which didn't

Section 4: **Class-by-Class Comparison**
- Table showing per class:
  - Class name
  - Pre-Test mean
  - Pre-Boards mean
  - Improvement %
  - Significance (Yes/No)
  - Student count

Section 5: **Item Analysis (Batch-Level)**
- Questions and their performance across entire batch:
  - Question text
  - % correct Pre-Test (batch average)
  - % correct Pre-Boards (batch average)
  - Improvement indicator
  - Most problematic questions across all classes

Section 6: **Batch Performance Trends**
- Chart showing improvement over time (if multiple Mock Boards exist)
- Distribution of scores (Pre-Test vs Pre-Boards)
- Pass rate trends by class

---

### Phase 6: AI Insights Integration

**Tasks:**
1. Extend existing AI services:
   - Reuse `CloudflareAI` service for generating insights
   - Call after Pre-Test and Pre-Boards completion
   - Cache in `mock_board_attempts` table (ai_strong, ai_weak, ai_recommendation)

2. Create Route:
   - `POST /mock-boards/{mock_board}/{phase}/insights` — generate insights
   - Gate by `AiSettingsResolver`

3. Update View:
   - Show AI analysis on results screen (reuse existing assessment results layout)

### Phase 5b: Batch-Level Analytics Dashboard (NEW)

**Tasks:**
1. Create Routes:
   - `GET /batch-analytics/mock-boards` — batch analytics dashboard (shows different views based on user role)
   - `GET /batch-analytics/mock-boards/{program}/{mock_board}` — batch analysis for specific Mock Board (admin/superadmin only)
   - `POST /batch-analytics/mock-boards/{program}/{mock_board}/compute-anova` — trigger batch-level ANOVA (AJAX, admin/superadmin only)

2. Authorization & Middleware:
   - Create Gate: `can-view-batch-analytics`
   - Admin & Superadmin: Can view any batch with full analytics
   - Track Teachers (Psychology, Accountancy, Education): Can view only their batch with **passing rates only** (no ANOVA, no item analysis)
   - Non-track teachers: Cannot access
   - Implement in `BatchAnalyticsController` with role-based view routing

3. Create Service Methods (extend `MockBoardStatisticsService`):
   - `computeBatchPassingRates(program, mock_board_id)` — return passing rates for Pre-Test and Pre-Boards
   - `computePassingRateImprovement(pre_test_rate, pre_boards_rate)` — calculate improvement %
   - For Admin/Superadmin only:
     - `computeBatchPreTestStats(program, mock_board_id)` — mean, std dev for entire batch Pre-Test
     - `computeBatchPreBoardsStats(program, mock_board_id)` — mean, std dev for entire batch Pre-Boards
     - `computeBatchANOVA(program, mock_board_id)` — F-stat, p-value for entire batch (all classes combined)
     - `getClassComparisonData(program, mock_board_id)` — per-class statistics for comparison table
     - `getItemAnalysisByBatch(program, mock_board_id)` — question performance across entire batch

4. Create Views:
   - `teacher/batch-analytics-dashboard.blade.php` — list Mock Boards by batch
     - For track teachers: Show passing rates card (Pre-Test %, Pre-Boards %, Improvement %)
     - For admin/superadmin: Show passing rates card + link to full analysis
   - `teacher/batch-mock-boards-analysis.blade.php` (admin/superadmin only) — detailed batch analysis
     - Display batch overview (program name, # classes, # students)
     - Display passing rates summary + class breakdown
     - Display batch ANOVA results with interpretation
     - Display class-by-class comparison table
     - Display batch-level item analysis

5. Create Controller:
   - `BatchAnalyticsController` (new)
   - Methods:
     - `dashboard()` — list all Mock Boards grouped by program
       - Filter by user role: show full analytics for admin/superadmin, simple rates for track teachers
     - `mockBoardsAnalysis($program, $mock_board)` — show detailed batch analysis (admin/superadmin only, abort 403 for others)
     - `computeANOVA($program, $mock_board)` — AJAX endpoint to compute batch ANOVA (admin/superadmin only)
   - Add authorization checks via Gate

6. Update Student Performance page:
   - Add "View Batch Analytics" button (visible to Admin/Superadmin/Track Teachers)
   - Link to `/batch-analytics/mock-boards`
   - For track teachers, label might be "View Batch Progress" or "View Program Progress"

---

## 5. DETAILED TECHNICAL SPECIFICATIONS

### 5.1 ANOVA Calculation

**Formula & Implementation:**

ANOVA tests whether the mean difference between Pre-Test and Pre-Boards is statistically significant.

```
Given:
- Pre-Test scores: [s1, s2, ..., sn]
- Pre-Boards scores: [t1, t2, ..., tn] (must be paired by student)

Calculate:
1. Grand mean: (sum of all scores) / (2n)
2. Group means: mean_pre = avg(pre_test), mean_post = avg(pre_boards)
3. SS_between = n * (mean_pre - grand_mean)² + n * (mean_post - grand_mean)²
4. SS_within = sum((xi - group_mean)²) for all observations
5. MS_between = SS_between / (k - 1) where k=2 groups
6. MS_within = SS_within / (2n - k) = SS_within / (2n - 2)
7. F = MS_between / MS_within
8. p_value = lookup F-distribution with (1, 2n-2) degrees of freedom

Interpret:
- If p < 0.05: Reject null hypothesis (significant improvement)
- If p >= 0.05: Fail to reject null hypothesis (no significant difference)
```

**Implementation in PHP:**

Create `app/Services/MockBoardStatisticsService.php`:
- Use `\phpunit\Framework\Assert` or custom implementation
- Calculate descriptive stats (mean, variance, std dev)
- Compute ANOVA manually or use statistical library
- For p-value lookup, use approximation or external library (e.g., `statslibphp/stats` if needed)

**Simpler Alternative (if avoiding heavy dependencies):**
- Store raw scores in mock_board_attempts
- Calculate mean, std dev, F-statistic manually
- Use simplified p-value interpretation or reference table
- Display results with caveat about sample size

### 5.2 Item Analysis Calculation

**Per-Question Performance:**

For each question across all attempts:
```
correct_count = number of students who answered correctly (pre-test or pre-boards)
attempt_count = total number of students who attempted the question
pct_correct = (correct_count / attempt_count) * 100
```

**Query Pattern:**
```sql
SELECT
    qq.id,
    qq.question_text,
    SUM(CASE WHEN qa.selected_option = qq.correct THEN 1 ELSE 0 END) as correct_count,
    COUNT(DISTINCT qa.quiz_attempt_id) as attempt_count,
    ROUND(SUM(CASE WHEN qa.selected_option = qq.correct THEN 1 ELSE 0 END) 
          / COUNT(DISTINCT qa.quiz_attempt_id) * 100, 1) as pct_correct
FROM quiz_questions qq
LEFT JOIN quiz_answers qa ON qa.question_id = qq.id
LEFT JOIN quiz_attempts qat ON qat.id = qa.quiz_attempt_id
WHERE qq.module_id = ? AND qat.mock_board_phase_type = 'pre_test'
GROUP BY qq.id
ORDER BY pct_correct ASC
```

Repeat for pre_boards, then compare side-by-side.

### 5.3 Route Summary

**New Routes (add to routes/web.php):**

```php
// Student Mock Boards
Route::prefix('mock-boards')->middleware('auth')->group(function () {
    Route::get('/', [StudentMockBoardController::class, 'dashboard'])->name('mock-boards.dashboard');
    Route::get('{mock_board}', [StudentMockBoardController::class, 'show'])->name('mock-boards.show');
    Route::get('{mock_board}/{phase}/take', [StudentMockBoardController::class, 'take'])->name('mock-boards.take');
    Route::post('{mock_board}/{phase}/submit', [StudentMockBoardController::class, 'submit'])->name('mock-boards.submit');
    Route::post('{mock_board}/{phase}/insights', [StudentMockBoardController::class, 'insights'])->name('mock-boards.insights');
});

// Teacher Mock Boards Management
Route::prefix('classes/{class}')->middleware('auth')->group(function () {
    Route::post('mock-boards', [MockBoardController::class, 'store'])->name('mock-boards.store');
    Route::get('mock-boards/list', [MockBoardController::class, 'listForClass'])->name('mock-boards.list');
});

Route::prefix('mock-boards')->middleware('auth')->group(function () {
    Route::put('{mock_board}', [MockBoardController::class, 'update'])->name('mock-boards.update');
    Route::delete('{mock_board}', [MockBoardController::class, 'destroy'])->name('mock-boards.destroy');
    Route::post('{mock_board}/phases', [MockBoardController::class, 'updatePhases'])->name('mock-boards.phases.update');
    Route::post('{mock_board}/{phase}/questions/generate', [MockBoardController::class, 'generateQuestions'])->name('mock-boards.questions.generate');
    Route::post('{mock_board}/{phase}/questions/approve', [MockBoardController::class, 'approveGeneratedQuestions'])->name('mock-boards.questions.approve');
    Route::get('{mock_board}/analysis', [MockBoardController::class, 'classAnalysis'])->name('mock-boards.analysis');
    Route::post('{mock_board}/compute-anova', [MockBoardController::class, 'computeANOVA'])->name('mock-boards.anova.compute');
    Route::get('{mock_board}/student/{student}', [MockBoardController::class, 'studentAnalysis'])->name('mock-boards.student-analysis');
});

// Batch Analytics (Admin, Superadmin, Track Teachers only)
Route::prefix('batch-analytics')->middleware('auth')->group(function () {
    Route::get('/mock-boards', [BatchAnalyticsController::class, 'dashboard'])->name('batch-analytics.mock-boards.dashboard');
    Route::get('/mock-boards/{program}/{mock_board}', [BatchAnalyticsController::class, 'mockBoardsAnalysis'])->name('batch-analytics.mock-boards.analysis');
    Route::post('/mock-boards/{program}/{mock_board}/compute-anova', [BatchAnalyticsController::class, 'computeANOVA'])->name('batch-analytics.anova.compute');
});
```

Key additions:
- `generateQuestions()` — POST file upload, trigger AI generation (same as existing quiz generation)
- `approveGeneratedQuestions()` — POST to approve generated questions and create quiz module for phase

---

## 6. EXECUTION STEPS (FOR AI AGENT)

### Step 1: Database Layer
1. Write migrations for new tables (mock_boards, mock_board_phases, mock_board_attempts, mock_board_statistics)
2. Modify existing tables (modules, quiz_attempts)
3. Run migrations and verify schema
4. Test relationships with tinker

### Step 2: Models
1. Create `MockBoard`, `MockBoardPhase`, `MockBoardAttempt`, `MockBoardStatistic` models
2. Define relationships (belongsTo, hasMany, hasManyThrough)
3. Add scopes for filtering (e.g., `ongoing()`, `ended()`, `byClass()`)
4. Test model creation and relationships

### Step 3: Services
1. Create `MockBoardStatisticsService` with ANOVA calculation
2. Create helper methods for mean, std dev, improvement %
3. Write unit tests for ANOVA calculation
4. Test with sample data

### Step 4: Controllers
1. Create `MockBoardController` for teacher (create, update, delete, phases, analysis, generateQuestions, approveGeneratedQuestions)
2. Create `StudentMockBoardController` for student (dashboard, take, submit, insights)
3. Add authorization checks (can only teacher edit their own class mock boards)
4. Add validation for form inputs
5. **AI Question Generation Methods:**
   - `generateQuestions()` — Handle file upload (PDF/DOCX), parse with smalot/pdfparser, call CloudflareAI to generate questions, return preview JSON
   - `approveGeneratedQuestions()` — Create Module record, create QuizQuestion records, link to MockBoardPhase

### Step 5: Views - Teacher Interface
1. Create tab in Modules drawer (`mock-boards` tab)
2. Create form to create Mock Board
3. Create form to manage phases with three options:
   - Manual question entry form
   - AI generation section (file upload + review modal)
   - Existing module link dropdown
4. Create "Use same questions" checkbox with conditional Pre-Boards display
5. Create list view for Mock Boards in a class

### Step 5b: Views - AI Question Generation Review
1. Create modal/dialog for reviewing AI-generated questions
2. Show preview of all generated questions with options A-D and correct answer
3. Add inline edit form for each question (or edit modal)
4. Add delete button for questions
5. Add "Approve & Publish" button to confirm and create quiz module
6. Add "Regenerate" button to re-run AI
7. Add "Cancel" button to discard

### Step 6: Views - Student Interface
1. Create mock-boards dashboard (list all enrolled)
2. Create mock-board detail view (pre-test & pre-boards cards)
3. Create mock-board-take view (reuse assessment-take logic with anti-cheat)
4. Create results view with AI insights

### Step 7: Views - Teacher Analytics
1. Create per-student analysis view (improvement, item analysis)
2. Create class-level analysis view (ANOVA results, item analysis, student grid)

### Step 8: JavaScript
1. Reuse existing assessment-taking logic (anti-cheat, timer, navigation)
2. Add phase tracking and attempt limiting for Pre-Boards
3. Add form submission handlers for Mock Board creation/editing
4. Add AJAX handlers for file upload and AI question generation preview
5. Add question edit/delete functionality in review modal
6. Add "Approve & Publish" and "Regenerate" button handlers

### Step 8b: Batch Analytics JavaScript
1. Create program filter dropdown (Psychology, Accountancy, Education)
2. Create AJAX handler for "Compute Batch ANOVA" button
3. Add chart rendering for batch trends (if charting library available)
4. Show/hide class comparison table based on data availability

### Step 9: Integration & Testing
1. Test full teacher flow: create Mock Board → upload PDF → AI generates questions → review → approve → manage Pre-Boards
2. Test "Use same questions" checkbox — Pre-Boards should auto-populate with Pre-Test questions
3. Test manual question entry flow
4. Test existing module linking
5. Test full student flow: view dashboard → take pre-test → take pre-boards → view results
6. Test class-level ANOVA calculation with sample data
7. Test batch-level ANOVA calculation (multiple classes combined)
8. Test batch analytics authorization:
   - Admin can view all batches
   - Superadmin can view all batches
   - Psychology teacher can only view Psychology batch
   - Accountancy teacher can only view Accountancy batch
   - Regular teachers cannot access batch analytics
9. Test item analysis queries (class-level and batch-level)
10. Test AI insights generation
11. Verify authorization and access control

### Step 10: Documentation & Cleanup
1. Update model documentation
2. Add inline code comments
3. Verify all routes work as expected
4. Test on multiple roles (teacher, admin, superadmin)
5. Test AI generation with different file types (PDF, DOCX)
6. Verify question parsing and AI prompt are appropriate for board-level difficulty
7. Test batch analytics with multiple classes (ensure ANOVA aggregates correctly)
8. Verify passing rate calculations per class and per batch

---

## 7. KEY PATTERNS TO FOLLOW (FROM EXISTING CODEBASE)

### 7.1 Authorization
```php
// Use Gate or policy
if (!auth()->user()->can('manage-class', $class)) {
    abort(403);
}
```

### 7.2 AJAX Responses
```php
return response()->json([
    'success' => true,
    'message' => 'Mock board created',
    'data' => $mockBoard
]);
```

### 7.3 Visibility Picker (Reuse)
- Use existing `vis-toggle`, `vis-student-picker`, `vis-chips` classes
- Store in `visible_user_ids[]` hidden inputs
- Gate module/quiz access at controller level

### 7.4 Assessment Interface
- Reuse `assessment-take.blade.php` structure
- Keep timer, navigation, question display consistent
- Reuse anti-cheat logic from `QuizController`
- Reuse result display and AI insights parsing

### 7.5 AI Integration
- Call `CloudflareAI::run()` in controller
- Gate by `AiSettingsResolver::isFeatureEnabled()`
- Cache results in model columns
- Use existing insight parsing (strong/weak/recommendation)

---

## 8. ACCEPTANCE CRITERIA

✅ **Feature Complete When:**

1. **Database:**
   - All migrations run successfully
   - All relationships functional
   - Sample data can be created via tinker

2. **Teacher Interface:**
   - Can create Mock Board (title, dates, passing %)
   - Can create/link Pre-Test phase with quiz module
   - Can create/link Pre-Boards phase (same or different questions)
   - Can update and delete Mock Boards
   - Can view list of Mock Boards in class
   - Visibility picker works (all/selected/except students)

3. **Student Interface:**
   - Can view Mock Boards dashboard
   - Can view Mock Board detail (pre-test & pre-boards status)
   - Can take Pre-Test (multiple attempts allowed, anti-cheat enabled)
   - Can take Pre-Boards (one attempt only, anti-cheat enabled)
   - Can view results with score gauge and pass/fail status
   - Can view AI insights (strong/weak areas, recommendations)

4. **Analytics - Per-Student:**
   - Can view Pre-Test & Pre-Boards attempts for a student
   - Can see improvement metrics (% change)
   - Can see item analysis (question performance per phase)

5. **Analytics - Class-Level:**
   - Can view ANOVA statistics (mean, std dev, F-stat, p-value)
   - Can see interpretation of results (significant improvement or not)
   - Can view item analysis (class-level question performance)
   - Can see student performance grid

6. **AI Integration:**
   - AI insights generated after each attempt
   - Insights cached and reused
   - Insights displayed on results screen

7. **Authorization:**
   - Teachers can only manage Mock Boards in their own classes
   - Students can only access Mock Boards they're enrolled in
   - Pre-Boards locked until Pre-Test is completed
   - Pre-Boards limited to one attempt

8. **Testing:**
   - All tests pass (existing + new feature tests)
   - No regressions in existing assessment system

---

## 9. DEPLOYMENT CHECKLIST

- [ ] Code review
- [ ] All tests passing
- [ ] Database migrations reviewed
- [ ] Routes registered and tested
- [ ] Authorization checks in place
- [ ] UI/UX matches existing patterns
- [ ] Error handling for edge cases
- [ ] Performance optimized (no N+1 queries)
- [ ] Documentation updated
- [ ] Staging environment tested
- [ ] Production deployment

---

## 10. REFERENCE: EXISTING ASSESSMENT CODE PATTERNS

### Assessment-Take Flow (Reuse)
**File:** `resources/views/pages/student/assessment-take.blade.php`
- Start screen with quiz info, warnings, rules
- Quiz shell with timer, navigation, questions
- Anti-cheat handling (tab switching, blur events)
- Result screen with gauge, pass/fail, AI insights

### Quiz Controller (Reuse)
**File:** `app/Http/Controllers/QuizController.php`
- Methods: store, submit, generateInsights
- AJAX endpoints for saving answers and scores
- AI insights cached in quiz_attempts table
- Authorization checks via gate/policy

### Item Analysis Query (Reference)
**File:** `app/Http/Controllers/PerformanceController.php`
- Question performance calculation
- Per-student and per-class metrics
- HTML rendering for results display

### Class Management Form (Reuse)
**File:** `resources/views/pages/teacher/class-management.blade.php`
- Module upload form, quiz creation form
- Visibility picker (all/selected/except students)
- Tab-based drawer interface

---

## 11. NOTES FOR AI AGENT

- **Assume codebase access:** AI will have access to full Reviso codebase
- **Follow existing patterns:** Match code style, naming conventions, folder structure
- **Write testable code:** Each feature should have corresponding tests
- **Optimize queries:** Use eager loading (with), select only needed columns
- **Handle edge cases:** Empty results, no attempts, phase not started, etc.
- **User experience:** Match existing UI/UX, provide feedback via toasts/alerts
- **Documentation:** Inline comments for complex logic, PHPDoc for public methods
- **Error handling:** Graceful fallbacks for failed AI generation, ANOVA calculation errors

---

## 12. SUMMARY

This prompt provides a complete blueprint for implementing Mock Boards in Reviso. The AI agent should:

1. **Plan:** Review this spec and outline implementation order
2. **Execute:** Follow the execution steps in Phase 1-10
3. **Test:** Verify each phase works before moving to the next
4. **Integrate:** Ensure Mock Boards work seamlessly with existing assessment system
5. **Document:** Update code comments and controller documentation

**Total Estimated Scope:**
- 4 new tables, 3 new models
- 3 new controllers (MockBoard + StudentMockBoard + BatchAnalytics)
- 9 new views (tabs, dashboard, detail, take, analysis, student analysis, question review, batch dashboard, batch detailed analysis for admin)
- 1 new service (StatisticsService for ANOVA - class level + batch level for admin only)
- ~17 new routes (class-level + batch-level analytics)
- **~22-28 hours** of development + testing
  - Track teachers: Simple view (2-3 cards with passing rates)
  - Admin/Superadmin: Full analytics (ANOVA, item analysis, class comparison, trends)

---

**End of Mock Boards Implementation Prompt**