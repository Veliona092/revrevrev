# Reviso AI Quiz Duplicate-Content Fix Report

## Scope

This report documents the fix for duplicate or echoed answer choices in AI-generated multiple-choice quizzes.

Affected flow:

```text
Teacher uploads context files
        -> generateQuizAi()
        -> validate AI response
        -> shuffle options
        -> save QuizQuestion records
```

## Original Problem

Some generated questions contained low-quality choices:

- Two or more answer options had the same text.
- An answer option repeated or closely restated the question stem.
- Duplicate option text could interfere with the option-shuffling logic and potentially associate the correct answer with the wrong duplicate occurrence.

The previous validation checked only the response shape:

- Question field exists.
- Options are an array.
- The option count is correct.
- The correct-answer letter is valid.

It did not check the content quality of the options.

## Root Cause

The Cloudflare Workers AI response was accepted after structural validation only. Content-level validation was missing from both:

1. The initial per-file, per-difficulty, per-question-type response.
2. The existing retry response used when the AI returned too few valid questions.

As a result, structurally valid but duplicated or stem-echoed questions could reach the final persistence transaction.

## Implemented Fix

### New helper

Added a private `isCleanAiQuestion()` helper in `ClassManagerController`.

The helper validates the following:

1. The candidate is an array with the required fields.
2. The question stem is a non-empty string.
3. The options array has the expected number of choices.
4. The correct-answer letter belongs to the allowed choice letters.
5. No option is empty after normalization.
6. No two options are duplicates after normalization.
7. No option is too similar to the question stem.

### Normalization

Before comparison, text is:

- Converted to lowercase.
- Trimmed.
- Reduced to single whitespace characters.

This catches differences such as capitalization or repeated spaces while avoiding unnecessary changes to the saved question text.

### Duplicate option rule

Normalized option values are compared with `array_unique()`.

Example rejected response:

```text
A: HTTPS
B: HTTPS
C: FTP
D: SMTP
```

### Stem-echo rule

Each normalized option is compared with the normalized question stem using PHP `similar_text()`.

Current threshold:

```text
similarity > 70 percent => reject the question
```

For very short question stems (fewer than 20 normalized characters), the similarity heuristic is skipped because `similar_text()` produces unreliable high percentages on short strings. Exact duplicate options are still rejected for short stems.

This is intentionally deterministic and adds no AI request.

## Integration Points

The helper is now applied to both AI response filters:

- The initial `$batch` filter.
- The `$retryBatch` filter.

Rejected questions reduce the valid batch count and reuse the existing retry mechanism. The whole quiz batch is not regenerated unnecessarily.

The following behavior remains unchanged:

- Per-file generation.
- Per-difficulty generation.
- Per-question-type generation (`what`, `why`, `how`).
- Response schema returned to the frontend.
- Option shuffling.
- Database transaction used to persist questions.
- Existing question ordering and difficulty assignment.

## Tests Added

Added `tests/Unit/AiQuizContentValidationTest.php` with three focused tests:

1. Duplicate options are rejected.
2. Stem-echo options are rejected.
3. Distinct options are accepted.

The tests invoke the private validator through PHP reflection so the content rules can be tested without an AI request or database setup.

## Validation Results

Focused unit tests:

```text
3 tests passed
```

Existing attempt-limit feature tests:

```text
11 tests passed
34 assertions passed
```

Additional checks:

```text
PHP syntax check passed
Laravel Blade cache passed
Laravel Pint passed
```

## Public Behavior

The frontend response shape was not changed. Successful generation still returns:

```json
{
  "success": true,
  "message": "...",
  "questions": []
}
```

The difference is that low-quality candidates are filtered before persistence. If the first AI response contains rejected items, the existing retry path attempts to fill the missing count.

## Known Limitations

The current stem-echo check uses `similar_text()`. It catches exact and close restatements for normal-length stems, but it may not catch every paraphrased echo with reordered words. Short stems intentionally bypass the similarity check to avoid false positives.

The current implementation does not add an additional AI self-critique call. This keeps the generation request count unchanged and avoids increasing teacher wait time unnecessarily.

The report's original estimate of nine bad questions was based on manual review. No historical batch audit was run, so an exact before/after reduction rate is not available yet.

## Recommended Follow-Up

If real teacher-generated batches still contain meaningful duplicate or echoed-choice rates:

1. Add a word-overlap check for reordered paraphrases.
2. Add a single batched AI review call after all per-file jobs complete.
3. Log rejected question indexes and rejection reasons for auditability.
4. Run a one-time audit against representative legacy batches.

The deterministic validation should remain the first filter even if a later AI review layer is added.

## Observability Follow-Up Completed

The two observability items from the original action list have now been implemented.

### Rejection logging

When a candidate is rejected during AI generation, the application writes an info-level log entry containing:

