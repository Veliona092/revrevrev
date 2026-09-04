<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockBoardApprovalQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private MockBoard $mockBoard;

    private Module $preTestModule;

    private MockBoardPhase $phase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'program' => 'education',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'program' => 'education',
        ]);

        $class = ClassModel::factory()->create([
            'created_by' => $this->teacher->id,
            'program' => 'education',
        ]);

        $this->mockBoard = MockBoard::create([
            'teacher_id' => $this->teacher->id,
            'program' => 'education',
            'title' => 'LET Licensure Pre-Board 2026',
            'description' => 'Comprehensive LET examination',
            'passing_percentage' => 75,
            'status' => 'pending',
            'review_period_start' => now(),
            'review_period_end' => now()->addMonth(),
        ]);

        $this->preTestModule = Module::create([
            'class_id' => $class->id,
            'title' => 'LET Pre-Test',
            'is_formal_assessment' => true,
            'is_quiz' => true,
            'quiz_stage' => 'pre_test',
            'time_limit' => 60,
            'passing_grade' => 75,
        ]);

        $this->phase = MockBoardPhase::create([
            'mock_board_id' => $this->mockBoard->id,
            'phase_type' => 'pre_test',
            'sequence_number' => 1,
            'title' => 'Pre-Test Diagnostic',
            'label' => 'Pre-Test Diagnostic',
            'module_id' => $this->preTestModule->id,
        ]);

        QuizQuestion::create([
            'module_id' => $this->preTestModule->id,
            'quiz_stage' => 'pre_test',
            'question_text' => 'Which of the following is a primary color?',
            'options' => [
                'a' => 'Green',
                'b' => 'Red',
                'c' => 'Purple',
                'd' => 'Orange',
            ],
            'correct_option' => 'b',
            'points' => 1,
            'order' => 1,
            'domain' => 'General Education',
            'difficulty' => 'easy',
            'explanation' => 'Red is a primary color alongside blue and yellow.',
        ]);
    }

    public function test_admin_can_preview_mock_board_questions(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson(route('admin.mock-boards.questions', $this->mockBoard));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'mock_board' => [
                'id' => $this->mockBoard->id,
                'title' => 'LET Licensure Pre-Board 2026',
                'program' => 'education',
                'status' => 'pending',
                'phases' => [
                    [
                        'phase_type' => 'pre_test',
                        'label' => 'Pre-Test Diagnostic',
                        'total_questions' => 1,
                        'questions' => [
                            [
                                'question_text' => 'Which of the following is a primary color?',
                                'correct_option' => 'b',
                                'explanation' => 'Red is a primary color alongside blue and yellow.',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_non_admin_cannot_access_mock_board_questions_preview(): void
    {
        $this->actingAs($this->student);

        $response = $this->getJson(route('admin.mock-boards.questions', $this->mockBoard));

        $response->assertStatus(403);
    }

    public function test_admin_approvals_page_displays_view_questions_button(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.mock-boards.approvals', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('View Questions');
        $response->assertSee('LET Licensure Pre-Board 2026');
        $response->assertSee('questionsModal', false);
    }
}
