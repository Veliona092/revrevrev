# Test Bank Integration Task

## Goal

Insert a central **Test Bank** between question creation and assessment creation.

```text
Teacher / AI creates questions
        ↓
      Test Bank
        ↓
Teacher selects questions
        ↓
Pre-test / Post-test / Assessment / Mock Board
```

## Existing system to preserve

- Keep the current quiz engine, student attempts, assessment pages, mock boards, and analytics.
- Existing `QuizQuestion` records are currently attached directly to a `Module`.
- Existing AI and manual question creation should remain usable.

## Required implementation

1. Create a teacher-managed Test Bank where questions can be saved before use in an assessment.
2. Each test-bank question must store:
   - program
   - subject
   - chapter/topic/subtopic
   - learning competency
   - difficulty: Easy, Average, Difficult
   - HOTS/cognitive level
   - question type: what, why, or how
   - question text, choices, correct answer, explanation
3. Add a teacher Test Bank page with search and filters for program, topic, competency, difficulty, HOTS, and question type.
4. Allow teachers to manually create, edit, archive, and select test-bank questions.
5. Allow AI-generated questions to be reviewed by the teacher and saved to the Test Bank.
6. From the Test Bank, allow a teacher to select questions and add them to:
   - pre-test
   - post-test
   - formal assessment
   - mock board phase
7. When questions are added to an assessment, save a copy/snapshot in that assessment so later edits to the Test Bank do not change questions already taken by students.
8. Add an assessment purpose/type so Pre-test and Post-test can be identified in analytics.
9. Extend analytics to show results by topic, competency, difficulty, and question type to help teachers identify review priorities.

## Acceptance criteria

- A teacher can create or generate a question once and reuse it in multiple assessments.
- A teacher can filter the Test Bank and select a defined number of questions for an exam.
- Pre-test, Post-test, and Mock Board questions can come from the same Test Bank.
- Student attempts and existing item analysis continue working.
- Editing a Test Bank question does not alter an already published or answered assessment.

## Important

- Do not remove or rewrite existing assessment, mock board, attempt-limit, or analytics features.
- Do not invent CPALE learning competencies; use the official competency list supplied by the school/teacher.
