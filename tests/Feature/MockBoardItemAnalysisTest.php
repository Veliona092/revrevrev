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
use App\Services\MockBoardStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockBoardItemAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $admin;

    protected User $studentA;

    protected User $studentB;

    protected MockBoard $board;

    protected MockBoardPhase $preTestPhase;

    protected MockBoardPhase $postTestPhase;

    protected QuizQuestion $q1;

    protected QuizQuestion $q2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'program' => 'accountancy',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'program' => 'admin',
        ]);

        $this->studentA = User::factory()->create([
            'role' => 'student',
            'program' => 'accountancy',
        ]);

        $this->studentB = User::factory()->create([
            'role' => 'student',
            'program' => 'accountancy',
        ]);

        $class = ClassModel::factory()->create([
            'program' => 'accountancy',
            'created_by' => $this->teacher->id,
        ]);
        $class->students()->attach([$this->studentA->id, $this->studentB->id]);

        $this->board = MockBoard::create([
            'title' => 'BSA Comprehensive Board 2026',
            'program' => 'accountancy',
            'class_id' => $class->id,
            'teacher_id' => $this->teacher->id,
            'passing_percentage' => 75,
            'review_period_start' => now()->subDays(5),
            'review_period_end' => now()->addDays(5),
            'status' => 'approved',
        ]);

        // Pre-Test Module & Phase
        $preTestModule = Module::factory()->create([
            'class_id' => $class->id,
            'title' => 'Pre-Test Module',
        ]);
        $this->preTestPhase = MockBoardPhase::create([
            'mock_board_id' => $this->board->id,
            'phase_type' => 'pre_test',
            'sequence_number' => 1,
            'label' => 'Pre-Test',
            'title' => 'Pre-Test Phase',
            'module_id' => $preTestModule->id,
        ]);

        // Create Questions for Pre-Test Module
        $this->q1 = QuizQuestion::create([
            'module_id' => $preTestModule->id,
            'question_text' => 'What is the fundamental accounting equation?',
            'options' => ['A' => 'Assets = Liabilities + Equity', 'B' => 'Assets = Liabilities - Equity', 'C' => 'Assets = Revenue - Expense', 'D' => 'Assets = Cash'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Easy',
        ]);

        $this->q2 = QuizQuestion::create([
            'module_id' => $preTestModule->id,
            'question_text' => 'Which financial statement reports financial position?',
            'options' => ['A' => 'Income Statement', 'B' => 'Balance Sheet', 'C' => 'Cash Flow Statement', 'D' => 'Notes'],
            'correct_option' => 'B',
            'points' => 1,
            'order' => 2,
            'difficulty' => 'Normal',
        ]);

        // Post-Test Module & Phase
        $postTestModule = Module::factory()->create([
            'class_id' => $class->id,
            'title' => 'Post-Test Module',
        ]);
        $this->postTestPhase = MockBoardPhase::create([
            'mock_board_id' => $this->board->id,
            'phase_type' => 'pre_boards',
            'sequence_number' => 1,
            'label' => 'Post-Test 1',
            'title' => 'Post-Test Phase 1',
            'module_id' => $postTestModule->id,
        ]);

        // Student A Attempts Pre-Test (Answers Q1: A (Correct), Q2: B (Correct)) -> 100%
        $quizAttemptA = QuizAttempt::create([
            'user_id' => $this->studentA->id,
            'module_id' => $preTestModule->id,
            'score' => 2,
            'total' => 2,
            'percentage' => 100,
            'passed' => true,
        ]);
        MockBoardAttempt::create([
            'user_id' => $this->studentA->id,
            'mock_board_id' => $this->board->id,
            'mock_board_phase_id' => $this->preTestPhase->id,
            'quiz_attempt_id' => $quizAttemptA->id,
            'phase_type' => 'pre_test',
            'score' => 2,
            'total_questions' => 2,
            'percentage' => 100,
            'passed' => true,
            'attempt_count' => 1,
        ]);
        QuizAnswer::create([
            'attempt_id' => $quizAttemptA->id,
            'question_id' => $this->q1->id,
            'selected_option' => 'A',
            'is_correct' => true,
        ]);
        QuizAnswer::create([
            'attempt_id' => $quizAttemptA->id,
            'question_id' => $this->q2->id,
            'selected_option' => 'B',
            'is_correct' => true,
        ]);

        // Student B Attempts Pre-Test (Answers Q1: A (Correct), Q2: A (Incorrect)) -> 50%
        $quizAttemptB = QuizAttempt::create([
            'user_id' => $this->studentB->id,
            'module_id' => $preTestModule->id,
            'score' => 1,
            'total' => 2,
            'percentage' => 50,
            'passed' => false,
        ]);
        MockBoardAttempt::create([
            'user_id' => $this->studentB->id,
            'mock_board_id' => $this->board->id,
            'mock_board_phase_id' => $this->preTestPhase->id,
            'quiz_attempt_id' => $quizAttemptB->id,
            'phase_type' => 'pre_test',
            'score' => 1,
            'total_questions' => 2,
            'percentage' => 50,
            'passed' => false,
            'attempt_count' => 1,
        ]);
        QuizAnswer::create([
            'attempt_id' => $quizAttemptB->id,
            'question_id' => $this->q1->id,
            'selected_option' => 'A',
            'is_correct' => true,
        ]);
        QuizAnswer::create([
            'attempt_id' => $quizAttemptB->id,
            'question_id' => $this->q2->id,
            'selected_option' => 'A',
            'is_correct' => false,
        ]);
    }

    public function test_group_item_analysis_calculates_difficulty_and_distractor_percentages(): void
    {
        $service = app(MockBoardStatisticsService::class);

        $itemAnalysis = $service->getItemAnalysis($this->board, null, $this->preTestPhase->id);

        $this->assertCount(2, $itemAnalysis);

        // Q1: Both Student A & B got it right (2/2 = 1.0 difficulty / Very Easy)
        $q1Stats = collect($itemAnalysis)->firstWhere('question_id', $this->q1->id);
        $this->assertNotNull($q1Stats);
        $this->assertEquals(1.0, $q1Stats['difficulty']);
        $this->assertSame(2, $q1Stats['correct_count']);
        $this->assertSame(2, $q1Stats['total_count']);
        $this->assertSame(2, $q1Stats['option_counts']['A']);
        $this->assertEquals(100.0, $q1Stats['option_percentages']['A']);

        // Q2: 1 of 2 got it right (1/2 = 0.5 difficulty / Moderate)
        $q2Stats = collect($itemAnalysis)->firstWhere('question_id', $this->q2->id);
        $this->assertNotNull($q2Stats);
        $this->assertEquals(0.5, $q2Stats['difficulty']);
        $this->assertSame(1, $q2Stats['correct_count']);
        $this->assertSame(2, $q2Stats['total_count']);
        $this->assertSame(1, $q2Stats['option_counts']['B']); // Correct key
        $this->assertSame(1, $q2Stats['option_counts']['A']); // Student B chosen distractor
        $this->assertEquals(50.0, $q2Stats['option_percentages']['A']);
        $this->assertEquals(50.0, $q2Stats['option_percentages']['B']);
    }

    public function test_individual_student_item_analysis_service_returns_question_breakdown(): void
    {
        $service = app(MockBoardStatisticsService::class);

        // Student B: Q1 correct (A), Q2 wrong (Selected A, Correct B)
        $studentBAnalysis = $service->getStudentMockBoardItemAnalysis($this->board, $this->studentB, $this->preTestPhase->id);

        $this->assertCount(2, $studentBAnalysis['questions']);
        $this->assertSame(1, $studentBAnalysis['summary']['correct_count']);
        $this->assertSame(1, $studentBAnalysis['summary']['incorrect_count']);
        $this->assertEquals(50.0, $studentBAnalysis['summary']['score_percentage']);
        $this->assertFalse($studentBAnalysis['summary']['passed']);

        $q2Answer = collect($studentBAnalysis['questions'])->firstWhere('question_id', $this->q2->id);
        $this->assertFalse($q2Answer['is_correct']);
        $this->assertSame('A', $q2Answer['selected_option']);
        $this->assertSame('B', $q2Answer['correct_option']);
        $this->assertSame('Income Statement', $q2Answer['selected_option_text']);
        $this->assertSame('Balance Sheet', $q2Answer['correct_option_text']);
    }

    public function test_teacher_can_fetch_student_item_analysis_via_endpoint(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson(route('mock-boards.batch.student-analysis', [
                'program' => 'accountancy',
                'mock_board' => $this->board->id,
                'user' => $this->studentB->id,
                'phase_id' => $this->preTestPhase->id,
            ]))
            ->assertOk();

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('student.name', $this->studentB->name);
        $response->assertJsonPath('summary.correct_count', 1);
        $response->assertJsonPath('summary.incorrect_count', 1);
        $response->assertJsonPath('questions.0.is_correct', true);
        $response->assertJsonPath('questions.1.is_correct', false);
    }

    public function test_batch_analysis_page_renders_group_item_analysis_and_student_breakdown_actions(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('mock-boards.batch.analysis', [
                'program' => 'accountancy',
                'mock_board' => $this->board->id,
            ]))
            ->assertOk();

        // Should see Individual Student Results table and Item Analysis button
        $response->assertSee('Individual Student Performance');
        $response->assertSee('Item Analysis');
        $response->assertSee('openStudentItemModal');
        $response->assertSee($this->studentA->name);
        $response->assertSee($this->studentB->name);

        // Should see Group Item Analysis tab and Question Breakdown
        $response->assertSee('Item Analysis (By Group)');
        $response->assertSee('What is the fundamental accounting equation?');
        $response->assertSee('Which financial statement reports financial position?');
    }

    public function test_admin_dashboard_renders_post_test_passing_rate_by_program(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('adminDashboard'))
            ->assertOk();

        $response->assertDontSee('Overall Test Passing Rate');
        $response->assertSee('Post-Test Passing Rate by Program');
    }
}
