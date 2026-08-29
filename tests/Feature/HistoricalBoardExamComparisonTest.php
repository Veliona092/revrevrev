<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\HistoricalBoardExamResult;
use App\Models\MockBoard;
use App\Models\MockBoardAttempt;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for manually-entered historical board/licensure exam
 * results and their comparison against a mock board's own overall post-test
 * passing rate.
 */
class HistoricalBoardExamComparisonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    private MockBoard $mockBoard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher', 'program' => 'education']);
        $this->student = User::factory()->create(['role' => 'student', 'program' => 'education']);
        $this->class = ClassModel::factory()->create(['created_by' => $this->teacher->id]);
        $this->class->students()->attach($this->student->id);

        $this->mockBoard = MockBoard::create([
            'class_id' => $this->class->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Board With Historical Comparison',
            'program' => 'education',
            'review_period_start' => now()->subDay(),
            'review_period_end' => now()->addWeek(),
            'passing_percentage' => 75,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_create_a_historical_exam_result(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('historical-board-exams.store'), [
                'program' => 'education',
                'exam_label' => 'October 2024 LEPT',
                'exam_period_or_year' => '2024',
                'total_examinees' => 200,
                'passed_count' => 120,
                'source_note' => 'Typed from PRC bulletin, page 4',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('historical_board_exam_results', [
            'exam_label' => 'October 2024 LEPT',
            'total_examinees' => 200,
            'passed_count' => 120,
        ]);
    }

    public function test_teacher_cannot_create_a_historical_exam_result(): void
    {
        $this->actingAs($this->teacher)
            ->postJson(route('historical-board-exams.store'), [
                'program' => 'education',
                'exam_label' => 'October 2024 LEPT',
                'exam_period_or_year' => '2024',
                'total_examinees' => 200,
                'passed_count' => 120,
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_view_the_historical_results_list(): void
    {
        HistoricalBoardExamResult::create([
            'program' => 'education',
            'exam_label' => 'October 2024 LEPT',
            'exam_period_or_year' => '2024',
            'total_examinees' => 200,
            'passed_count' => 120,
            'entered_by' => $this->admin->id,
        ]);

        $this->actingAs($this->teacher)
            ->getJson(route('historical-board-exams.index'))
            ->assertOk()
            ->assertJsonPath('results.0.passing_rate', 60);
    }

    public function test_admin_can_delete_a_historical_exam_result(): void
    {
        $result = HistoricalBoardExamResult::create([
            'program' => 'education',
            'exam_label' => 'October 2024 LEPT',
            'exam_period_or_year' => '2024',
            'total_examinees' => 200,
            'passed_count' => 120,
            'entered_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('historical-board-exams.destroy', $result))
            ->assertOk();

        $this->assertDatabaseMissing('historical_board_exam_results', ['id' => $result->id]);
    }

    public function test_teacher_can_link_their_mock_board_to_a_matching_program_historical_result(): void
    {
        $result = HistoricalBoardExamResult::create([
            'program' => 'education',
            'exam_label' => 'October 2024 LEPT',
            'exam_period_or_year' => '2024',
            'total_examinees' => 200,
            'passed_count' => 120,
            'entered_by' => $this->admin->id,
        ]);

        $this->actingAs($this->teacher)
            ->postJson(route('student.mock-boards.link-historical-exam', $this->mockBoard), [
                'historical_board_exam_result_id' => $result->id,
            ])
            ->assertOk();

        $this->assertSame($result->id, $this->mockBoard->fresh()->historical_board_exam_result_id);
    }

    public function test_linking_to_a_different_program_historical_result_is_rejected(): void
    {
        $result = HistoricalBoardExamResult::create([
            'program' => 'accountancy',
            'exam_label' => 'October 2024 CPALE',
            'exam_period_or_year' => '2024',
            'total_examinees' => 200,
            'passed_count' => 120,
            'entered_by' => $this->admin->id,
        ]);

        $this->actingAs($this->teacher)
            ->postJson(route('student.mock-boards.link-historical-exam', $this->mockBoard), [
                'historical_board_exam_result_id' => $result->id,
            ])
            ->assertStatus(422);

        $this->assertNull($this->mockBoard->fresh()->historical_board_exam_result_id);
    }

    public function test_batch_analysis_shows_comparison_delta_when_linked(): void
    {
        $module = Module::factory()->create([
            'class_id' => null,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'is_mock_board' => true,
        ]);

        $phase = MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'phase_type' => 'pre_boards',
            'title' => 'Post-Test',
            'module_id' => $module->id,
        ]);

        MockBoardAttempt::create([
            'user_id' => $this->student->id,
            'mock_board_id' => $this->mockBoard->id,
            'mock_board_phase_id' => $phase->id,
            'phase_type' => 'pre_boards',
            'score' => 8,
            'total' => 10,
            'percentage' => 80,
            'passed' => true,
        ]);

        $result = HistoricalBoardExamResult::create([
            'program' => 'education',
            'exam_label' => 'October 2024 LEPT',
            'exam_period_or_year' => '2024',
            'total_examinees' => 200,
            'passed_count' => 140, // 70% historical passing rate
            'entered_by' => $this->admin->id,
        ]);

        $this->mockBoard->update(['historical_board_exam_result_id' => $result->id]);

        $response = $this->actingAs($this->teacher)
            ->get(route('mock-boards.batch.analysis', ['program' => $this->mockBoard->program, 'mock_board' => $this->mockBoard]))
            ->assertOk();

        $comparison = $response->viewData('historicalComparison');

        $this->assertNotNull($comparison);
        $this->assertSame(70.0, $comparison['historical_passing_rate']);
        // 1/1 student passed at their best score -> 100% Reviso rate.
        $this->assertSame(100.0, $comparison['reviso_passing_rate']);
        $this->assertSame(30.0, $comparison['delta']);
        $response->assertSee('October 2024 LEPT');
    }

    public function test_batch_analysis_shows_no_comparison_when_not_linked(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('mock-boards.batch.analysis', ['program' => $this->mockBoard->program, 'mock_board' => $this->mockBoard]))
            ->assertOk();

        $this->assertNull($response->viewData('historicalComparison'));
    }

    public function test_teacher_can_quick_benchmark_by_typing_passing_rate(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('mock-boards.quick-benchmark', $this->mockBoard), [
                'exam_label' => 'March 2024 BLEPP',
                'exam_period_or_year' => '2024',
                'passing_rate' => 72.5,
            ])
            ->assertRedirect();

        $this->mockBoard->refresh();
        $this->assertNotNull($this->mockBoard->historical_board_exam_result_id);
        $this->assertSame('March 2024 BLEPP', $this->mockBoard->historicalBoardExamResult->exam_label);
        $this->assertEquals(73, $this->mockBoard->historicalBoardExamResult->passed_count);
    }

    public function test_teacher_can_quick_benchmark_by_selecting_existing_id(): void
    {
        $result = HistoricalBoardExamResult::create([
            'program' => 'education',
            'exam_label' => 'Existing Exam',
            'exam_period_or_year' => '2023',
            'total_examinees' => 100,
            'passed_count' => 80,
            'entered_by' => $this->admin->id,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('mock-boards.quick-benchmark', $this->mockBoard), [
                'historical_board_exam_result_id' => $result->id,
            ])
            ->assertRedirect();

        $this->mockBoard->refresh();
        $this->assertSame($result->id, $this->mockBoard->historical_board_exam_result_id);
    }
}
