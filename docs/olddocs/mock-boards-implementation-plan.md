# Mock Boards Implementation Plan

**Date:** May 10, 2026  
**Status:** Phase 1 - Database Migrations  
**Goal:** Implement Mock Boards feature per `boardexams-corrections-v2.md`

---

## Phase 1: Database Migrations (Current)

### 1.1 Create Migration Files
Order matters for foreign key dependencies:

1. `create_mock_boards_table` - Master table (no FK dependencies)
2. `create_mock_board_phases_table` - Depends on mock_boards, modules
3. `create_mock_board_attempts_table` - Depends on mock_boards, users, quiz_attempts
4. `create_mock_board_statistics_table` - Depends on mock_boards, classes
5. `add_is_mock_board_to_modules` - Alter modules table
6. `add_mock_board_fields_to_quiz_attempts` - Alter quiz_attempts table

### 1.2 Migration Details

**File:** `2026_05_10_000001_create_mock_boards_table.php`
```php
Schema::create('mock_boards', function (Blueprint $table) {
    $table->id();
    $table->foreignId('class_id')->constrained()->onDelete('cascade');
    $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->date('review_period_start');
    $table->date('review_period_end');
    $table->integer('passing_percentage')->default(75);
    // CRITICAL: Visibility columns per corrections-v2.md
    $table->enum('visibility', ['all', 'selected', 'except'])->default('all');
    $table->json('visible_to')->nullable();
    $table->timestamps();
    
    $table->index(['class_id', 'visibility']);
    $table->index('teacher_id');
});
```

**File:** `2026_05_10_000002_create_mock_board_phases_table.php`
```php
Schema::create('mock_board_phases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mock_board_id')->constrained()->onDelete('cascade');
    $table->enum('phase_type', ['pre_test', 'pre_boards']);
    $table->string('title');
    $table->foreignId('module_id')->nullable()->constrained()->onDelete('set null');
    $table->json('question_ids')->nullable();
    $table->boolean('is_same_questions')->default(false);
    $table->timestamps();
    
    $table->unique(['mock_board_id', 'phase_type']); // Only one pre_test and one pre_boards per mock board
    $table->index('mock_board_id');
});
```

**File:** `2026_05_10_000003_create_mock_board_attempts_table.php`
```php
Schema::create('mock_board_attempts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('mock_board_id')->constrained()->onDelete('cascade');
    $table->enum('phase_type', ['pre_test', 'pre_boards']);
    $table->foreignId('quiz_attempt_id')->nullable()->constrained()->onDelete('set null');
    $table->integer('score')->nullable();
    $table->integer('total')->nullable();
    $table->integer('percentage')->nullable();
    $table->boolean('passed')->default(false);
    $table->integer('attempt_count')->default(1);
    $table->text('ai_strong')->nullable();
    $table->text('ai_weak')->nullable();
    $table->text('ai_recommendation')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'mock_board_id', 'phase_type']);
    $table->index(['mock_board_id', 'phase_type']);
});
```

**File:** `2026_05_10_000004_create_mock_board_statistics_table.php`
```php
Schema::create('mock_board_statistics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mock_board_id')->constrained()->onDelete('cascade');
    $table->foreignId('class_id')->constrained()->onDelete('cascade');
    // Pre-Test stats
    $table->integer('pre_test_count')->default(0);
    $table->decimal('pre_test_mean', 5, 2)->nullable();
    $table->decimal('pre_test_std_dev', 5, 2)->nullable();
    // Pre-Boards stats
    $table->integer('pre_boards_count')->default(0);
    $table->decimal('pre_boards_mean', 5, 2)->nullable();
    $table->decimal('pre_boards_std_dev', 5, 2)->nullable();
    // ANOVA results
    $table->decimal('anova_f_statistic', 10, 4)->nullable();
    $table->decimal('anova_p_value', 10, 6)->nullable();
    $table->boolean('anova_significant')->nullable();
    $table->decimal('improvement_percentage', 5, 2)->nullable();
    $table->timestamp('computed_at')->nullable();
    $table->timestamps();
    
    $table->unique('mock_board_id');
    $table->index('class_id');
});
```

**File:** `2026_05_10_000005_add_is_mock_board_to_modules.php`
```php
Schema::table('modules', function (Blueprint $table) {
    $table->boolean('is_mock_board')->default(false)->after('is_formal_assessment');
    $table->index('is_mock_board');
});
```

