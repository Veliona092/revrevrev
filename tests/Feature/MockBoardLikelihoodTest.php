<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\MockBoardAttempt;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockBoardLikelihoodTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    private MockBoard $mockBoard;

    private MockBoardPhase $preTestPhase;

    private MockBoardPhase $preBoardsPhase;

    private Module $preTestModule;

    private Module $preBoardsModule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create([
            'role' => 'student',
            'program' => 'education',
        ]);

        $this->class = ClassModel::factory()->create([
            'created_by' => $this->teacher->id,
            'program' => 'education',
        ]);
        $this->class->students()->attach($this->student->id);

        $this->preTestModule = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
        ]);

        $this->preBoardsModule = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
        ]);

        $this->mockBoard = MockBoard::create([
            'class_id' => $this->class->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Sample Board Exam',
            'program' => 'education',
            'review_period_start' => now()->subDay(),
            'review_period_end' => now()->addWeek(),
            'passing_percentage' => 75,
            'status' => 'approved',
        ]);

        $this->preTestPhase = MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'module_id' => $this->preTestModule->id,
            'phase_type' => 'pre_test',
            'sequence_number' => 1,
            'title' => 'Sample Board Exam - Pre-Test',
        ]);

        $this->preBoardsPhase = MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'module_id' => $this->preBoardsModule->id,
            'phase_type' => 'pre_boards',
            'sequence_number' => 1,
            'title' => 'Sample Board Exam - Pre-Boards',
        ]);

        QuizQuestion::create([
            'module_id' => $this->preBoardsModule->id,
            'question_text' => 'Sample Question 1',
            'category' => 'Core Subject',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'correct_answer' => 'A',
            'correct_option' => 'A',
            'order' => 1,
        ]);
    }

    public function test_likelihood_not_shown_when_only_pre_test_is_completed(): void
    {
        $preTestAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->preTestModule->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 6,
            'total' => 10,
            'percentage' => 60,
            'passed' => false,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->preTestPhase->id,
            'quiz_attempt_id' => $preTestAttempt->id,
            'phase_type' => 'pre_test',
            'attempt_count' => 1,
            'score' => 6,
            'total' => 10,
            'percentage' => 60,
            'passed' => false,
        ]);

        // Pre-test insights endpoint should NOT return board likelihood
        $insightsResponse = $this->actingAs($this->student)
            ->postJson(route('student.mock-boards.insights', [$this->mockBoard, $this->preTestPhase]));

        $insightsResponse->assertOk();
        $this->assertNull($insightsResponse->json('board_likelihood'));

        // Results view should NOT show Board Passing Likelihood card yet
        $viewResponse = $this->actingAs($this->student)
            ->get(route('student.mock-boards.results', $this->mockBoard));

        $viewResponse->assertOk();
        $viewResponse->assertDontSee('Board Passing Likelihood');
    }

    public function test_high_likelihood_returned_when_both_phases_completed_and_score_is_75_or_above(): void
    {
        // 1. Complete Pre-test
        $preTestAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->preTestModule->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 6,
            'total' => 10,
            'percentage' => 60,
            'passed' => false,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->preTestPhase->id,
            'quiz_attempt_id' => $preTestAttempt->id,
            'phase_type' => 'pre_test',
            'attempt_count' => 1,
            'score' => 6,
            'total' => 10,
            'percentage' => 60,
            'passed' => false,
        ]);

        // 2. Complete Pre-Boards (80%)
        $postTestAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->preBoardsModule->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 8,
            'total' => 10,
            'percentage' => 80,
            'passed' => true,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->preBoardsPhase->id,
            'quiz_attempt_id' => $postTestAttempt->id,
            'phase_type' => 'pre_boards',
            'attempt_count' => 1,
            'score' => 8,
            'total' => 10,
            'percentage' => 80,
            'passed' => true,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.mock-boards.insights', [$this->mockBoard, $this->preBoardsPhase]));

        $response->assertOk();
        $response->assertJsonPath('board_likelihood.tier', 'high');
        $response->assertJsonPath('board_likelihood.label', 'High Chance (Board Ready)');
        $this->assertStringContainsString('meets the standard 75% PRC Board Exam benchmark', $response->json('board_likelihood.rationale'));

        // Results view should show Board Passing Likelihood card
        $viewResponse = $this->actingAs($this->student)
            ->get(route('student.mock-boards.results', $this->mockBoard));

        $viewResponse->assertOk();
        $viewResponse->assertSee('Board Passing Likelihood');
        $viewResponse->assertSee('High Chance (Board Ready)');
    }

    public function test_moderate_likelihood_returned_for_borderline_scores_when_both_phases_completed(): void
    {
        // 1. Pre-Test
        $preTestAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->preTestModule->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 5,
            'total' => 10,
            'percentage' => 50,
            'passed' => false,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->preTestPhase->id,
            'quiz_attempt_id' => $preTestAttempt->id,
            'phase_type' => 'pre_test',
            'attempt_count' => 1,
            'score' => 5,
            'total' => 10,
            'percentage' => 50,
            'passed' => false,
        ]);

        // 2. Pre-Boards (70%)
        $postTestAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->preBoardsModule->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 7,
            'total' => 10,
            'percentage' => 70,
            'passed' => false,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->preBoardsPhase->id,
            'quiz_attempt_id' => $postTestAttempt->id,
            'phase_type' => 'pre_boards',
            'attempt_count' => 1,
            'score' => 7,
            'total' => 10,
            'percentage' => 70,
            'passed' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.mock-boards.insights', [$this->mockBoard, $this->preBoardsPhase]));

        $response->assertOk();
        $response->assertJsonPath('board_likelihood.tier', 'moderate');
        $response->assertJsonPath('board_likelihood.label', 'Moderate Chance');
        $this->assertStringContainsString('shy of the 75% PRC passing threshold', $response->json('board_likelihood.rationale'));
    }

    public function test_low_likelihood_returned_for_at_risk_scores_when_both_phases_completed(): void
    {
        // 1. Pre-Test
        $preTestAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->preTestModule->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 4,
            'total' => 10,
            'percentage' => 40,
            'passed' => false,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->preTestPhase->id,
            'quiz_attempt_id' => $preTestAttempt->id,
            'phase_type' => 'pre_test',
            'attempt_count' => 1,
            'score' => 4,
            'total' => 10,
            'percentage' => 40,
            'passed' => false,
        ]);

        // 2. Pre-Boards (50%)
        $postTestAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->preBoardsModule->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 5,
            'total' => 10,
            'percentage' => 50,
            'passed' => false,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->preBoardsPhase->id,
            'quiz_attempt_id' => $postTestAttempt->id,
            'phase_type' => 'pre_boards',
            'attempt_count' => 1,
            'score' => 5,
            'total' => 10,
            'percentage' => 50,
            'passed' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.mock-boards.insights', [$this->mockBoard, $this->preBoardsPhase]));

        $response->assertOk();
        $response->assertJsonPath('board_likelihood.tier', 'low');
        $response->assertJsonPath('board_likelihood.label', 'Low Chance (At-Risk)');
        $this->assertStringContainsString('below the standard 75% threshold (At-Risk zone)', $response->json('board_likelihood.rationale'));
    }
}
