# MOCK BOARDS IMPLEMENTATION - CRITICAL CORRECTIONS & FIXES

**Date:** May 10, 2026  
**Purpose:** Essential corrections to the Mock Boards spec based on actual Reviso codebase review

---

## 1. CRITICAL FIXES (Must Implement)

### 1.1 Anti-Cheat Configuration
**Critical Issue:** Mock Board modules must have `is_formal_assessment = 1` to enable anti-cheat

**In MockBoardController when creating phase modules:**
```php
$module = Module::create([
    'title' => $phaseTitle,
    'is_quiz' => true,
    'is_formal_assessment' => true, // ⚠️ REQUIRED - enables anti-cheat
    'is_mock_board' => true,
    'class_id' => $mockBoard->class_id,
    'passing_percentage' => $mockBoard->passing_percentage,
    'time_limit' => $request->time_limit ?? 0,
]);
```

**Why:** Anti-cheat is disabled when `is_formal_assessment = 0`. We just fixed this bug. Mock Boards MUST use formal assessment mode.

---

### 1.2 Authorization Gates
**Missing:** Gates not defined in original spec. Add to `AuthServiceProvider`:

```php
// AuthServiceProvider.php
Gate::define('manage-mock-board', function (User $user, MockBoard $mockBoard) {
    return $user->id === $mockBoard->teacher_id 
        || $user->role === 'admin' 
        || $user->role === 'superadmin';
});

Gate::define('view-mock-board', function (User $user, MockBoard $mockBoard) {
    // Students can view if enrolled in the class
    if ($user->role === 'student') {
        return $mockBoard->class->students->contains($user);
    }
    return true; // Teachers/admins/superadmins can view
});

Gate::define('view-batch-analytics', function (User $user) {
    return in_array($user->role, ['admin', 'superadmin']) 
        || in_array($user->track, ['psych', 'educ', 'accountancy']);
});
```

**Use in Controllers:**
```php
// In MockBoardController methods
if (!auth()->user()->can('manage-mock-board', $mockBoard)) {
    abort(403);
}

// In BatchAnalyticsController
if (!auth()->user()->can('view-batch-analytics')) {
    abort(403);
}
```

---

### 1.3 Mock Boards Table - Add Visibility
**Missing Columns:** Original spec didn't include visibility columns

**Add to migration `create_mock_boards_table`:**
```php
// After passing_percentage column
$table->enum('visibility', ['all', 'selected', 'except'])->default('all');
$table->json('visible_to')->nullable(); // array of user_ids

// Add indexes
$table->index(['class_id', 'visibility']);
```

**In Model:**
```php
// MockBoard.php
protected $casts = [
    'visible_to' => 'array',
];

public function isVisibleTo(User $student): bool
{
    if ($this->visibility === 'all') {
        return true;
    }
    
    $visibleIds = $this->visible_to ?? [];
    
    if ($this->visibility === 'selected') {
        return in_array($student->id, $visibleIds);
    }
    
    if ($this->visibility === 'except') {
        return !in_array($student->id, $visibleIds);
    }
    
    return false;
}
```

**In StudentMockBoardController:**
```php
public function dashboard()
{
    $mockBoards = MockBoard::whereHas('class.students', function ($q) {
        $q->where('user_id', auth()->id());
    })
    ->where(function ($q) {
        $q->where('visibility', 'all')
          ->orWhere(function ($inner) {
              $inner->where('visibility', 'selected')
                    ->whereJsonContains('visible_to', auth()->id());
          })
          ->orWhere(function ($inner) {
              $inner->where('visibility', 'except')
                    ->whereJsonDoesntContain('visible_to', auth()->id());
          });
    })
    ->get();
}
```

---

## 2. IMPORTANT CHANGES

### 2.1 Route Structure - Fix Naming Conflict
**Issue:** `/batch-analytics/mock-boards` could conflict with future features