**File:** `2026_05_10_000006_add_mock_board_fields_to_quiz_attempts.php`
```php
Schema::table('quiz_attempts', function (Blueprint $table) {
    $table->foreignId('mock_board_id')->nullable()->constrained()->onDelete('set null')->after('module_id');
    $table->enum('mock_board_phase_type', ['pre_test', 'pre_boards'])->nullable()->after('mock_board_id');
    $table->index(['mock_board_id', 'mock_board_phase_type']);
});
```

---

## Phase 2: Models (After migrations)

### 2.1 Create Model Files

**app/Models/MockBoard.php**
- Relationships: belongsTo Class, belongsTo User (teacher), hasMany phases, hasMany attempts, hasOne statistics
- Method: `isVisibleTo(User $student)`
- Scopes: `ongoing()`, `ended()`, `byClass($classId)`

**app/Models/MockBoardPhase.php**
- Relationships: belongsTo MockBoard, belongsTo Module
- Accessors: `phaseLabel()`

**app/Models/MockBoardAttempt.php**
- Relationships: belongsTo User, belongsTo MockBoard, belongsTo QuizAttempt
- Casts: ai_strong, ai_weak, ai_recommendation

**app/Models/MockBoardStatistic.php**
- Relationships: belongsTo MockBoard, belongsTo Class
- Method: `interpretation()` for ANOVA results

### 2.2 Update Existing Models

**app/Models/Module.php**
- Add `is_mock_board` to $fillable
- Relationship: hasOne MockBoardPhase

**app/Models/QuizAttempt.php**
- Add `mock_board_id`, `mock_board_phase_type` to $fillable
- Relationship: belongsTo MockBoard

**app/Models/ClassModel.php**
- Relationship: hasMany MockBoard

**app/Models/User.php**
- Relationship: hasMany MockBoardAttempt

---

## Phase 3: Authorization Gates (Critical)

**File:** `app/Providers/AuthServiceProvider.php` or `AuthServiceProvider::boot()`

```php
Gate::define('manage-mock-board', function (User $user, MockBoard $mockBoard) {
    return $user->id === $mockBoard->teacher_id 
        || in_array($user->role, ['admin', 'superadmin']);
});

Gate::define('view-mock-board', function (User $user, MockBoard $mockBoard) {
    if (in_array($user->role, ['admin', 'superadmin', 'teacher'])) {
        return true;
    }
    // Students: check enrollment and visibility
    if ($user->role === 'student') {
        $isEnrolled = $mockBoard->class->students->contains($user);
        return $isEnrolled && $mockBoard->isVisibleTo($user);
    }
    return false;
});

Gate::define('view-batch-analytics', function (User $user) {
    return in_array($user->role, ['admin', 'superadmin']) 
        || in_array($user->track, ['psych', 'educ', 'accountancy']);
});
```

---

## Phase 4: Service Layer

**File:** `app/Services/MockBoardStatisticsService.php`

Methods:
- `computeClassStatistics(MockBoard $mockBoard)` - Pre-test/pre-boards stats
- `computeClassANOVA(MockBoard $mockBoard)` - F-stat, p-value
- `computeBatchPassingRates($program, $mockBoardId)`
- `computeBatchANOVA($program, $mockBoardId)`
- `getItemAnalysis(MockBoard $mockBoard, $phaseType = null)`

---

## Phase 5: Controllers - Teacher

**File:** `app/Http/Controllers/MockBoardController.php`

Methods:
- `index(ClassModel $class)` - List for class
- `store(ClassModel $class, Request $request)` - Create
- `update(MockBoard $mockBoard, Request $request)` - Edit
- `destroy(MockBoard $mockBoard)` - Delete
- `updatePhases(MockBoard $mockBoard, Request $request)` - Manage phases
- `generateQuestions(MockBoard $mockBoard, string $phase, Request $request)` - AI generation
- `approveGeneratedQuestions(MockBoard $mockBoard, string $phase, Request $request)` - Approve & create module
- `classAnalysis(MockBoard $mockBoard)` - Class ANOVA view
- `studentAnalysis(MockBoard $mockBoard, User $student)` - Per-student view
- `computeANOVA(MockBoard $mockBoard)` - AJAX endpoint

---

