# Reviso — Verified Fix Plan (2026-08-18)

**Verification Status:** All issues from `reviso-remaining-tasks.md` confirmed against `origin/main` commit `84c70cca`.

---

## Issues Confirmed

### 🔴 CRITICAL — Must fix before anything else

#### 1. **max_attempts not in Module fillable** (Backend Bug)
- **File:** `app/Models/Module.php`
- **Problem:** `Module::$fillable` missing `'max_attempts'` → Laravel mass assignment silently drops it
- **Impact:** Teacher sets limit via `QuizController::updateMaxAttempts()` → endpoint returns success → limit never persists
- **Fix:** Add `'max_attempts'` to fillable array and `'max_attempts' => 'integer'` to casts
- **Verification:** After fix, confirm `modules.max_attempts` column exists in DB (migration `add_max_attempts_to_modules_table.php` exists but may not have run)

---

### 🟡 HIGH — Needed for attempt limits to work end-to-end

#### 2. **Attempt limits UI not implemented (3-part bug)**

**Part A: Student assessment list doesn't show attempts**
- **File:** `resources/views/pages/student/assessment.blade.php`
- **Problem:**
  - Shows disabled "View Results" chip after ANY attempt (no retake button even if attempts remain)
  - No display of attempts used / attempts allowed
  - No context-aware button logic
- **Fix:** 
  - Load `max_attempts` + grant count in `StudentAssessmentController::index()`
  - Replace button logic with state machine:
    - No attempt, not overdue → **Take Assessment**
    - Attempted, attempts remaining, not overdue → **Retake (2 of 3)**
    - Attempted, no attempts left → **Attempts Used Up** (disabled)
    - Overdue → **Past Due**
  - Add attempts chip showing `X / Y` used

**Part B: Controller missing attempt data**
- **File:** `app/Http/Controllers/StudentAssessmentController.php` method `index()`
- **Problem:** Only loads `.first()` attempt, no max_attempts or grant info
- **Fix:** Eager-load `attemptGrants` (one query, no N+1), compute `attempts_used` + `attempts_allowed` per module

**Part C: Take screen hides attempt info before block**
- **File:** `resources/views/pages/student/assessment-take.blade.php` (start card)
- **Problem:** Doesn't show "Attempt 2 of 3" before student clicks Begin → surprise 403 after they commit time
- **Fix:** Pass `attempts_used` / `attempts_allowed` from controller, add one more stat chip at start

---

### 🟡 MEDIUM — Behavioral & copy issues

#### 3. **Warning copy mismatch: says "2 warnings", code enforces "3 warnings"**
- **Files:** 
  - `resources/views/pages/student/assessment-take.blade.php:465` (says "warned twice")
  - `resources/views/pages/student/assessment-take.blade.php:1233` (code: `if (warningCount >= 4)`)
- **Problem:** warningCount starts 0, increments per tab-switch → student gets 3 warnings before auto-fail, not 2
- **Fix:** Pick a policy:
  - warning lang wag mo ipakita kung ilang warning

#### 4. **AI Quiz Generation (question_type & difficulty) — untested LLM behavior**
- **Files:** `app/Http/Controllers/ClassManagerController.php` (generation path)
- **Status:** Code wired correctly (schema enforces what/why/how, difficulty per-question)
- **Problem:** LLM has been wrong twice before; no browser test yet to confirm:
  - Questions actually vary between what/why/how (not defaulted to "what")
  - Difficulty varies per question (not all stamped with same value)
  - Non-default choice counts (5 options) work correctly
- **Fix:** Generate a real batch through UI, inspect JSON output, verify distribution
- **Note:** `question_type` not persisted to DB (no column, no fillable) — this is intentional (generation-time only)

---

### 🟡 LOW — Cleanup, debt

#### 5. **storeQuizManual() hardcodes Normal difficulty**
- **File:** `app/Http/Controllers/ClassManagerController.php:1725`
- **Problem:** Manual quiz creation always sets `'difficulty' => 'Normal'` (inconsistent with AI path which accepts difficulty parameter)
- **Fix:** Add difficulty input to manual quiz form, pass to storeQuizManual

