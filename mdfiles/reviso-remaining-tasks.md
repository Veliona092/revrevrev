# Reviso — Remaining Tasks (verified)

Checked against `origin/main` of `Veliona092/revrevrev`, commit `84c70cca` ("Initial commit", 2026-08-18), re-fetched just now. **No new commits have been pushed since my last check.** No code was changed to produce this file.

Verdict on your list: **items 2–5 are all confirmed true. Item 1 I cannot confirm** — see below.

---

## 1. pdfjs-viewer fix — marked done ❓ CANNOT VERIFY

On the pushed `main` this is **still broken**. Nothing has been pushed since I reported it, so if you fixed it, the fix is local-only.

Current state on `main`:

- `app/Http/Controllers/ClassManagerController.php:1026`
  ```php
  return view('pages.student.pdfjs-viewer', compact('pdfUrl', 'module'));
  ```
- `resources/views/pages/student/pdfjs-viewer.blade.php` needs a `$file`, not a `$module`:
  ```blade
  6:  <title>Preview: {{ $file->original_name ?? 'Document' }}</title>
  59: const fileId = {{ (int) $file->id }};
  60: const subtopicId = {{ (int) $file->board_exam_subtopic_id }};
  ```

So on `main`, opening a PDF/PPT module still throws `Undefined variable $file`.

Second half of that bug, in case your fix only covered the variable: the viewer posts `type: 'board-exam-pdf-progress'` while the listener in `modules.blade.php:721` only accepts `'pdf-scroll-progress'`. If those two strings still disagree, the viewer renders fine but **no scroll progress is ever saved** — which looks like "working" until you check the DB.

**Action:** push your fix (or tell me and I'll verify the local working tree), and confirm both the `$file`/`$module` mismatch *and* the message-type mismatch are covered.

---

## 2. Task A — warning copy ✅ TRUE, still not applied

Approach may be agreed, but nothing in the file changed:

- `resources/views/pages/student/assessment-take.blade.php:465` — "You will be warned twice before the quiz is auto-submitted as failed."
- `resources/views/pages/student/assessment-take.blade.php:1233` — `if (warningCount >= 4) {`

`warningCount` starts at 0 and increments per tab-switch, so the student actually gets **3 warnings** before auto-fail, not 2. Copy and code still disagree. One-line fix either way, but you have to pick which number is the real policy.

---

## 3. Manual test of what/why/how + per-question difficulty ✅ TRUE, still the top priority

The enforcement code is all there and correct on `main` (`ClassManagerController.php`): quota computed at 1439-1440, stated in the prompt at 1452, `question_type` enum-locked and `required` in the JSON schema at 1499-1504, system message reinforcing it at 1469, per-question `difficulty` read back in the save loop.

But this is generation-time behavior of an LLM — **code review cannot prove it**, and it has already been wrong twice while looking right on paper. It needs a real batch generated through the UI, then eyeballing the actual distribution:

- Roughly half or fewer questions phrased as "what", the rest genuinely "why"/"how".
- Difficulty varying per question, not all stamped with the same value.
- Try a non-default choice count (e.g. 5 options) too — the old hardcoded `count($q['options']) === 4` filter used to silently drop every question in that case. The fix is in, but it has never been exercised.

---

## 4. Manual click-test of Task B/C ✅ TRUE, code-confirmed only

Everything is wired on `main` — `getAllowedAttempts()` (130), `startAttempt()` enforcing at 174, `updateMaxAttempts()` (315), `grantExtraAttempt()` (343), routes at `web.php:736-737`, and the 403 branch in `assessment-take.blade.php` (`r.ok` check at 599-607, `showBlockedState()` at 616).

Never exercised in a browser. Worth testing in this order:

1. Student burns the base attempt → second launch should show the blocked screen with the real `attempts_used` / `attempts_allowed`, not silently start the quiz.
2. Teacher grants +1 from the Item Analysis dialog → student can launch again.
3. Teacher edits the base limit from the quiz-create page → takes effect without a grant.
4. Repeat step 1 on a **Mock Board phase**, since that path uses the same `assessment-take.blade.php`.

---

## 5. Low priority ✅ ALL TRUE

| Item | Evidence on `main` |
|---|---|
| `storeQuizManual()` still hardcodes Normal | `ClassManagerController.php:1705` → `'difficulty' => 'Normal',` |
| Dead `quiz.attempts.reset` route | `routes/web.php:648` points to `QuizController::resetAttempts` — zero matches for that method in the controller |
| `question_type` not persisted | zero matches for `question_type` in `database/migrations/` and `app/Models/` — generation-time only |
| `BatchAnalyticsController` duplicate | still present *and still routed* — `web.php:698-700` (`mock-boards.batch.dashboard`, `.analysis`, `.anova.compute`), separate from `MockBoardAnalyticsController`. So it is not dead code you can just delete; the routes have to be retired or repointed first. |

---

## Not on your list, but still open

**Board Exam Modules is not built on `main`.** The 5 migrations exist (`board_exam_topics`, `board_exam_subtopics`, `board_exam_subtopic_files`, `board_exam_subtopic_progress`, `board_exam_subtopic_file_progress`), but there are no matching models, no routes, and `TeacherBoardExamModuleController.php` type-hints `BoardExamMaterial` / `BoardExamMaterialFile`, which do not exist anywhere in the codebase. Your context dump lists it as "admin CRUD + student viewer built" — that is not true of what is pushed. Either that work was never committed, or the controller needs to be rewritten against the real migrations.

This also explains item 1: `pdfjs-viewer.blade.php` was clearly written *for* the board-exam feature, and got pointed at the class-module route instead.

---

## Suggested order

1. Push the pdfjs fix so it can actually be verified (and confirm the postMessage type matches).
2. Generate a real AI quiz batch through the UI — item 3, the only one that has burned you twice.
3. Browser click-test B/C — item 4.
4. Task A copy/threshold — one line, pick the number.
5. Decide the Board Exam Modules situation.
6. Sweep the low-priority four.