- Source filename.
- Difficulty tier.
- Question type.
- Whether the candidate came from the initial response or retry response.
- Candidate index within that response.
- Rejection reason.

Supported rejection reasons are:

- `invalid_structure`
- `empty_question_or_option`
- `duplicate_options`
- `stem_echo`

This gives a concrete signal when the existing retry path is activated because content-quality validation removed candidates.

### Read-only audit command

Added the Artisan command:

```powershell
php artisan quiz:audit-ai-content
```

The command scans saved `QuizQuestion` records without modifying them. It reports the number audited, clean, flagged, and the reason breakdown. It also lists the affected question IDs, module IDs, order values, and rejection reasons.

For machine-readable output or export into an audit artifact, use:

```powershell
php artisan quiz:audit-ai-content --json
```

To limit the audit to one quiz module:

```powershell
php artisan quiz:audit-ai-content --module=181 --json
```

## Current Legacy Baseline

The first read-only audit against the current saved database returned:

```text
Audited:               504
Clean:                 362
Flagged:               142
Duplicate options:     18
Stem echo:             121
Empty question/option: 0
Invalid structure:     3
```

The original pre-guard flagged rate was $142 / 504 = 28.17\%$. After adding the short-stem guard, the same read-only audit returned:

```text
Audited:               504
Clean:                 376
Flagged:               128
Duplicate options:      18
Stem echo:             107
Empty question/option:  0
Invalid structure:       3
```

The post-guard flagged rate is $128 / 504 = 25.40\%$. This is still a legacy-data baseline, not a post-fix AI generation rejection rate. The reduction mainly reflects removal of short-stem similarity false positives; it should not be interpreted as repaired legacy content.

## Manual Review of Stem-Echo Sample

A representative sample of 20 flagged records was exported and inspected manually:

```text
806, 832, 844, 864, 869, 928, 955, 960, 1029, 1032,
1034, 1037, 1049, 1078, 1081, 1094, 1111, 1112, 1113, 1114
```

Findings:

- Most sampled records were genuine stem echoes or near-restatements, especially IDs `1029`, `1032`, `1034`, `1037`, and `1111` through `1114`.
- ID `806` was a clear false positive caused by the very short stem `fadsf`; it motivated the 20-character short-stem guard.
- The 70% threshold is reasonable for normal-length stems based on this sample and does not need immediate adjustment.
- No regeneration was performed during this review because the records are legacy data and the audit command is read-only.

## Verification After This Follow-Up

```text
AiQuizContentValidationTest: 3 passed
StudentAssessmentAttemptsTest: 11 passed, 34 assertions
PHP syntax checks: passed
Laravel Blade cache: passed
Laravel Pint: passed
Read-only legacy audit: completed
```

The next generation run should be audited separately after generation. The new log entries and the audit command can then show how many candidates were rejected during generation and how many flagged questions, if any, were persisted afterward.

## Flagged Module Inventory Before Regeneration

The current legacy audit was grouped by `module_id` before any regeneration decision was made:

```text
module_id  title                  class                 flagged
246        Test90                 Test9                 41
256        asfasf                 Test9                  9
218        Chapter 3 network       Grade 10 Section A    7
257        Test                   Test9                  7
210        TESTSUNB                Grade 10 Section A    6
217        TEST33333              Grade 10 Section A    5
241        TESTDRIVE222           Test9                  4
244        SDA                    Test9                  4
243        asfafs                 Test9                  4
194        Chapter 2 - Networking IT120                 4
219        Chapter 4 Psychology   Grade 10 Section A    4
221        Test9                  Grade 10 Section A    3
242        dgdag                  Test9                  3
203        safa                   Grade 10 Section A    3
214        testtesteste3           Grade 10 Section A    2
Remaining modules with one flag each: 13 modules
```

Total remains 128 flagged records under the post-guard rules. The planned test module must be identified explicitly before regeneration because the current generation endpoint replaces the selected module/stage question set rather than updating individual saved question IDs in place.

Recommended safe next command after selecting the target module is:

```powershell
php artisan quiz:audit-ai-content --module=<TARGET_MODULE_ID> --json
```

Only after confirming that module and preserving its original context files should the teacher regenerate that module, then rerun the same module-scoped audit to measure the post-fix result.

## Selected Test Module Audit

Because no target module was specified in the request, the highest-impact module in the recent class context was selected for inspection: `module_id=246`, titled `Test90`, in class `Test9`.

Module-scoped command:

```powershell
php artisan quiz:audit-ai-content --module=246 --json
```

Result:

```text
Audited:                60
Clean:                  19
Flagged:                41
Duplicate options:       0
Stem echo:              41
Empty question/option:   0
Invalid structure:        0
Flagged rate:          68.33%
```

The 41 flagged records are question IDs:

