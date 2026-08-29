# AI Quiz Generation Fix Report

## Scope
This report captures the AI quiz generation changes made in `app/Http/Controllers/ClassManagerController.php`, including the validation fix for `why` / `how` questions, evidence grounding, cross-batch deduplication, and the safety check that prevents deleting existing quiz content when the generated batch is under the acceptance threshold.

## Root cause
The original validation treated all question types the same when checking for `stem_echo` similarity. This was too strict for `why` and `how` questions because those question stems legitimately overlap with source wording and answer phrasing.

## Fixes implemented
### 1) Type-aware stem echo guard
`why` and `how` now use a higher similarity threshold than `what`.

```php
private function getStemEchoThreshold(?string $questionType): float
{
    return in_array($questionType, ['why', 'how'], true) ? 92.0 : 70.0;
}
```

### 2) Source grounding check
A generated question must include `evidence` text that is actually present in the source text.

```php
private function isGroundedInSource(string $evidence, string $sourceText): bool
{
    $normalizedEvidence = $this->normalizeForComparison($evidence);
    $normalizedSource = $this->normalizeForComparison($sourceText);

    return $normalizedEvidence !== '' && str_contains($normalizedSource, $normalizedEvidence);
}
```

### 3) Acceptance threshold before replacing existing questions
The system does not erase the old quiz if the generated count falls below the acceptance floor.

```php
private function shouldReplaceExistingQuestions(int $requested, int $generated, float $minimumAcceptanceRatio = 0.8): bool
{
    if ($requested <= 0) {
        return false;
    }

    return $generated >= (int) ceil($requested * $minimumAcceptanceRatio);
}
```

### 4) Cross-batch duplicate detection
Questions are deduplicated across the whole generated batch using a similarity threshold.

```php
private function deduplicateQuestionBatch(array $questions): array
{
    $deduplicated = [];
    $removed = [];

    foreach ($questions as $index => $question) {
        $questionText = (string) ($question['question'] ?? '');
        $normalizedQuestion = $this->normalizeForComparison($questionText);
        $questionType = (string) ($question['question_type'] ?? 'what');

        if ($normalizedQuestion === '') {
            $deduplicated[] = $question;
            continue;
        }

        $isDuplicate = false;
        foreach ($deduplicated as $existingIndex => $existingQuestion) {
            $existingText = (string) ($existingQuestion['question'] ?? '');
            $normalizedExisting = $this->normalizeForComparison($existingText);

            if ($normalizedExisting === '') {
                continue;
            }

            similar_text($normalizedExisting, $normalizedQuestion, $similarity);
            if ($similarity >= $this->getCrossBatchDuplicateThreshold()) {
                $removed[] = [
                    'dropped_index' => $index,
                    'kept_index' => $existingIndex,
                    'dropped_question' => $questionText,
                    'kept_question' => $existingText,
                    'similarity' => round($similarity, 2),
                    'question_type' => $questionType,
                ];
                $isDuplicate = true;
                break;
            }
        }

        if (! $isDuplicate) {
            $deduplicated[] = $question;
        }
    }

    return [
        'questions' => $deduplicated,
        'duplicates' => $removed,
    ];
}
```

### 5) Unified rejection logic
This is the main validation gate used during generation.

```php
private function aiQuestionRejectionReason(mixed $question, array $choiceLetters, ?string $questionType = null, ?string $sourceText = null): ?string
{
    if (! is_array($question)
        || ! isset($question['question'], $question['options'], $question['correct'])
        || ! is_string($question['question'])
        || ! is_string($question['correct'])
        || ! is_array($question['options'])
        || count($question['options']) !== count($choiceLetters)
        || ! in_array(strtoupper($question['correct']), $choiceLetters, true)) {
        return 'invalid_structure';
    }

    $normalize = static fn (mixed $value): string => trim((string) preg_replace('/\s+/u', ' ', mb_strtolower((string) $value)));
    $normalizedQuestion = $normalize($question['question']);
    $normalizedOptions = array_map($normalize, array_values($question['options']));

    if ($normalizedQuestion === ''
        || in_array('', $normalizedOptions, true)) {
        return 'empty_question_or_option';
    }

    if (count($normalizedOptions) !== count(array_unique($normalizedOptions))) {
        return 'duplicate_options';
    }

    if ($sourceText !== null) {
        $evidence = trim((string) ($question['evidence'] ?? $question['evidence_text'] ?? ''));
        if ($evidence === '' || ! $this->isGroundedInSource($evidence, $sourceText)) {
            return 'ungrounded';
        }
    }

    $similarityThreshold = $this->getStemEchoThreshold($questionType);

    if (mb_strlen($normalizedQuestion) >= 20) {
        foreach ($normalizedOptions as $option) {
            similar_text($normalizedQuestion, $option, $similarity);
            if ($similarity > $similarityThreshold) {
                return 'stem_echo';
            }
        }
    }

    return null;
}
```

## Observed run data (latest 26/40 generation in laravel log)
From the newest generation block in `storage/logs/laravel.log`, the final generated count is 26 out of 40. The relevant per-job log entries show the following rejection pattern.

### Aggregate rejection counts from the run
- `ungrounded`: 42
- `stem_echo`: 39
- `duplicate_options`: 0
- `cross_batch_duplicate`: 5

### Key interpretation
- `ungrounded` and `stem_echo` are both materially active.
- `ungrounded` is not overwhelmingly dominant, but it is close to `stem_echo` and contributes significantly.
- `duplicate_options` is zero in this run.
- `cross_batch_duplicate` only contributed 5 removals; it is not the primary reason for the under-generation.

## Relevant production log marker
The log includes the final generated count marker:

```json
{"distribution":{"A":9,"B":9,"C":4,"D":4,"E":0,"F":0,"G":0,"H":0,"I":0,"J":0},"generated":26}
```

This is the newest run ending at the `AI quiz answer letter distribution` log entry in `storage/logs/laravel.log`.

## File changed
- `app/Http/Controllers/ClassManagerController.php`

## Result
The bug fix preserves the public API contract, prevents destructive replacement when the generated count is too low, and adds better observability for per-job validation outcomes.
