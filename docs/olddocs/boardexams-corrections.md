# BOARDEXAMS.md Corrections for Reviso LMS

**Date:** May 10, 2026  
**Purpose:** Corrections and clarifications to the Mock Boards spec based on actual Reviso codebase

---

## 1. CRITICAL: Login/Signup Flow (Not in Original Spec)

**Issue:** The current default landing page is signup (`/`), but it should be login.

**Current State (web.php lines 63-88):**
```php
Route::get('/signup', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('signup');
})->name('signup')->middleware('guest');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('login');  // This is correct
})->name('home');
```

**Correction:** The root route already shows login. The issue is that `/signup` is accessible directly. No changes needed to routes, but ensure login page has a link to signup.

---

## 2. TECH STACK CORRECTIONS

### 2.1 PHP Version
**Spec says:** PHP 8.2+  
**Actual:** PHP 8.5 (per AGENTS.md)

### 2.2 Laravel Version
**Spec says:** Laravel v12  
**Actual:** Laravel v12 (correct)

### 2.3 Livewire Version
**Spec doesn't mention:** Livewire v4 is installed and actively used

**Impact:** Mock Board interfaces could leverage Livewire components for real-time updates (optional but recommended for phase management)

---

## 3. DATABASE CORRECTIONS

### 3.1 Table Naming Convention
**Issue:** Spec uses snake_case table names which is correct, but migrations should follow Laravel conventions.

**Correction:** Migration filenames should be:
```
2026_05_10_000001_create_mock_boards_table.php
2026_05_10_000002_create_mock_board_phases_table.php
2026_05_10_000003_create_mock_board_attempts_table.php
2026_05_10_000004_create_mock_board_statistics_table.php
```

### 3.2 JSON Column for question_ids
**Spec says:** `question_ids JSON`  
**Correction:** In MySQL, JSON type is fine. But for older versions, use `text` with JSON casting in model.

```php
// In model
protected $casts = [
    'question_ids' => 'array',
];
```

### 3.3 Missing: Visibility for Mock Boards
**Spec mentions:** Visibility picker (all/selected/except students)  
**Correction:** Need to add `visibility` and `visible_to` columns to mock_boards table, or reuse module visibility pattern.

**Recommended addition:**
```sql
ALTER TABLE mock_boards ADD COLUMN visibility ENUM('all', 'selected', 'except') DEFAULT 'all';
ALTER TABLE mock_boards ADD COLUMN visible_to JSON NULL; -- array of user_ids when visibility != 'all'
```

---

## 4. ANTI-CHEAT IMPLEMENTATION (Critical Fix)

### 4.1 The Problem
**Spec says:** "Both are graded and monitored (anti-cheat enabled)"  
**Actual Implementation:** We just fixed anti-cheat to be DISABLED for pre-assessments (`is_formal_assessment = 0`)

**Correction:** Mock Boards MUST set `is_formal_assessment = 1` when creating linked modules to ensure anti-cheat works.

**In MockBoardController:**
```php
// When creating quiz module for phase
$module = Module::create([
    'title' => $phaseTitle,
    'is_quiz' => true,
    'is_formal_assessment' => true, // REQUIRED for anti-cheat
    'is_mock_board' => true, // Flag for identification
    'passing_percentage' => $mockBoard->passing_percentage,
    // ... other fields
]);
```

### 4.2 Anti-Cheat Variables
**Spec doesn't mention:** The JavaScript variables needed for anti-cheat to work properly.

**Required in assessment-take.blade.php and modules.blade.php:**
```javascript
// Must be set when quiz loads
var isFormalAssessment = {{ $module->is_formal_assessment ? 'true' : 'false' }};

// Debounce protection
let lastWarningTime = 0;
```

**Note:** This was recently implemented (lines 433, 1034, 1131-1144 in modules.blade.php)

---

## 5. ROUTE STRUCTURE CORRECTIONS

### 5.1 Batch Analytics Routes May Conflict
**Spec suggests:** `/batch-analytics/mock-boards`  
**Issue:** Could conflict with future batch-related features.

**Recommended:** Use `/mock-boards/batch-analytics` instead to keep namespace consistent.

**Corrected Routes:**
```php
// Student routes (keep as spec'd)
Route::prefix('mock-boards')->middleware('auth')->group(function () {
    Route::get('/', [StudentMockBoardController::class, 'dashboard']);
    Route::get('{mock_board}', [StudentMockBoardController::class, 'show']);
    // ... etc
});

// Teacher management routes (keep as spec'd)
Route::post('classes/{class}/mock-boards', [MockBoardController::class, 'store']);

// CORRECTED: Batch analytics under mock-boards namespace
Route::prefix('mock-boards/batch-analytics')->middleware(['auth', 'can:view-batch-analytics'])->group(function () {
    Route::get('/', [BatchAnalyticsController::class, 'dashboard']);
    Route::get('{program}/{mock_board}', [BatchAnalyticsController::class, 'mockBoardsAnalysis']);
    Route::post('{program}/{mock_board}/compute-anova', [BatchAnalyticsController::class, 'computeANOVA']);
});
```