**Corrected Routes (in web.php):**
```php
// Student Mock Boards (unchanged)
Route::prefix('mock-boards')->middleware('auth')->group(function () {
    Route::get('/', [StudentMockBoardController::class, 'dashboard'])->name('mock-boards.dashboard');
    Route::get('{mock_board}', [StudentMockBoardController::class, 'show'])->name('mock-boards.show');
    Route::get('{mock_board}/{phase}/take', [StudentMockBoardController::class, 'take'])->name('mock-boards.take');
    Route::post('{mock_board}/{phase}/submit', [StudentMockBoardController::class, 'submit'])->name('mock-boards.submit');
    Route::post('{mock_board}/{phase}/insights', [StudentMockBoardController::class, 'insights'])->name('mock-boards.insights');
});

// Teacher Mock Boards Management (unchanged)
Route::prefix('classes/{class}')->middleware('auth')->group(function () {
    Route::post('mock-boards', [MockBoardController::class, 'store'])->name('mock-boards.store');
    Route::get('mock-boards/list', [MockBoardController::class, 'listForClass'])->name('mock-boards.list');
});

// Teacher Mock Boards (unchanged)
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

// CORRECTED: Batch analytics under mock-boards namespace to avoid conflicts
Route::prefix('mock-boards/batch-analytics')->middleware(['auth', 'can:view-batch-analytics'])->group(function () {
    Route::get('/', [BatchAnalyticsController::class, 'dashboard'])->name('mock-boards.batch.dashboard');
    Route::get('{program}/{mock_board}', [BatchAnalyticsController::class, 'mockBoardsAnalysis'])->name('mock-boards.batch.analysis');
    Route::post('{program}/{mock_board}/compute-anova', [BatchAnalyticsController::class, 'computeANOVA'])->name('mock-boards.batch.anova.compute');
});
```

---

### 2.2 Reuse assessment-take.blade.php - Don't Duplicate
**Issue:** Original spec suggested creating `mock-board-take.blade.php` 

**Correction:** Reuse existing `assessment-take.blade.php` to avoid code duplication and maintenance issues

**In StudentMockBoardController::take():**
```php
public function take(MockBoard $mockBoard, string $phase)
{
    // Authorization
    if (!$mockBoard->isVisibleTo(auth()->user())) {
        abort(403);
    }
    
    // Check phase validity
    if (!in_array($phase, ['pre_test', 'pre_boards'])) {
        abort(404);
    }
    
    // Get phase
    $mockBoardPhase = $mockBoard->phases()
        ->where('phase_type', $phase)
        ->firstOrFail();
    
    $module = $mockBoardPhase->module;
    
    // Important: Reuse assessment-take view
    return view('pages.student.assessment-take', [
        'module' => $module,
        'isMockBoard' => true,
        'mockBoard' => $mockBoard,
        'mockBoardPhase' => $mockBoardPhase,
        'phase' => $phase,
    ]);
}
```

**In assessment-take.blade.php** (add at top of template):
```blade
@if($isMockBoard ?? false)
    <div style="padding: 8px 12px; background: #eff6ff; border-left: 3px solid #3b82f6; margin-bottom: 16px; border-radius: 6px;">
        <p style="margin: 0; font-size: 16px; color: #1e40af; font-weight: 500;">
            {{ $mockBoard->title }} - {{ $phase === 'pre_test' ? 'Pre-Test' : 'Pre-Boards' }}
        </p>
        <p style="margin: 4px 0 0; font-size: 14px; color: #64748b;">
            Passing: {{ $mockBoard->passing_percentage }}%
        </p>
    </div>
@endif
```

**Why:** Anti-cheat fixes and timer logic are in assessment-take.blade.php. Having one file prevents bugs from duplication.

---

### 2.3 View Folder Organization
**Correction:** Follow existing Reviso pattern

**Recommended Structure:**
```
resources/views/pages/teacher/mock-boards/
  ├── index.blade.php           # List for class (in modules drawer tab)
  ├── edit.blade.php            # Create/edit form
  ├── student-analysis.blade.php # Per-student view
  ├── class-analysis.blade.php   # Class-level ANOVA view
  ├── batch-dashboard.blade.php  # Batch list (for admin/track teachers)
  └── batch-analysis.blade.php   # Batch ANOVA view (admin only)
```

