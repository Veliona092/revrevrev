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

    private MockBoardPhase $phase;

    private Module $module;

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

        $this->module = Module::factory()->create([
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

        $this->phase = MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'module_id' => $this->module->id,
            'phase_type' => 'pre_boards',
            'sequence_number' => 1,
            'title' => 'Sample Board Exam - Pre-Boards',
        ]);

        QuizQuestion::create([
            'module_id' => $this->module->id,
            'question_text' => 'Sample Question 1',
            'category' => 'Core Subject',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'correct_answer' => 'A',
            'correct_option' => 'A',
            'order' => 1,
        ]);
    }

    public function test_high_likelihood_returned_for_scores_meeting_75_threshold(): void
    {
        $quizAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->module->id,
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
            'mock_board_phase_id' => $this->phase->id,
            'quiz_attempt_id' => $quizAttempt->id,
            'phase_type' => 'pre_boards',
            'attempt_count' => 1,
            'score' => 8,
            'total' => 10,
            'percentage' => 80,
            'passed' => true,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.mock-boards.insights', [$this->mockBoard, $this->phase]));

        $response->assertOk();
        $response->assertJsonPath('board_likelihood.tier', 'high');
        $response->assertJsonPath('board_likelihood.label', 'High Chance (Board Ready)');
        $this->assertStringContainsString('meets the standard 75% PRC Board Exam benchmark', $response->json('board_likelihood.rationale'));
    }

    public function test_moderate_likelihood_returned_for_borderline_scores(): void
    {
        $quizAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->module->id,
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
            'mock_board_phase_id' => $this->phase->id,
            'quiz_attempt_id' => $quizAttempt->id,
            'phase_type' => 'pre_boards',
            'attempt_count' => 1,
            'score' => 7,
            'total' => 10,
            'percentage' => 70,
            'passed' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.mock-boards.insights', [$this->mockBoard, $this->phase]));

        $response->assertOk();
        $response->assertJsonPath('board_likelihood.tier', 'moderate');
        $response->assertJsonPath('board_likelihood.label', 'Moderate Chance');
        $this->assertStringContainsString('shy of the 75% PRC passing threshold', $response->json('board_likelihood.rationale'));
    }

    public function test_low_likelihood_returned_for_at_risk_scores(): void
    {
        $quizAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->module->id,
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
            'mock_board_phase_id' => $this->phase->id,
            'quiz_attempt_id' => $quizAttempt->id,
            'phase_type' => 'pre_boards',
            'attempt_count' => 1,
            'score' => 5,
            'total' => 10,
            'percentage' => 50,
            'passed' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.mock-boards.insights', [$this->mockBoard, $this->phase]));

        $response->assertOk();
        $response->assertJsonPath('board_likelihood.tier', 'low');
        $response->assertJsonPath('board_likelihood.label', 'Low Chance (At-Risk)');
        $this->assertStringContainsString('below the standard 75% threshold (At-Risk zone)', $response->json('board_likelihood.rationale'));
    }

    public function test_student_results_page_displays_board_likelihood(): void
    {
        $quizAttempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $this->module->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 9,
            'total' => 10,
            'percentage' => 90,
            'passed' => true,
            'completed_at' => now(),
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $this->phase->id,
            'quiz_attempt_id' => $quizAttempt->id,
            'phase_type' => 'pre_boards',
            'attempt_count' => 1,
            'score' => 9,
            'total' => 10,
            'percentage' => 90,
            'passed' => true,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.mock-boards.results', $this->mockBoard));

        $response->assertOk();
        $response->assertSee('Board Passing Likelihood');
        $response->assertSee('High Chance (Board Ready)');
    }
}
