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

/**
 * Regression coverage for multi-post-test support on a single mock board.
 *
 * Before this change, MockBoardPhase enforced a unique(mock_board_id,
 * phase_type) constraint (so a board could only ever have one 'pre_boards'
 * phase), and MockBoardAttempt was keyed by (user_id, mock_board_id,
 * phase_type) — meaning even after removing that constraint, two post-test
 * phases on the same board would have silently collided onto a single
 * MockBoardAttempt row per student. This suite verifies both the schema
 * change and the phase-id-based attempt key actually prevent that collision.
 */
class MockBoardMultiPostTestTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    private MockBoard $mockBoard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher', 'program' => 'educ']);
        $this->student = User::factory()->create(['role' => 'student', 'program' => 'educ']);
        $this->class = ClassModel::factory()->create(['created_by' => $this->teacher->id]);
        $this->class->students()->attach($this->student->id);

        $this->mockBoard = MockBoard::create([
            'class_id' => $this->class->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Multi Post-Test Board',
            'program' => 'educ',
            'review_period_start' => now()->subDay(),
            'review_period_end' => now()->addWeek(),
            'passing_percentage' => 75,
            'status' => 'approved',
        ]);
    }

    public function test_teacher_can_add_a_second_post_test_phase_to_the_same_board(): void
    {
        $this->actingAs($this->teacher)
            ->postJson(route('student.mock-boards.phases.add', $this->mockBoard), [
                'phase_type' => 'pre_boards',
            ])
            ->assertRedirect();

        $this->actingAs($this->teacher)
            ->postJson(route('student.mock-boards.phases.add', $this->mockBoard), [
                'phase_type' => 'pre_boards',
            ])
            ->assertRedirect();

        $postTestPhases = $this->mockBoard->phases()->where('phase_type', 'pre_boards')->orderBy('sequence_number')->get();

        $this->assertCount(2, $postTestPhases);
        $this->assertSame(1, $postTestPhases[0]->sequence_number);
        $this->assertSame(2, $postTestPhases[1]->sequence_number);
        $this->assertSame('Post-Test 2', $postTestPhases[1]->phase_label);
    }

    public function test_a_second_pre_test_phase_is_still_rejected(): void
    {
        MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'phase_type' => 'pre_test',
            'title' => 'Pre-Test',
        ]);

        $this->actingAs($this->teacher)
            ->postJson(route('student.mock-boards.phases.add', $this->mockBoard), [
                'phase_type' => 'pre_test',
            ])
            ->assertRedirect();

        $this->assertCount(1, $this->mockBoard->phases()->where('phase_type', 'pre_test')->get());
    }

    /**
     * Creates a post-test phase with its own module and returns it.
     */
    private function createPostTestPhase(string $label): MockBoardPhase
    {
        $module = Module::factory()->create([
            'class_id' => null,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'is_mock_board' => true,
        ]);

        $sequence = $this->mockBoard->phases()->where('phase_type', 'pre_boards')->count() + 1;

        return MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'phase_type' => 'pre_boards',
            'sequence_number' => $sequence,
            'label' => $sequence > 1 ? $label : null,
            'title' => $label,
            'module_id' => $module->id,
        ]);
    }

    /**
     * Submits a full attempt (via the real /modules/{module}/quiz/submit
     * path, mirroring the live student flow) for the given phase, scoring
     * $correctCount out of $totalCount.
     */
    private function submitAttemptForPhase(MockBoardPhase $phase, int $correctCount, int $totalCount): void
    {
        $module = $phase->module;

        $attempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $module->id,
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
                'module_id' => $module->id,
                'question_text' => "Question {$phase->id}-{$attempt->id}-{$i}",
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
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'attempt_id' => $attempt->id,
                'total' => $totalCount,
            ])
            ->assertOk();
    }

    public function test_two_post_test_phases_do_not_collide_into_a_single_attempt_row(): void
    {
        $phaseOne = $this->createPostTestPhase('Post-Test 1');
        $phaseTwo = $this->createPostTestPhase('Post-Test 2');

        // Different scores on each phase.
        $this->submitAttemptForPhase($phaseOne, 9, 10); // 90%
        $this->submitAttemptForPhase($phaseTwo, 5, 10); // 50%

        $attempts = MockBoardAttempt::where('user_id', $this->student->id)
            ->where('mock_board_id', $this->mockBoard->id)
            ->get()
            ->keyBy('mock_board_phase_id');

        // Both phases must have kept their OWN independent score — this is
        // exactly the collision the old (user_id, mock_board_id, phase_type)
        // key would have caused (both being phase_type = 'pre_boards').
        $this->assertCount(2, $attempts);
        $this->assertSame(90, $attempts->get($phaseOne->id)->percentage);
        $this->assertSame(50, $attempts->get($phaseTwo->id)->percentage);
    }

    public function test_take_route_resolves_the_correct_phase_and_module_by_id(): void
    {
        $phaseOne = $this->createPostTestPhase('Post-Test 1');
        $phaseTwo = $this->createPostTestPhase('Post-Test 2');

        $this->actingAs($this->student)
            ->get(route('student.mock-boards.take', [$this->mockBoard, $phaseOne]))
            ->assertOk()
            ->assertViewHas('module', fn ($module) => $module->id === $phaseOne->module_id);

        $this->actingAs($this->student)
            ->get(route('student.mock-boards.take', [$this->mockBoard, $phaseTwo]))
            ->assertOk()
            ->assertViewHas('module', fn ($module) => $module->id === $phaseTwo->module_id);
    }

    public function test_student_index_page_renders_with_multiple_post_test_phases(): void
    {
        $phaseOne = $this->createPostTestPhase('Post-Test 1');
        $phaseTwo = $this->createPostTestPhase('Post-Test 2');

        $response = $this->actingAs($this->student)
            ->get(route('student.mock-boards.index'))
            ->assertOk();

        $response->assertSee($phaseOne->phase_label);
        $response->assertSee($phaseTwo->phase_label);
        $response->assertSee(route('student.mock-boards.take', [$this->mockBoard->id, $phaseOne->id]));
    }

    public function test_student_can_complete_first_post_test_and_then_take_second_post_test(): void
    {
        $phaseOne = $this->createPostTestPhase('Post-Test 1');
        $phaseTwo = $this->createPostTestPhase('Post-Test 2');

        // Student completes Post-Test 1
        $this->submitAttemptForPhase($phaseOne, 9, 10); // 90%

        // Index page should show Post-Test 1 completed and Post-Test 2 available to take
        $response = $this->actingAs($this->student)
            ->get(route('student.mock-boards.index'))
            ->assertOk();

        $response->assertSee('90% (Passed)');
        $response->assertSee($phaseTwo->phase_label);
        $response->assertSee(route('student.mock-boards.take', [$this->mockBoard->id, $phaseTwo->id]));
        $response->assertSee(route('student.mock-boards.results', $this->mockBoard->id));

        // Student takes Post-Test 2
        $this->submitAttemptForPhase($phaseTwo, 8, 10); // 80%

        // Now both are completed
        $responseAllComplete = $this->actingAs($this->student)
            ->get(route('student.mock-boards.index'))
            ->assertOk();

        $responseAllComplete->assertSee('90% (Passed)');
        $responseAllComplete->assertSee('80% (Passed)');
        $responseAllComplete->assertSee('View Performance Results');
    }

    public function test_results_page_renders_with_per_phase_breakdown_for_multiple_post_tests(): void
    {
        $phaseOne = $this->createPostTestPhase('Post-Test 1');
        $phaseTwo = $this->createPostTestPhase('Post-Test 2');

        $this->submitAttemptForPhase($phaseOne, 9, 10); // 90%
        $this->submitAttemptForPhase($phaseTwo, 5, 10); // 50%

        $response = $this->actingAs($this->student)
            ->get(route('student.mock-boards.results', $this->mockBoard))
            ->assertOk();

        $phasesDetail = $response->viewData('phasesDetail');
        $this->assertCount(2, $phasesDetail);

        $overallPostTest = $response->viewData('overallPostTest');
        $this->assertNotNull($overallPostTest);
        // Best score across the two post-test phases must be used, not the average.
        $this->assertSame(90, $overallPostTest['best_percentage']);
        $this->assertTrue($overallPostTest['passed']);
        $this->assertSame(2, $overallPostTest['phases_attempted']);
        $this->assertSame(2, $overallPostTest['phases_total']);

        // Assert that both phase score cards and details are rendered in HTML
        $response->assertSee("scoreCard_{$phaseOne->id}");
        $response->assertSee("scoreCard_{$phaseTwo->id}");
        $response->assertSee($phaseOne->phase_label);
        $response->assertSee($phaseTwo->phase_label);
        $response->assertSee('90%');
        $response->assertSee('50%');
        $response->assertSee('Best Score: 90%');
        $response->assertSee('Passed Overall');
        $response->assertSee('2 of 2 Post-Tests completed');
    }

    public function test_student_with_pre_test_and_multiple_post_tests_sees_growth_comparison(): void
    {
        // 1. Create Pre-Test
        $preTestModule = Module::factory()->create([
            'class_id' => null,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'is_mock_board' => true,
        ]);
        $preTestPhase = MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'phase_type' => 'pre_test',
            'sequence_number' => 1,
            'title' => 'Pre-Test',
            'module_id' => $preTestModule->id,
        ]);

        // 2. Create two post-tests
        $postTest1 = $this->createPostTestPhase('Post-Test 1');
        $postTest2 = $this->createPostTestPhase('Post-Test 2');

        // Submit Pre-Test (60%), Post-Test 1 (70%), Post-Test 2 (85%)
        $this->submitAttemptForPhase($preTestPhase, 6, 10);
        $this->submitAttemptForPhase($postTest1, 7, 10);
        $this->submitAttemptForPhase($postTest2, 8, 10); // 80%

        $response = $this->actingAs($this->student)
            ->get(route('student.mock-boards.results', $this->mockBoard))
            ->assertOk();

        // Growth is Best Post-Test (80%) - Pre-Test (60%) = +20%
        $response->assertSee('+20%');
        $response->assertSee('improvement from Pre-Test (60%) to Best Post-Test (80%)');
    }

    public function test_overall_post_test_passing_rate_uses_best_score_per_student_across_phases(): void
    {
        $phaseOne = $this->createPostTestPhase('Post-Test 1');
        $phaseTwo = $this->createPostTestPhase('Post-Test 2');

        // Student A: 90% then 50% -> best is 90%, passes (threshold 75%).
        $this->submitAttemptForPhase($phaseOne, 9, 10);
        $this->submitAttemptForPhase($phaseTwo, 5, 10);

        // Student B: attempts only phase two, scores 60% -> fails.
        $studentB = User::factory()->create(['role' => 'student', 'program' => 'educ']);
        $this->class->students()->attach($studentB->id);

        $moduleTwo = $phaseTwo->module;
        $attempt = QuizAttempt::create([
            'user_id' => $studentB->id,
            'module_id' => $moduleTwo->id,
            'mock_board_id' => $this->mockBoard->id,
            'score' => 0,
            'total' => 0,
            'percentage' => 0,
            'passed' => false,
            'attempt_count' => 1,
        ]);

        for ($i = 0; $i < 10; $i++) {
            $question = QuizQuestion::create([
                'module_id' => $moduleTwo->id,
                'question_text' => "StudentB-Q{$i}",
                'options' => ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'],
                'correct_option' => 'A',
                'points' => 1,
                'order' => $i,
            ]);

            QuizAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option' => $i < 6 ? 'A' : 'B',
                'is_correct' => $i < 6,
            ]);
        }

        $this->actingAs($studentB)
            ->postJson("/modules/{$moduleTwo->id}/quiz/submit", [
                'attempt_id' => $attempt->id,
                'total' => 10,
            ])
            ->assertOk();

        $stats = app(MockBoardStatisticsService::class)
            ->computeOverallPostTestPassingRate($this->mockBoard->fresh());

        $this->assertSame(2, $stats['students_attempted']);
        $this->assertSame(1, $stats['students_passed']);
        $this->assertSame(50.0, $stats['overall_passing_rate']);
        $this->assertSame(2, $stats['phases_total']);
    }

    public function test_batch_analysis_page_renders_with_combined_post_test_stat(): void
    {
        $phaseOne = $this->createPostTestPhase('Post-Test 1');
        $phaseTwo = $this->createPostTestPhase('Post-Test 2');

        $this->submitAttemptForPhase($phaseOne, 9, 10); // 90%
        $this->submitAttemptForPhase($phaseTwo, 5, 10); // 50%

        $response = $this->actingAs($this->teacher)
            ->get(route('mock-boards.batch.analysis', ['program' => $this->mockBoard->program, 'mock_board' => $this->mockBoard]))
            ->assertOk();

        $stats = $response->viewData('overallPostTestStats');
        $this->assertSame(2, $stats['phases_total']);
        $this->assertSame(1, $stats['students_attempted']);
        $response->assertSee('Overall Post-Test Passing Rate');
    }

    public function test_teacher_dashboard_renders_with_multiple_post_test_phases_and_add_button(): void
    {
        $this->createPostTestPhase('Post-Test 1');
        $this->createPostTestPhase('Post-Test 2');

        $response = $this->actingAs($this->teacher)
            ->get(route('student.mock-boards.index'))
            ->assertOk();

        $response->assertSee('Add Post-Test');
        $response->assertSee('Post-Test 2');
    }
}
