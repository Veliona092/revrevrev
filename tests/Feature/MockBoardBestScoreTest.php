<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\MockBoardAttempt;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the "best score if individually" rule: a student's
 * cached MockBoardAttempt (used for both their own results screen and every
 * passing-rate rollup) must always reflect the HIGHEST score they've ever
 * achieved for a mock board phase, not just their most recent submission.
 */
class MockBoardBestScoreTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    private MockBoard $mockBoard;

    private MockBoardPhase $phase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->class = ClassModel::factory()->create(['created_by' => $this->teacher->id]);
        $this->class->students()->attach($this->student->id);

        $this->module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
        ]);

        $this->mockBoard = MockBoard::create([
            'class_id' => $this->class->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Sample Board',
            'program' => 'education',
            'review_period_start' => now()->subDay(),
            'review_period_end' => now()->addWeek(),
            'passing_percentage' => 75,
        ]);

        $this->phase = MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'phase_type' => 'pre_boards',
            'title' => 'Post-Test',
            'module_id' => $this->module->id,
        ]);
    }

    /**
     * Creates a fresh QuizAttempt row with answers producing the given score,
     * simulating one full attempt at the post-test (mirrors the real "retake"
     * flow, where each retry is its own attempt id).
     */
    private function submitAttempt(int $correctCount, int $totalCount): void
    {
        $attempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->module->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 0,
            'total' => 0,
            'percentage' => 0,
            'passed' => false,
            'attempt_count' => 1,
        ]);

        $options = ['A', 'B', 'C', 'D'];

        for ($i = 0; $i < $totalCount; $i++) {
            $correctOption = $options[$i % 4];
            $selectedOption = $i < $correctCount ? $correctOption : $options[($i + 1) % 4];

            $question = QuizQuestion::create([
                'module_id' => $this->module->id,
                'question_text' => "Question {$attempt->id}-{$i}",
                'options' => ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'],
                'correct_option' => $correctOption,
                'points' => 1,
                'order' => $i,
            ]);

            QuizAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option' => $selectedOption,
                'is_correct' => $i < $correctCount,
            ]);
        }

        $this->actingAs($this->student)
            ->postJson("/modules/{$this->module->id}/quiz/submit", [
                'attempt_id' => $attempt->id,
                'total' => $totalCount,
            ])
            ->assertOk();
    }

    public function test_mock_board_attempt_keeps_best_score_when_a_later_retake_is_worse(): void
    {
        // First attempt: 90% (well above the 75% passing threshold).
        $this->submitAttempt(9, 10);

        // Second attempt (retake): only 40% — worse than the first.
        $this->submitAttempt(4, 10);

        $cached = MockBoardAttempt::where('user_id', $this->student->id)
            ->where('mock_board_id', $this->mockBoard->id)
            ->where('phase_type', 'pre_boards')
            ->first();

        $this->assertNotNull($cached);
        $this->assertSame(90, $cached->percentage);
        $this->assertTrue($cached->passed);
        // attempt_count still reflects both tries even though the score wasn't overwritten.
        $this->assertSame(2, $cached->attempt_count);
    }

    public function test_mock_board_attempt_updates_when_a_later_retake_is_better(): void
    {
        // First attempt: 40% — fails.
        $this->submitAttempt(4, 10);

        // Second attempt: 85% — a new best, and now passes.
        $this->submitAttempt(8, 10);

        $cached = MockBoardAttempt::where('user_id', $this->student->id)
            ->where('mock_board_id', $this->mockBoard->id)
            ->where('phase_type', 'pre_boards')
            ->first();

        $this->assertNotNull($cached);
        $this->assertSame(80, $cached->percentage);
        $this->assertTrue($cached->passed);
        $this->assertSame(2, $cached->attempt_count);
    }

    public function test_mock_board_attempt_best_score_persists_across_three_attempts(): void
    {
        // Mirrors the Juan example from the product discussion:
        // attempts of 60%, 82%, 76% -> best (82%) should be what's cached.
        $this->submitAttempt(6, 10); // 60%
        $this->submitAttempt(8, 10); // 80% (closest achievable to 82% with 10 Qs)
        $this->submitAttempt(7, 10); // 70%

        $cached = MockBoardAttempt::where('user_id', $this->student->id)
            ->where('mock_board_id', $this->mockBoard->id)
            ->where('phase_type', 'pre_boards')
            ->first();

        $this->assertNotNull($cached);
        $this->assertSame(80, $cached->percentage);
        $this->assertTrue($cached->passed);
        $this->assertSame(3, $cached->attempt_count);
    }
}
