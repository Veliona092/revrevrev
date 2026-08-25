# Test Bank Implementation Report

## Completed

Implemented a central Test Bank without replacing the existing quiz, assessment, mock board, student attempt, and analytics systems.

```text
Teacher / AI-reviewed quiz questions
        ↓
      Test Bank
        ↓
Teacher selects questions
        ↓
Pre-Test / Post-Test / Assessment / Mock Board
        ↓
Existing student attempts and analytics
```

## What was added

- `test_bank_questions` database table and `TestBankQuestion` model.
- Test Bank fields:
  - automatic program assignment from the teacher account
  - difficulty: Average, Normal, or Hard
  - question text, choices, and correct answer
- Teacher Test Bank page with:
  - manual question creation
  - edit
  - archive
  - question search and difficulty filter
  - selection of questions for an assessment
- Test Bank navigation link in the teacher layout.
- Assessment purpose field for Formal Assessment, Pre-Test, and Post-Test.

## Reusing old questions

Existing questions are not automatically copied into the Test Bank.

To make old questions reusable:

1. Open an existing quiz or assessment editor.
2. Click **Save Questions to Test Bank**.
3. Questions that are not already linked to a Test Bank source are imported.
4. Go to **Test Bank**, select the questions, choose a destination assessment, and click **Add selected as snapshots**.

The same import action works for AI-generated questions after the teacher reviews and saves them in the normal quiz editor.

## Snapshot behavior

When a teacher adds a Test Bank question to an assessment, the system creates a copy in `quiz_questions`.

This means editing the original Test Bank question later does not change an assessment already published or answered by students.

## Database migrations applied

- `create_test_bank_questions_table`
- `add_assessment_purpose_to_modules_table`
- `add_test_bank_question_metadata_to_quiz_questions_table`

## Verification

- Test Bank feature tests: passed.
- Student assessment attempt regression tests: passed.
- Result: **14 tests passed, 50 assertions**.
- Blade views were compiled successfully.

## Scope clarification

Subject, chapter, topic, subtopic, learning competency, HOTS/cognitive level, question type, and Test Bank explanation fields were intentionally removed. They are not implemented as reusable data in the current system. HOTS remains an AI-generation instruction only.