#### 6. **Dead routes (2 missing methods)**
- `routes/web.php:648` → `quiz.attempts.reset` points to `QuizController::resetAttempts()` (method doesn't exist)
- `routes/web.php:725` → `student.preassessments` points to `StudentAssessmentController::preassessments()` (method doesn't exist)
- **Fix:** Either implement the methods or remove the routes

#### 7. **MockBoardAnalyticsController is dead code**
- **Status:** 
  - `BatchAnalyticsController` IS routed (`routes/web.php:698-700`, `mock-boards.batch.*`)
  - `MockBoardAnalyticsController` is NOT routed anywhere → dead class
  - `MockBoardController::classAnalysis()` (line ~436-445) is also dead/unrouted
- **Problem:** 
  - `MockBoardController::classAnalysis()` calls `redirect()->route('admin.mock-board-analytics')`
  - Route named `'admin.mock-board-analytics'` does NOT exist
  - If `classAnalysis()` is ever called, will throw `RouteNotFoundException` (500)
- **Fix:** Either:
  - **(a) Delete as unused:** Remove `MockBoardAnalyticsController`, `MockBoardController::classAnalysis()` method entirely
  - **(b) If meant to be used:** Create missing `'admin.mock-board-analytics'` route, wire `classAnalysis()` properly to `MockBoardAnalyticsController`
  - **Decision:** Which path? (recommend A — seems abandoned)

#### 8. **Board Exam Models incomplete**
- **Status:** 5 migrations exist (topics, subtopics, files, progress) but no models
- **Files missing:** `BoardExamTopic`, `BoardExamSubtopic`, `BoardExamSubtopicFile` models
- **Impact:** `TeacherBoardExamModuleController.php` type-hints non-existent models
- **Fix:** Either complete the implementation (models + routes) or remove the controller

---

## Implementation Order

### Phase 1: Unblock attempt limits (critical path)
1. **Fix max_attempts fillable** (5 mins)
   - Add to Module model
   - Verify migration ran
   - Test with curl/form
2. **Implement student assessment list UI** (30 mins)
   - Controller: eager-load data
   - Blade: state machine buttons + chips
3. **Implement take screen attempt info** (15 mins)
   - Pass data, render chip
4. **Feature test & browser click-test** (20 mins)
   - Burn attempt → retake flow
   - Grant logic
   - Overdue state

### Phase 2: Copy & logic fixes (quick wins)
5. **Warning copy fix** (2 mins)
   - Pick policy, change 1 line

6. **Manual quiz difficulty form** (10 mins)
   - Add difficulty input + pass to controller

### Phase 3: Verification & cleanup (deferred)
7. **Generate test AI quiz batch** (15 mins, deferred until Phase 1 passes)
8. **Dead routes audit** (15 mins)
9. **BoardExam & BatchAnalytics audit** (30 mins)

---

## Structural Change (needs your approval)

To make attempt limits display & enforcement never drift apart, move `getAllowedAttempts()` logic to model:

```php
// app/Models/Module.php
public function attemptGrants(): HasMany {
    return $this->hasMany(AssessmentAttemptGrant::class);
}

public function allowedAttemptsFor(int $userId): int {
    $grants = $this->attemptGrants()
        ->where('user_id', $userId)
        ->sum('additional_attempts') ?? 0;
    return $this->max_attempts + $grants;
}
```

Then `QuizController::getAllowedAttempts()`, `ClassManagerController`, and student UI all delegate to it — single source of truth.

**Decision:** OK to add this, or keep current duplicate logic?

---

## Decisions Needed

1. **Warning policy:** 2 warnings or 3? (change copy or code?)
2. **Structural refactor:** Move `getAllowedAttempts()` to model?
3. **Manual quiz difficulty:** Add to form?
4. **Board Exam Modules:** Complete or remove?
5. **Dead routes:** Implement or delete?
6. **BatchAnalytics audit:** Merge or clarify?

---

## Commit Strategy

- Phase 1 fixes: **single commit** ("Fix: attempt limits persistence + UI")
- Phase 2 fixes: **single commit** ("Fix: warning copy, manual quiz difficulty")
- Phase 3: **separate commits** per audit result

---

**Approval needed on Phase 1 approach + decisions before coding starts.**
