# Plan — Show/apply "attempts taken" on the student assessment side

Scope: the student-facing assessment list (`/assessment`) and the take screen (`/assessment/{module}`). Read-only investigation done against `origin/main` (`84c70cca`). **No code changed yet — this is the plan for your approval.**

---

## 1. What I found (why it looks "not applied")

There are **three separate causes**, and only one of them is a UI problem. Fixing the UI alone will not make it work.

### Cause A — the teacher's base limit is silently discarded (backend bug, most important)

`QuizController::updateMaxAttempts()` (line 330) does:

```php
$module->update(['max_attempts' => $validated['max_attempts']]);
```

but `max_attempts` is **not** in `Module::$fillable`:

```php
protected $fillable = [
    'class_id', 'title', 'description', 'file_path', 'file_type', 'order',
    'is_quiz', 'is_assignment', 'is_formal_assessment', 'time_limit', 'passing_grade', 'visibility',
    'created_by', 'is_mock_board', 'due_date',
];
```

Laravel mass assignment silently drops non-fillable keys, so the update is a **no-op**. The endpoint still returns `success: true` and echoes `$module->max_attempts` (the *old* value), so the teacher UI looks like it saved. Every module therefore stays at the default `max_attempts = 1`, no matter what the teacher types. `getAllowedAttempts()` then always computes `1 + grants`.

This alone makes the whole attempt-limit feature look broken/unapplied even though the enforcement logic is correct.

**Also verify the column actually exists in your DB:** the migration file is `database/migrations/add_max_attempts_to_modules_table.php` — it has **no timestamp prefix**, unlike every other migration. It will still run (last, alphabetically), but it is easy for it to have been missed on a database that was migrated before the file was added. If the column is missing, `$module->max_attempts` reads as `null` → treated as 1, and the update would throw. Worth a one-line check before anything else.

### Cause B — the student list never shows attempts, and blocks retakes outright

`resources/views/pages/student/assessment.blade.php`:

```blade
@if($attempt !== null)
    <span class="as-btn disabled"><i class="fas fa-check"></i> View Results</span>
@elseif($assessment->isOverdue())
    ...
@else
    <a href="{{ route('assessment.take', $assessment) }}" class="as-btn">Take Assessment</a>
@endif
```

So the moment a student has **any** attempt row, the card turns into a **disabled** "View Results" chip. Even if the teacher allowed 3 attempts or granted an extra one, the student has no way to start attempt #2 from this page. Nothing on the card shows how many attempts were used or remain. ("View Results" is also not a link — it goes nowhere.)

`StudentAssessmentController::index()` loads only:

```php
$module->student_attempt = QuizAttempt::query()
    ->where('user_id', $user->id)->where('module_id', $module->id)
    ->orderByDesc('percentage')->first();
```

No `max_attempts`, no grant, so the view has nothing to display even if we wanted to.

### Cause C — the take screen only reveals attempts *after* it blocks you

`assessment-take.blade.php`'s start screen lists question count, time limit, and the anti-cheat notice — no attempt info. The numbers appear only inside `showBlockedState()` (line 616), i.e. after the student clicks "Begin Assessment" and gets the 403. The student cannot see "Attempt 2 of 3" before committing.

Note the data model: there is **one** `quiz_attempts` row per (user, module), and `attempt_count` is a counter on it that `startAttempt()` increments; each retake wipes the previous answers. So "attempts taken" = `attempt.attempt_count`, not a row count.

---

## 2. Proposed fix

### Step 1 — make the base limit actually persist (backend)

- Add `'max_attempts'` to `Module::$fillable`, and `'max_attempts' => 'integer'` to `$casts` for consistency with `passing_grade`.
- Confirm the `modules.max_attempts` column exists (`php artisan migrate --pretend` / `database-schema`); run the migration if it never ran.
- After this, `updateMaxAttempts()` works unchanged.

### Step 2 — expose the numbers in one reusable place (backend)

`getAllowedAttempts()` is currently `private` in `QuizController` and duplicated as inline arithmetic in `ClassManagerController` and `PerformanceController` (`$module->max_attempts + grant`). Instead of adding a fourth copy for the student side, move it onto the model:

```php
// app/Models/Module.php
public function attemptGrants(): HasMany;                       // AssessmentAttemptGrant
public function allowedAttemptsFor(int $userId): int;           // base + granted
```

Then `QuizController::getAllowedAttempts()` delegates to it, so enforcement and display can never drift apart. This is the one structural change I'd like your OK on — it touches an existing controller method, though the behavior stays identical.

### Step 3 — student assessment list (`assessment.blade.php` + `StudentAssessmentController::index`)

- In `index()`, eager-load the grants (single query for all modules, no N+1) and attach `attempts_used` (`$attempt?->attempt_count ?? 0`) and `attempts_allowed` per module.
- On each card, add a stat chip next to the existing time/question chips:
  - not started → `Attempts: 0 / 3`
  - used some → `Attempts: 1 / 3`
  - exhausted → `Attempts: 3 / 3` styled as locked (same red treatment as the overdue chip)
- Replace the button logic so the CTA reflects the real state:

| State | Button |
|---|---|
| No attempt, not overdue | **Take Assessment** (unchanged) |
| Attempted, attempts remaining, not overdue | **Retake (2 of 3)** → `assessment.take` |
| Attempted, no attempts left | **Attempts Used Up** (disabled) + last score chip |
| Overdue | **Past Due** (unchanged, wins over everything) |
| In progress (`status = 'in_progress'`) | **Resume** — no attempt is consumed by resuming |

The existing score pill and `%` chip stay as they are.

### Step 4 — take screen start card (`assessment-take.blade.php`)

- Pass `attempts_used` / `attempts_allowed` from `StudentAssessmentController::take()` into the view.
- Add one more `at-start-stat` chip: `Attempt 2 of 3` (or `Last attempt` when it's the final one, in a warning colour) so the student knows the cost **before** clicking Begin.
- Leave `launchAssessment()` / `showBlockedState()` untouched — they already work; the server stays the single source of truth, and the new display is advisory only.

### Step 5 — verification

- Feature test: student with `max_attempts = 2` sees a retake CTA after attempt 1, and a locked CTA after attempt 2; a teacher grant of +1 makes the CTA available again. Add a regression test that `updateMaxAttempts()` genuinely persists (this is the bug from Step 1).
- Run only the related tests via `--filter`, then Pint on touched PHP files.
- Manual browser pass: exhaust attempts → verify list chip, blocked screen, then grant +1 and confirm the student can retake.

---

## 3. Decisions I need from you

1. **Practice (non-formal) modules** — `startAttempt()` only enforces limits when `is_formal_assessment = true`. The `/assessment` page lists only formal assessments, so this plan doesn't change practice modules. OK?
2. **Retake wording/policy** — retaking wipes the previous answers and resets the score (existing `startAttempt()` behavior). Should the card warn "Retaking will replace your previous score", or stay silent?
3. **"View Results"** — currently a dead disabled chip. Leave it as-is, or wire it to a real results page in the same change?
4. **Mock Board phases** — they share `assessment-take.blade.php`, so the Step 4 chip would show there too. Want it there, or suppressed for mock boards?

## 4. Unrelated things I noticed while reading (not in scope, flagging only)

- `routes/web.php:725` → `student.preassessments` points at `StudentAssessmentController::preassessments`, which **does not exist** — a second dead route alongside `quiz.attempts.reset` (line 648).
- `updateMaxAttempts()` and `grantExtraAttempt()` validate inline; repo convention (AGENTS.md) asks for Form Requests. Not touching them unless you want the cleanup.