### 5.2 Route Naming Convention
**Spec uses:** `mock-boards.{action}`  
**Correction:** Use resourceful naming:
```php
Route::resource('mock-boards', MockBoardController::class)->except(['index', 'create']);
Route::post('mock-boards/{mock_board}/phases', [MockBoardController::class, 'updatePhases'])->name('mock-boards.phases');
```

---

## 6. CONTROLLER ARCHITECTURE CORRECTIONS

### 6.1 Controller Names
**Spec suggests:** `MockBoardController`, `StudentMockBoardController`, `BatchAnalyticsController`  
**Correction:** Consider consolidating to reduce code duplication.

**Recommended:**
- `MockBoardController` - Teacher management only
- `StudentMockBoardController` - Student views only  
- `MockBoardAnalyticsController` - Analytics for both class and batch level (use methods to differentiate)

Or use single controller with clear method naming:
```php
class MockBoardController extends Controller
{
    // Teacher methods
    public function index(ClassModel $class) { }
    public function store(ClassModel $class, Request $request) { }
    public function update(MockBoard $mockBoard, Request $request) { }
    public function destroy(MockBoard $mockBoard) { }
    
    // Student methods
    public function studentDashboard() { }
    public function take(MockBoard $mockBoard, string $phase) { }
    public function submit(MockBoard $mockBoard, string $phase, Request $request) { }
    
    // Analytics methods
    public function classAnalysis(MockBoard $mockBoard) { }
    public function studentAnalysis(MockBoard $mockBoard, User $student) { }
    public function batchAnalytics() { }
    public function batchAnalysis(string $program, MockBoard $mockBoard) { }
}
```

### 6.2 Missing: Authorization Gates
**Spec mentions:** Gate `can-view-batch-analytics`  
**Correction:** Also need gates for managing Mock Boards.

**Required Gates:**
```php
// In AuthServiceProvider
Gate::define('manage-mock-board', function (User $user, MockBoard $mockBoard) {
    return $user->id === $mockBoard->teacher_id 
        || $user->role === 'admin' 
        || $user->role === 'superadmin';
});

Gate::define('view-mock-board', function (User $user, MockBoard $mockBoard) {
    // Check if student is enrolled in the class
    if ($user->role === 'student') {
        return $mockBoard->class->students->contains($user);
    }
    return true; // Teachers/admins can view
});

Gate::define('view-batch-analytics', function (User $user) {
    return in_array($user->role, ['admin', 'superadmin']) 
        || in_array($user->track, ['psych', 'educ', 'accountancy']);
});
```

---

## 7. VIEW STRUCTURE CORRECTIONS

### 7.1 View Folder Organization
**Spec suggests:** `teacher/batch-analytics-dashboard.blade.php`  
**Correction:** Follow existing Reviso pattern.

**Existing Pattern:**
```
resources/views/
  pages/
    teacher/
      class-management.blade.php
      student-analysis.blade.php
    student/
      modules.blade.php
      assessment-take.blade.php
```

**Recommended:**
```
resources/views/
  pages/
    teacher/
      mock-boards/           
        index.blade.php           # List for class
        edit.blade.php            # Create/edit form
        student-analysis.blade.php # Per-student view
        class-analysis.blade.php   # Class-level ANOVA view
        batch-dashboard.blade.php  # Batch list
        batch-analysis.blade.php   # Batch ANOVA view
    student/
      mock-boards/
        dashboard.blade.php       # Student dashboard
        take.blade.php            # Taking assessment (or reuse assessment-take)
```

### 7.2 Reuse assessment-take.blade.php
**Spec suggests:** Create `mock-board-take.blade.php`  
**Correction:** Should REUSE existing `assessment-take.blade.php` with conditional logic.

**Why:** We just fixed anti-cheat bugs in assessment-take.blade.php. Duplicating means double maintenance.

**Implementation:**
```php
// In StudentMockBoardController::take()
$module = $mockBoardPhase->module;
return view('pages.student.assessment-take', [
    'module' => $module,
    'isMockBoard' => true,
    'mockBoard' => $mockBoard,
    'phase' => $phaseType, // 'pre_test' or 'pre_boards'
]);
```

Then in `assessment-take.blade.php`:
```blade
@if($isMockBoard ?? false)
    <div class="mock-board-header">
        <h3>{{ $mockBoard->title }} - {{ $phase === 'pre_test' ? 'Pre-Test' : 'Pre-Boards' }}</h3>
        <p>Passing: {{ $mockBoard->passing_percentage }}%</p>
    </div>
@endif
```

---

## 8. AI INTEGRATION CORRECTIONS

### 8.1 AI Service Class Name
**Spec mentions:** `CloudflareAI` service  
**Correction:** Verify actual service name in codebase.

**Check for:**
- `App\Services\CloudflareAI`
- `App\Services\AI\CloudflareAIService`
- Or similar naming pattern

