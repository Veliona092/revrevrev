# Bug Report & Fix Plan: Duplicate/Echoed Content in AI-Generated Quiz Questions

**System:** Reviso (Laravel 12 licensure-exam review platform)
**Affected method:** `generateQuizAi()` — quiz question generation controller method
**Reported by:** Teacher-side testing, 50-question generation run
**Severity:** Medium-High — corrupted questions get persisted to `QuizQuestion` table and later feed into `MockBoardStatisticsService` (item analysis, difficulty/discrimination stats), so bad data silently pollutes analytics, not just the quiz UI.

---

## 1. Symptom

When a teacher requests a batch of 50 AI-generated questions (across multiple uploaded context files, multiple difficulty tiers, and multiple question types — what/why/how), **at least 9 of the 50 generated questions were observed to contain duplicated content**, specifically:

- The same answer text repeated across two or more `options` within a single question (duplicate choices).
- An answer option whose text is essentially an echo/restatement of the question stem itself (i.e., the "distractor" is not really a distractor — it's the question repeated as an answer).

**Note on the count:** the "9" figure comes from manual/spot-check review of the batch, not an automated audit — the true number is likely higher, since the stem-echo case in particular is easy to miss on a manual read-through if the AI reworded the echo rather than repeating it verbatim. Section 6 below adds an action item to get an automated, exact count before and after the fix, rather than relying on manual counting again.

## 2. Root Cause

The current validation logic on the AI response (inside the per-`(file, tier, type)` generation loop) only checks **structural correctness**, not **content quality**:

```php
$batch = array_values(array_filter($batch, function ($q) use ($choiceLetters) {
    return isset($q['question'], $q['options'], $q['correct'])
        && is_string($q['question'])
        && is_string($q['correct'])
        && is_array($q['options'])
        && count($q['options']) === count($choiceLetters)
        && in_array(strtoupper($q['correct']), $choiceLetters, true);
}));
```

This confirms the *shape* of each question object (right number of fields, right number of options, valid correct-answer letter) but never inspects whether the option **values** are distinct from each other or distinct from the question stem. The underlying Cloudflare Workers AI model (Llama 3.x family, per `AiSettingsResolver`/`CloudflareAI`) occasionally produces:
- Two options with identical or near-identical text.
- An option that is a near-verbatim restatement of the question.

This is a known failure mode of LLM-generated MCQs — not unique to this model or prompt. Academic literature on LLM-generated multiple-choice questions (e.g. self-refine / iterative self-critique frameworks such as MCQG-SRefine) documents the same class of error, including "direct inclusion of the answer within the context/stem" and duplicate/low-quality distractors, and shows that adding a self-critique/correction pass measurably improves output quality over direct single-pass generation.

There is a secondary, lower-priority correctness issue in the existing option-shuffle step: if duplicate option text exists, the shuffle's `$correctText` matching logic (`if (($texts[$idx] ?? null) === $correctText)`) can match the **wrong occurrence** of a duplicated string, potentially mislabeling the correct answer. This is a symptom of the same root cause (duplicate content should never reach this stage), not a separate bug to fix independently.

## 3. Constraints for the Fix

- **Traffic profile:** This method runs **once per teacher-initiated "Generate" action**, not per student. Once questions are persisted via the `DB::transaction()` block into `QuizQuestion`, students taking the quiz only read already-saved rows — there is no AI involvement, and no scaling concern, at quiz-taking time (confirmed: even with 50 concurrent students taking the quiz, this method is not re-invoked). Any added cost only affects the teacher's one-time wait during generation.
- **Existing retry mechanism already exists** for short batches (`if (count($batch) < $typeCount) { ... retry ... }`) and should be reused/extended rather than duplicated.
- **Time budget already accounts for multiple AI calls**: `set_time_limit(max(90, $activeJobCount * 90))` — the fix should avoid materially multiplying the number of AI calls per job (i.e., avoid adding an AI call inside the innermost `(file × tier × type)` loop).
- Must not break the existing per-file / per-tier / per-type structure, response schema, or the final persistence step.

## 4. Recommended Fix — Two-Layer Validation

### Layer 1 — Deterministic validation (no AI call, negligible cost, do this first)

Add a content-level check, applied to every candidate question **before** it's accepted into `$batch` (alongside the existing structural filter), rejecting a question if either:

**(a) Duplicate options** — two or more option values are identical after normalization (lowercase, trim, collapse whitespace):

```php
$normalizedOptions = array_map($normalize, array_values($q['options']));
if (count($normalizedOptions) !== count(array_unique($normalizedOptions))) {
    return false; // exact-match duplicate, no ambiguity
}
```

**(b) Option that echoes the question stem** — string similarity between the (normalized) question text and any option exceeds a threshold, using PHP's `similar_text()`:

```php
similar_text($question, $opt, $percent);
if ($percent > 70) { // threshold to be tuned empirically against real output
    return false;
}
```

A question rejected by Layer 1 is simply **not added to `$batch`**, which means it naturally falls short of `$typeCount` and triggers the **existing retry block** already in the code — no new retry pathway needs to be built.

Known limitation: `similar_text()` is a longest-common-substring-based measure and is weak against paraphrased-but-still-echoing text with reordered words. If Layer 1 alone proves insufficient after real-world testing, a word-overlap-ratio check (percentage of the option's words that also appear in the question) is a reasonable upgrade, still with zero AI cost.

### Layer 2 — Batched LLM self-critique (optional, one extra AI call per generation run, not per job)

If Layer 1 does not catch enough cases in practice, add a **single** review call **after** all per-`(file, tier, type)` jobs have finished and `$allGeneratedQuestions` is fully assembled — not inside the per-job loop. This keeps the added cost to **+1 AI call total per "Generate" click**, regardless of how many `(file, tier, type)` jobs ran.

The review call should use a **checklist-style prompt** (not open-ended "review this"), asking the model to flag, by index, any item where:
1. Two or more options are duplicates or near-duplicates.
2. Any option is a restatement of the question stem rather than a genuine answer choice.
3. There is no clearly correct answer among the options.

Only the flagged items get regenerated, reusing the existing retry mechanism, rather than re-running the whole batch.

**Recommendation:** Implement Layer 1 now (immediate, safe, no cost). Treat Layer 2 as a follow-up enhancement, added only if post-Layer-1 testing still shows a meaningful duplicate/echo rate.

## 5. Suggested Integration Point

Modify the existing structural filter closure (the `array_filter` shown in Section 2) to also call a new private helper, e.g. `isCleanQuestion(array $q): bool`, combining checks (a) and (b) above. Apply this same helper identically to the **retry batch** filter later in the same method, so retried questions are held to the same standard.

## 6. Open Items for Implementation

- [ ] Tune the `similar_text()` percentage threshold against a real sample of flagged vs. clean questions (70% is a starting estimate, not validated).
- [ ] Decide whether Layer 2 is needed before the 50-student test run, or deferred to a follow-up iteration.
- [ ] Confirm the fix does not change the public response shape returned to the frontend (`success`, `message`, `questions`).
- [ ] **Add a one-time audit script/log** that runs Layer 1's duplicate/echo check against a generated batch and reports an exact count and the specific question indices affected, both (a) on existing/legacy generated batches, to get a true baseline instead of the manual "at least 9" estimate, and (b) after the fix ships, to confirm the actual reduction rate. Manual spot-checking should not be the basis for measuring whether the fix worked.