```text
1281, 1282, 1285, 1286, 1287, 1288, 1289, 1291, 1293, 1294,
1295, 1297, 1298, 1302, 1303, 1304, 1306, 1308, 1309, 1310,
1311, 1313, 1314, 1316, 1320, 1321, 1322, 1323, 1324, 1325,
1326, 1327, 1328, 1329, 1330, 1331, 1332, 1333, 1335, 1337,
1339
```

### Regeneration status

Selective regeneration was intentionally not triggered. The current `generateQuizAi()` endpoint accepts context files and replaces the selected module/stage question set; it does not accept a list of saved question IDs for in-place regeneration. The original context files for module 246 are also not stored with each `QuizQuestion` record, so regenerating without the teacher's original source would risk replacing the entire module with unrelated questions.

Safe next step for this module:

1. Preserve/re-upload the exact source file or files originally used for `Test90`.
2. Confirm whether module 246 is the actual 50-student test target.
3. Generate the replacement set using the same source and requested distribution.
4. Run `php artisan quiz:audit-ai-content --module=246 --json` again.
5. Compare the post-generation flagged count and the generation log entries against this 41-item baseline.

## Recent 40-Question Generation Run Diagnosis

The exact run associated with nine saved questions was located by matching `QuizQuestion.created_at` to the Laravel log:

```text
Log window:       2026-08-28 19:24:33 to 19:25:18
Saved module:     module_id=238
Saved questions:  9
```

The request used these planned jobs:

```text
Prc-Reviewer-System-Summary (6).pdf | Average | what=4 | why=8 | how=8
42212 (5).pdf                      | Average | what=4 | why=8 | how=8
Total requested: 40
```

All 67 rejection entries in this run were `stem_echo`:

```text
Reason                    Rejections
stem_echo                 67
duplicate_options          0
empty_question_or_option   0
invalid_structure          0
```

Per-job rejection and retry breakdown:

```text
File                                  Difficulty  Type  Initial  Retry  Initial got  Retry need
Prc-Reviewer-System-Summary (6).pdf   Average     why       9       8       0           8
Prc-Reviewer-System-Summary (6).pdf   Average     how       8       8       0           8
42212 (5).pdf                         Average     what      3       3       1           4
42212 (5).pdf                         Average     why       8       8       0           8
42212 (5).pdf                         Average     how       7       8       1           8
```

The `Initial got` values are the `got` values recorded by the retry warning. They show the valid initial candidates that remained after filtering. The retry warnings show that every listed job entered the one-retry path.

### Important instrumentation gap

The current code does not log the final valid count after the retry batch is filtered. Therefore, the exact final achieved count per `(file, difficulty, type)` cannot be reconstructed from `laravel.log` alone. The database confirms that module 238 ended with 9 saved questions, all with `difficulty=Average`, but `question_type` is not persisted on `QuizQuestion`, so those 9 cannot be assigned exactly to the five jobs from saved data either.

This run proves that the current one-retry policy can silently produce a severe shortfall: 40 requested versus 9 persisted. A follow-up instrumentation fix should log `requested`, `initial_valid`, `retry_valid`, and `final_valid` after every job, and should emit a warning or fail the request when `final_valid < requested` according to the chosen product policy.

## Type-Aware Threshold and Shortfall Fix Implemented

The follow-up fix for the 40-request/9-saved regression is now implemented.

### Type-aware stem-echo thresholds

The validator now uses:

```text
what       > 70% similarity => reject
why/how    > 92% similarity => reject
```

The duplicate-options rule remains unchanged for every question type. This preserves protection against the original duplicate-choice bug while allowing legitimate vocabulary overlap in reasoning and procedure answers.

### Per-job final metrics

Every `(file, difficulty, type)` job now logs:

```text
requested
initial_candidates
initial_valid
retry_valid
final_valid
shortfall
```

If the final count is below the requested count, a distinct warning is written as `AI quiz per-type final shortfall`. The JSON response also includes:

```json
{
  "requested": 40,
  "generated": 9,
  "shortfall": 31
}
```

The successful response message includes both generated and requested totals so the teacher can see under-delivery immediately.

### Related accounting correction

The request-total validator was also corrected to count the actual supported difficulty keys `Easy`, `Average`, and `Difficult`. It previously read `Normal` and `Hard`, which could miscalculate requested totals for mixed difficulty runs.

### Verification

```text
AiQuizContentValidationTest: 4 passed
StudentAssessmentAttemptsTest: 11 passed, 34 assertions
Combined focused run: 15 passed, 38 assertions
PHP syntax check: passed
Laravel Blade cache: passed
Laravel Pint: passed
```

A new live AI generation run was not triggered automatically. The next run should use the same two source files and distribution, then compare the new per-job logs and response totals against the 40-request/9-saved baseline above.