### 8.2 AI Settings Resolver
**Spec mentions:** `AiSettingsResolver`  
**Correction:** Verify this exists or if it should be `AiSettingsController` or config-based.

**Check:** `config/ai-settings.php` or database table for AI feature flags.

### 8.3 PDF Parser
**Spec mentions:** `smalot/pdfparser`  
**Correction:** Verify package is installed in composer.json.

**Check:** `composer.json` for `"smalot/pdfparser": "^2.0"` or similar.

---

## 9. ANOVA CALCULATION CORRECTIONS

### 9.1 Statistical Library
**Spec suggests:** Manual calculation or `statslibphp/stats`  
**Correction:** Consider using existing PHP statistical packages.

**Recommended:** Use `draino/stats` or similar for accurate p-value calculations.

**Installation:**
```bash
composer require draino/stats
```

**Alternative:** Use ANOVA formula directly (as spec'd) but with proper F-distribution lookup.

### 9.2 Paired vs Unpaired ANOVA
**Spec shows:** Paired t-test style (same students pre/post)  
**Correction:** This should be **paired t-test**, not ANOVA, since we're comparing the same students at two time points.

**Correction:**
```php
// Paired t-test is more appropriate
// H0: mean difference = 0
// H1: mean difference ≠ 0

// Formula:
// t = mean(differences) / (std_dev(differences) / sqrt(n))
// df = n - 1
```

**If sticking with ANOVA:** Use repeated measures ANOVA (within-subjects), not between-subjects.

---

## 10. EXISTING CODE PATTERNS TO FOLLOW

### 10.1 Module Visibility
**Existing pattern in ClassManagerController:**
```php
// Check visibility before serving module
if ($module->visibility === 'selected' && !in_array($user->id, $module->visible_to ?? [])) {
    abort(403);
}
```

**Mock Boards should follow same pattern.**

### 10.2 Assessment Result Display
**Existing pattern:** `modules.blade.php` lines 950-990 shows result with gauge.

**Mock Boards should reuse this exact pattern for consistency.**

### 10.3 AI Insights Caching
**Existing pattern:** Cached in `quiz_attempts` table (ai_strong, ai_weak, ai_recommendation).

**Mock Boards should store in `mock_board_attempts` table with same columns.**

---

## 11. TIMELINE CORRECTIONS

### 11.1 Estimated Hours
**Spec estimates:** 22-28 hours  
**Correction:** With testing, debugging, and integration, estimate 35-45 hours.

**Breakdown:**
- Phase 1 (Database): 2-3 hours
- Phase 2 (Models): 2-3 hours  
- Phase 3 (Services): 4-6 hours (ANOVA is complex)
- Phase 4 (Teacher UI): 8-10 hours
- Phase 5 (Student UI): 4-6 hours
- Phase 6 (Analytics): 6-8 hours
- Phase 7 (Testing): 6-8 hours
- **Total: 32-44 hours**

### 11.2 Critical Path
**Order matters:**
1. Database first (blocks everything)
2. Models second (blocks controllers)
3. Teacher interface third (needed to create test data)
4. Student interface fourth (depends on teacher-created data)
5. Analytics last (depends on attempts existing)

---

## 12. TESTING REQUIREMENTS NOT IN SPEC

### 12.1 Test Cases to Add
**Not mentioned in spec:**
- Test that anti-cheat works for Mock Board formal assessments
- Test that pre-test allows multiple attempts
- Test that pre-boards allows only one attempt
- Test batch analytics authorization (role-based access)
- Test AI question generation with various file types
- Test "use same questions" checkbox functionality

### 12.2 Factory/Seeder Data
**Add to DatabaseSeeder:**
```php
MockBoard::factory()->count(3)->create();
MockBoardPhase::factory()->count(6)->create(); // 2 per board
MockBoardAttempt::factory()->count(50)->create();
```

---

## 13. DEPLOYMENT CHECKLIST ADDITIONS

**Additional items not in spec:**
- [ ] Verify anti-cheat works in production (test tab switching)
- [ ] Test AI generation with production file sizes
- [ ] Verify ANOVA calculations with real student data
- [ ] Check batch analytics performance with large datasets
- [ ] Ensure proper error handling for failed AI generation

---

## 14. SUMMARY OF CRITICAL FIXES

| Issue | Severity | Fix Required |
|-------|----------|--------------|
| Anti-cheat flag not mentioned | **HIGH** | Set `is_formal_assessment = 1` for Mock Board modules |
| ANOVA should be paired t-test | **MEDIUM** | Use paired t-test formula instead |
| Route naming conflict | **MEDIUM** | Use `/mock-boards/batch-analytics` |
| View reuse not emphasized | **MEDIUM** | Reuse assessment-take.blade.php |
| Missing authorization gates | **HIGH** | Add gates for manage/view mock boards |
| Visibility columns missing | **MEDIUM** | Add to mock_boards table |
| PHP version mismatch | **LOW** | Update spec to PHP 8.5 |
| Estimated hours too low | **LOW** | Plan for 35-45 hours |

---

**End of Corrections Document**
