# Mock Board Workflow (Teacher and Student)

This document explains how the mock board feature works in the application from both the teacher side and the student side.

## Overview
A mock board is a program-specific assessment setup tied to a student track (for example: Accountancy, Psychology, Education). It is built around:

- `MockBoard`: the top-level assessment
- `MockBoardPhase`: each phase of the assessment (for example `pre_test` and `pre_boards`)
- `Module`: the quiz content linked to each phase
- `QuizQuestion`: actual question data used in the quiz
- `MockBoardAttempt`: student attempt record for each phase

The teacher owns and manages the mock board, while students are assigned to a matching program and take the exam through the student dashboard.

---

## Teacher-side flow

### 1) Teacher creates a mock board
The teacher creates a mock board under a specific program and optionally under a class. In the controller, the teacher can filter mock boards by program and class when viewing management pages.

Key properties include:

- title
- description
- program
- review period start and end
- passing percentage
- status (`pending`, `approved`, `rejected`)

The model logic also supports approval and rejection states.

### 2) Teacher links phases to quiz modules
Each mock board is composed of phases. The model uses `MockBoardPhase` with fields such as:

- `mock_board_id`
- `phase_type` (for example `pre_test` or `pre_boards`)
- `module_id`
- `question_ids`
- `is_same_questions`

This means a mock board is not just a single exam. It can be a staged assessment with different modules for each phase.

### 3) Teacher prepares the quiz content
Each phase is associated with a module that can hold quiz questions. The teacher may:

- create the quiz manually,
- generate AI questions,
- approve or edit generated items,
- attach different question sets to different phases.

The main teacher entry points are centered around the mock board admin pages and the module/quiz creation flow.

### 4) Teacher reviews the mock board and analytics
The teacher can:

- view all mock boards they own,
- filter by class or program,
- check number of attempts,
- inspect analytics and performance summaries,
- see result trends across students and phases.

The system also includes batch analytics that aggregate scores, completion rates, and highest scores for the teacher’s mock boards in a selected program.

### 5) Teacher enforces passing rules
Each mock board has a passing percentage, and the underlying module also stores a passing percentage. When the teacher updates the mock board, the passing percentage is synced to related modules.

This makes the mock board rules consistent with the actual quiz performance thresholds.

### 6) Teacher can approve or reject mock boards
The mock board has approval-related logic:

- pending
- approved
- rejected
- approver and approved timestamp
- rejection reason

This allows governance for board setup and ensures not every board becomes active immediately.

---

## Student-side flow

### 1) Student sees only boards for their program
In the student controller, available mock boards are loaded using the current user’s program:

- `MockBoard::where('program', $user->program)`
- then filtered by the user’s attempts

This ensures a student only sees mock boards relevant to their track.

### 2) Student can take each phase once
When a student enters a mock board phase, the app checks if the student already completed that phase:

- same `user_id`
- same `mock_board_id`
- same `phase_type`

If the student already completed it, the system blocks re-entry with an error message: “You have already completed this phase.”

### 3) Student receives a randomized question set
The exam page loads questions in random order from the board’s linked question collection.

The student controller does this:

```php
$questions = $mockBoard->questions()->inRandomOrder()->get();
```

This gives each attempt a randomized order, which makes the board feel more like a real assessment session.

### 4) Student answers and submits the exam
During submission, the app compares the selected answer against the correct answer for each question.

The flow does the following:

- reads submitted `answers`
- checks each question’s `correct_answer`
- counts correct responses
- computes percentage
- determines pass/fail using a threshold of 75%

Example logic:

```php
$percentage = ($totalQuestions > 0) ? ($correctCount / $totalQuestions) * 100 : 0;
$passed = $percentage >= 75;
```

### 5) Student attempt is saved
A `MockBoardAttempt` record is created with:

- user id
- mock board id
- phase type
- score
- total questions
- percentage
- passed flag

This creates a historical record for the student’s performance within that board and phase.

### 6) Student sees dashboard results and history
After submission, the app redirects the student back to the mock board index and they can open results. The student results page loads the student’s attempt data for each phase and displays:

- score
- total questions
- percentage
- pass/fail state
- AI insights if available

This gives the student a quick summary of their mock board performance.

---

## How the scoring model works
The scoring logic is intentionally simple and reliable:

- each question is either correct or incorrect
- total score = number of correct answers
- percentage = correctCount / totalQuestions * 100
- pass threshold = 75%

This is used in the student submission flow and is persisted as `MockBoardAttempt` data.

## Passing-rate policy and plan
The following policy is the current working plan for determining pass/fail for both individual students and the program cohort.

### Individual passing rate
For a single student, the app should evaluate pass/fail using the best score across all attempts for the same mock board phase.

- If a student takes the same post-test three times, we should not average the three scores.
- We should not require them to pass all attempts.
- We should use the highest percentage they achieved for that phase.
- If that best score is greater than or equal to the configured passing percentage, the student is considered to have passed individually.

This is the recommended interpretation for the product direction: `best score if individually`.

### Program passing rate
For a program-level result, compute the pass rate based on the final individual-best score of each student in that program.

- Count how many students in the program have a passing best score.
- Divide by the total number of students who are eligible for the mock board or phase.
- This gives the cohort-level passing rate for that program.

This keeps the program metric aligned with the student-level metric while still showing aggregate performance.

### Why this plan is preferable
This approach avoids unfairly penalizing students for having one weak attempt while still recognizing their strongest demonstrated mastery. It is also easier to explain and report in analytics:

- student result = best score for that phase
- program result = percentage of students whose best score passed
- teacher sees both the per-student outcome and the program-wide benchmark clearly

### Example
If Juan has three post-test attempts:

- Attempt 1: 60%
- Attempt 2: 82%
- Attempt 3: 76%

Then his individual passing result is based on `82%` because that is his best score. If the passing threshold is 75%, he passes the phase.

For program analytics, the program pass rate is computed from each student’s best score, not from the average of all attempts.

---

## Relationship between mock board and module/quiz system
The mock board is layered on top of the existing quiz infrastructure:

- `MockBoardPhase` points to one `Module`
- that module contains the actual `QuizQuestion` records
- the student attempts are tracked separately from quiz attempts
- the app can show historical quiz attempts for each phase

This design allows the mock board to be reused as a formal assessment shell while still reusing the standard quiz engine.

---

## End-to-end summary
### Teacher flow
1. Create mock board for a program/class.
2. Add phases and tie them to modules.
3. Prepare or generate questions.
4. Publish/approve board.
5. Monitor performance and analytics.

### Student flow
1. Student logs in and sees boards for their program.
2. Chooses a board and phase.
3. Takes the exam once per phase.
4. Answers all questions and submits.
5. System calculates score and pass/fail.
6. Student views history and results.

---

## Key files involved
- `app/Models/MockBoard.php`
- `app/Models/MockBoardPhase.php`
- `app/Http/Controllers/MockBoardController.php`
- `app/Http/Controllers/Student/StudentMockBoardController.php`
- `app/Services/MockBoardStatisticsService.php`

These are the main files responsible for mock board creation, management, grading, and student experience.