## Phase 6: Controllers - Student

**File:** `app/Http/Controllers/StudentMockBoardController.php`

Methods:
- `dashboard()` - List enrolled mock boards
- `show(MockBoard $mockBoard)` - Detail view
- `take(MockBoard $mockBoard, string $phase)` - Start assessment
- `submit(MockBoard $mockBoard, string $phase, Request $request)` - Submit answers
- `insights(MockBoard $mockBoard, string $phase)` - AI analysis

**CRITICAL:** In `take()` method, reuse `assessment-take.blade.php`:
```php
return view('pages.student.assessment-take', [
    'module' => $module,
    'isMockBoard' => true,
    'mockBoard' => $mockBoard,
    'phase' => $phase,
]);
```

---

## Phase 7: Controllers - Batch Analytics

**File:** `app/Http/Controllers/BatchAnalyticsController.php`

Methods:
- `dashboard()` - List by program (role-based filtering)
- `mockBoardsAnalysis(string $program, MockBoard $mockBoard)` - Detailed analysis
- `computeANOVA(string $program, MockBoard $mockBoard)` - AJAX endpoint

Authorization: Use `can:view-batch-analytics` middleware

---

## Phase 8: Views - Teacher

**Create directory:** `resources/views/pages/teacher/mock-boards/`

Files:
1. `index.blade.php` - List in modules drawer tab
2. `edit.blade.php` - Create/edit form (tabs for pre-test/pre-boards)
3. `class-analysis.blade.php` - ANOVA dashboard
4. `student-analysis.blade.php` - Per-student view
5. `batch-dashboard.blade.php` - Batch list (admin/track teachers)
6. `batch-analysis.blade.php` - Detailed batch analysis (admin only)

**Integration:** Add "Mock Boards" tab to `class-management.blade.php` modules drawer

---

## Phase 9: Views - Student

**File:** `resources/views/pages/student/mock-boards/dashboard.blade.php`
- List of enrolled mock boards
- Pre-test & pre-boards status cards

**File:** `resources/views/pages/student/mock-boards/show.blade.php`
- Detail view
- Status indicators
- Take assessment buttons

**CRITICAL:** Modify `assessment-take.blade.php`:
```blade
@if($isMockBoard ?? false)
    <div class="mock-board-header">
        <h3>{{ $mockBoard->title }} - {{ $phase === 'pre_test' ? 'Pre-Test' : 'Pre-Boards' }}</h3>
        <p>Passing: {{ $mockBoard->passing_percentage }}%</p>
    </div>
@endif
```

---

## Phase 10: Routes

**Add to `routes/web.php`:**

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

// Teacher Mock Boards
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

// Batch Analytics (CORRECTED ROUTE per corrections-v2.md)
Route::prefix('mock-boards/batch-analytics')->middleware(['auth', 'can:view-batch-analytics'])->group(function () {
    Route::get('/', [BatchAnalyticsController::class, 'dashboard'])->name('mock-boards.batch.dashboard');
    Route::get('{program}/{mock_board}', [BatchAnalyticsController::class, 'mockBoardsAnalysis'])->name('mock-boards.batch.analysis');
    Route::post('{program}/{mock_board}/compute-anova', [BatchAnalyticsController::class, 'computeANOVA'])->name('mock-boards.batch.anova.compute');
});
```

---

## Testing Checklist

### Phase 1 Test
- [ ] All migrations run successfully
- [ ] No foreign key errors
- [ ] Rollback works

### Phase 2-3 Test  
- [ ] Models create without errors
- [ ] Relationships work (tinker test)
- [ ] Gates return correct booleans

### Phase 4 Test
- [ ] ANOVA calculation works with sample data
- [ ] Item analysis queries run efficiently

### Phase 5-7 Test
- [ ] CRUD operations work
- [ ] Authorization blocks unauthorized access
- [ ] AI generation works (if AI service available)

### Phase 8-10 Test
- [ ] Teacher can create mock board
- [ ] Teacher can manage phases
- [ ] Student can view dashboard
- [ ] Student can take pre-test
- [ ] Anti-cheat works (tab switching triggers warning)
- [ ] Student can take pre-boards (after pre-test)
- [ ] Results display with ANOVA stats
- [ ] Batch analytics accessible to authorized users

---

**Next Step:** Execute Phase 1 - Database Migrations
