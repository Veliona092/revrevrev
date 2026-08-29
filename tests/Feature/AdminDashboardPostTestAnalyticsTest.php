<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\MockBoardAttempt;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardPostTestAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'program' => 'accountancy',
        ]);
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);

        $this->actingAs($student)
            ->get(route('adminDashboard'))
            ->assertForbidden();
    }

    public function test_admin_dashboard_renders_overall_and_by_program_post_test_passing_rates(): void
    {
        // 1. Setup Accountancy Mock Board & Class
        $accClass = ClassModel::factory()->create(['program' => 'accountancy', 'created_by' => $this->teacher->id]);
        $accBoard = MockBoard::create([
            'title' => 'BSA Mock Board 2026',
            'program' => 'accountancy',
            'class_id' => $accClass->id,
            'teacher_id' => $this->teacher->id,
            'passing_percentage' => 75,
            'review_period_start' => now()->subDays(5),
            'review_period_end' => now()->addDays(5),
            'status' => 'approved',
        ]);
        $accModule = Module::factory()->create(['is_quiz' => true, 'is_formal_assessment' => true, 'is_mock_board' => true]);
        $accPhase = MockBoardPhase::create([
            'mock_board_id' => $accBoard->id,
            'phase_type' => 'pre_boards',
            'sequence_number' => 1,
            'title' => 'Post-Test 1',
            'module_id' => $accModule->id,
        ]);

        // Student A (80% - Passed) and Student B (60% - Failed)
        $accStudentA = User::factory()->create(['role' => 'student', 'program' => 'accountancy', 'status' => 'active']);
        $accStudentB = User::factory()->create(['role' => 'student', 'program' => 'accountancy', 'status' => 'active']);

        MockBoardAttempt::create([
            'user_id' => $accStudentA->id,
            'mock_board_id' => $accBoard->id,
            'mock_board_phase_id' => $accPhase->id,
            'phase_type' => 'pre_boards',
            'score' => 8,
            'total_questions' => 10,
            'percentage' => 80,
            'passed' => true,
            'attempt_count' => 1,
        ]);

        MockBoardAttempt::create([
            'user_id' => $accStudentB->id,
            'mock_board_id' => $accBoard->id,
            'mock_board_phase_id' => $accPhase->id,
            'phase_type' => 'pre_boards',
            'score' => 6,
            'total_questions' => 10,
            'percentage' => 60,
            'passed' => false,
            'attempt_count' => 1,
        ]);

        // 2. Setup Education Mock Board with Multi Post-Tests
        $educClass = ClassModel::factory()->create(['program' => 'educ', 'created_by' => $this->teacher->id]);
        $educBoard = MockBoard::create([
            'title' => 'LEPT Mock Board 2026',
            'program' => 'educ',
            'class_id' => $educClass->id,
            'teacher_id' => $this->teacher->id,
            'passing_percentage' => 75,
            'review_period_start' => now()->subDays(5),
            'review_period_end' => now()->addDays(5),
            'status' => 'approved',
        ]);
        $educModule1 = Module::factory()->create(['is_quiz' => true, 'is_formal_assessment' => true, 'is_mock_board' => true]);
        $educPhase1 = MockBoardPhase::create([
            'mock_board_id' => $educBoard->id,
            'phase_type' => 'pre_boards',
            'sequence_number' => 1,
            'title' => 'Post-Test 1',
            'module_id' => $educModule1->id,
        ]);
        $educModule2 = Module::factory()->create(['is_quiz' => true, 'is_formal_assessment' => true, 'is_mock_board' => true]);
        $educPhase2 = MockBoardPhase::create([
            'mock_board_id' => $educBoard->id,
            'phase_type' => 'pre_boards',
            'sequence_number' => 2,
            'label' => 'Post-Test 2',
            'title' => 'Post-Test 2',
            'module_id' => $educModule2->id,
        ]);

        // Student C: Post-Test 1 (70% - fail), Post-Test 2 (90% - pass) -> Best is 90%, so Student C PASSED
        $educStudentC = User::factory()->create(['role' => 'student', 'program' => 'educ', 'status' => 'active']);

        MockBoardAttempt::create([
            'user_id' => $educStudentC->id,
            'mock_board_id' => $educBoard->id,
            'mock_board_phase_id' => $educPhase1->id,
            'phase_type' => 'pre_boards',
            'score' => 7,
            'total_questions' => 10,
            'percentage' => 70,
            'passed' => false,
            'attempt_count' => 1,
        ]);

        MockBoardAttempt::create([
            'user_id' => $educStudentC->id,
            'mock_board_id' => $educBoard->id,
            'mock_board_phase_id' => $educPhase2->id,
            'phase_type' => 'pre_boards',
            'score' => 9,
            'total_questions' => 10,
            'percentage' => 90,
            'passed' => true,
            'attempt_count' => 1,
        ]);

        // Access dashboard as Admin
        $response = $this->actingAs($this->admin)
            ->get(route('adminDashboard'))
            ->assertOk();

        // Check view data
        $postTestAnalytics = $response->viewData('postTestAnalytics');
        $this->assertNotNull($postTestAnalytics);

        // Total takers across all boards: Student A, Student B, Student C = 3 students
        // Total passed: Student A (80%), Student C (90%) = 2 passed
        // Overall passing rate: 2/3 = 66.7%
        $this->assertSame(3, $postTestAnalytics['overall']['students_attempted']);
        $this->assertSame(2, $postTestAnalytics['overall']['students_passed']);
        $this->assertEquals(66.7, $postTestAnalytics['overall']['passing_rate']);

        // Accountancy program stats: 1 passed out of 2 = 50.0%
        $this->assertSame(2, $postTestAnalytics['by_program']['accountancy']['students_attempted']);
        $this->assertSame(1, $postTestAnalytics['by_program']['accountancy']['students_passed']);
        $this->assertEquals(50.0, $postTestAnalytics['by_program']['accountancy']['passing_rate']);

        // Education program stats: 1 passed out of 1 = 100.0%
        $this->assertSame(1, $postTestAnalytics['by_program']['education']['students_attempted']);
        $this->assertSame(1, $postTestAnalytics['by_program']['education']['students_passed']);
        $this->assertEquals(100.0, $postTestAnalytics['by_program']['education']['passing_rate']);

        // Psychology program stats: 0 takers = 0.0%
        $this->assertSame(0, $postTestAnalytics['by_program']['psychology']['students_attempted']);
        $this->assertEquals(0.0, $postTestAnalytics['by_program']['psychology']['passing_rate']);

        // Assert rendered HTML elements
        $response->assertDontSee('Overall Test Passing Rate');
        $response->assertSee('Post-Test Passing Rate by Program');
        $response->assertSee('Accountancy (BSA)');
        $response->assertSee('Education (BSED/BEED)');
        $response->assertSee('Psychology (BS Psych)');
        $response->assertSee('50%');
        $response->assertSee('100%');
    }

    public function test_admin_views_batch_analytics_dashboard_with_admin_layout(): void
    {
        $class1 = ClassModel::factory()->create(['program' => 'accountancy', 'created_by' => $this->teacher->id]);
        MockBoard::create([
            'title' => 'BSA Mock Board 2026',
            'program' => 'accountancy',
            'class_id' => $class1->id,
            'teacher_id' => $this->teacher->id,
            'passing_percentage' => 75,
            'review_period_start' => now()->subDays(5),
            'review_period_end' => now()->addDays(5),
            'status' => 'approved',
        ]);

        $class2 = ClassModel::factory()->create(['program' => 'educ', 'created_by' => $this->teacher->id]);
        MockBoard::create([
            'title' => 'LEPT Mock Board 2026',
            'program' => 'educ',
            'class_id' => $class2->id,
            'teacher_id' => $this->teacher->id,
            'passing_percentage' => 75,
            'review_period_start' => now()->subDays(5),
            'review_period_end' => now()->addDays(5),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('mock-boards.batch.dashboard'))
            ->assertOk();

        // Should use Admin Layout
        $response->assertSee('Back to Admin Dashboard');
        $response->assertSee(route('adminDashboard'));
        $response->assertSee('Accountancy (BSA)');
        $response->assertSee('Education (BSED/BEED)');
    }

    public function test_admin_views_batch_analysis_page_with_admin_layout(): void
    {
        $class = ClassModel::factory()->create(['program' => 'accountancy', 'created_by' => $this->teacher->id]);
        $board = MockBoard::create([
            'title' => 'BSA Mock Board 2026',
            'program' => 'accountancy',
            'class_id' => $class->id,
            'teacher_id' => $this->teacher->id,
            'passing_percentage' => 75,
            'review_period_start' => now()->subDays(5),
            'review_period_end' => now()->addDays(5),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('mock-boards.batch.analysis', ['program' => 'accountancy', 'mock_board' => $board->id]))
            ->assertOk();

        // Should use Admin Layout
        $response->assertSee('Back to Dashboard');
        $response->assertSee(route('mock-boards.batch.dashboard', ['program' => 'accountancy']));
    }
}