(Note: Student-side uses existing `pages/student/assessment-take.blade.php` - don't create new view)

---

## 3. MODERATE ISSUES

### 3.1 ANOVA Calculation - Paired vs Unpaired
**Technical Note:** Original spec shows ANOVA, but statistically more precise would be **paired t-test** (same students pre/post)

**However:** For your use case, ANOVA is fine and easier to explain to teachers

**If you want to switch to paired t-test, use this formula:**
```php
// In MockBoardStatisticsService
public function computePairedTTest($preTestScores, $preBoardsScores)
{
    // Ensure same students in both arrays (paired data)
    $differences = array_map(function($pre, $post) {
        return $post - $pre;
    }, $preTestScores, $preBoardsScores);
    
    $meanDiff = array_sum($differences) / count($differences);
    $n = count($differences);
    
    // Standard deviation of differences
    $sumSquaredDiff = array_reduce($differences, function($carry, $diff) use ($meanDiff) {
        return $carry + pow($diff - $meanDiff, 2);
    }, 0);
    
    $stdDevDiff = sqrt($sumSquaredDiff / ($n - 1));
    
    // t-statistic
    $t = $meanDiff / ($stdDevDiff / sqrt($n));
    $df = $n - 1;
    
    // p-value lookup (use external library or approximation)
    // For now, use ANOVA approach as spec'd
    
    return [
        'mean_difference' => $meanDiff,
        't_statistic' => $t,
        'degrees_of_freedom' => $df,
    ];
}
```

**Recommendation:** Stick with original ANOVA approach (simpler, sufficient for educational context)

---

### 3.2 Estimated Hours Correction
**Original estimate:** 22-28 hours  
**Realistic estimate:** 35-45 hours

**Breakdown:**
- Phase 1 (Database): 2-3 hours
- Phase 2 (Models): 2-3 hours
- Phase 3 (Services): 5-7 hours (ANOVA calculations)
- Phase 4 (Teacher UI + AI generation): 8-10 hours
- Phase 5 (Student UI + anti-cheat): 5-7 hours
- Phase 6 (Class analytics): 4-6 hours
- Phase 7 (Batch analytics): 3-5 hours
- Phase 8 (Testing + fixes): 6-8 hours
- **Total: 35-45 hours**

**Schedule accordingly.**

---

## 4. VERIFICATION CHECKLIST

Before implementation, verify these exist in codebase:

### 4.1 AI Integration
- [ ] `App\Services\CloudflareAI` service exists
- [ ] `AiSettingsResolver` or similar config service exists
- [ ] `smalot/pdfparser` in composer.json for PDF parsing

### 4.2 Existing Patterns
- [ ] `assessment-take.blade.php` uses anti-cheat logic (lines 495, 593-601, 1156-1169)
- [ ] `modules.blade.php` has `isFormalAssessment` variable
- [ ] Module visibility uses `visible_to` JSON column

### 4.3 Database
- [ ] `modules` table has `is_formal_assessment` column
- [ ] `quiz_attempts` table has `attempt_count` column
- [ ] `classes` table exists and has `teacher_id` 

---

## 5. IMPLEMENTATION PRIORITY

**Do this order:**

1. **First:** Add authorization gates (AuthServiceProvider)
2. **Second:** Create migrations (database layer with visibility columns)
3. **Third:** Create models and relationships
4. **Fourth:** Create MockBoardStatisticsService (ANOVA calculations)
5. **Fifth:** Create MockBoardController (teacher management)
6. **Sixth:** Create StudentMockBoardController (student views)
7. **Seventh:** Add tab to modules drawer (teacher UI)
8. **Eighth:** Integrate with assessment-take.blade.php (student taking test)
9. **Ninth:** Create BatchAnalyticsController and views
10. **Tenth:** Test authorization, anti-cheat, ANOVA calculations

---

## 6. KEY CODE PATTERNS

### 6.1 Visibility Check Pattern
```php
// In StudentMockBoardController
$mockBoard->load('class.students');
if (!$mockBoard->isVisibleTo(auth()->user())) {
    abort(403, 'You do not have access to this Mock Board');
}
```

### 6.2 Anti-Cheat Requirement
```php
// When creating module for Mock Board phase
'is_formal_assessment' => true, // ⚠️ ALWAYS TRUE
```

### 6.3 Authorization Check
```php
// In MockBoardController methods
if (!auth()->user()->can('manage-mock-board', $mockBoard)) {
    abort(403);
}
```

### 6.4 Batch Analytics Authorization
```php
// In BatchAnalyticsController
if (!auth()->user()->can('view-batch-analytics')) {
    abort(403);
}
```

---

## 7. CRITICAL REMINDERS FOR AI AGENT

1. **MUST set `is_formal_assessment = 1`** when creating Mock Board modules (enables anti-cheat)
2. **MUST add visibility columns** to mock_boards table and implement visibility logic
3. **MUST add authorization gates** to AuthServiceProvider
4. **REUSE assessment-take.blade.php** - don't create new quiz taking view
5. **Use `/mock-boards/batch-analytics`** route (not `/batch-analytics/mock-boards`)
6. **Add visibility picker** to teacher interface (like existing modules)
7. **Test anti-cheat works** for Mock Board modules (tab switching should trigger warnings)
8. **Verify ANOVA calculations** with sample data before deployment

---

**End of Corrections Document**
