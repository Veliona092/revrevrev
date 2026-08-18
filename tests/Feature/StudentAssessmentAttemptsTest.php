<?php

namespace Tests\Feature;

use App\Models\AssessmentAttemptGrant;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAssessmentAttemptsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->class = ClassModel::factory()->create(['created_by' => $this->teacher->id]);
        $this->class->students()->attach($this->student->id);
    }

    private function createAssessment(int $maxAttempts = 1): Module
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'max_attempts' => $maxAttempts,
        ]);

        QuizQuestion::create([
            'module_id' => $module->id,
            'question_text' => 'Test Question',
            'options' => ['A' => 'Option A', 'B' => 'Option B', 'C' => 'Option C', 'D' => 'Option D'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
        ]);

        return $module;
    }

    private function createCompletedAttempt(Module $module, int $attemptCount): QuizAttempt
    {
        return QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $module->id,
            'attempt_count' => $attemptCount,
            'score' => 1,
            'total' => 1,
            'percentage' => 100,
            'passed' => true,
            'status' => 'completed',
        ]);
    }

    public function test_teacher_base_attempt_limit_is_persisted(): void
    {
        $module = $this->createAssessment();

        $this->actingAs($this->teacher)
            ->putJson("/modules/{$module->id}/quiz/max-attempts", ['max_attempts' => 3])
            ->assertOk()
            ->assertJsonPath('max_attempts', 3);

        $this->assertSame(3, $module->fresh()->max_attempts);
    }

    public function test_allowed_attempts_adds_teacher_grant_to_base_limit(): void
    {
        $module = $this->createAssessment(2);

        AssessmentAttemptGrant::create([
            'module_id' => $module->id,
            'user_id' => $this->student->id,
            'extra_attempts' => 2,
            'granted_by' => $this->teacher->id,
        ]);

        $this->assertSame(4, $module->allowedAttemptsFor($this->student->id));
    }

    public function test_assessment_list_shows_attempts_used_and_allowed(): void
    {
        $module = $this->createAssessment(3);
        $this->createCompletedAttempt($module, 1);

        $this->actingAs($this->student)
            ->get('/assessment')
            ->assertOk()
            ->assertSee('Attempts: 1 / 3');
    }

    public function test_assessment_list_offers_retake_while_attempts_remain(): void
    {
        $module = $this->createAssessment(3);
        $this->createCompletedAttempt($module, 1);

        $this->actingAs($this->student)
            ->get('/assessment')
            ->assertOk()
            ->assertSee('Retake (2 of 3)')
            ->assertDontSee('Attempts Used Up');
    }

    public function test_assessment_list_locks_card_once_attempts_are_exhausted(): void
    {
        $module = $this->createAssessment(2);
        $this->createCompletedAttempt($module, 2);

        $this->actingAs($this->student)
            ->get('/assessment')
            ->assertOk()
            ->assertSee('Attempts Used Up')
            ->assertDontSee('Retake (');
    }

    public function test_teacher_grant_reopens_an_exhausted_assessment(): void
    {
        $module = $this->createAssessment(1);
        $this->createCompletedAttempt($module, 1);

        AssessmentAttemptGrant::create([
            'module_id' => $module->id,
            'user_id' => $this->student->id,
            'extra_attempts' => 1,
            'granted_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->student)
            ->get('/assessment')
            ->assertOk()
            ->assertSee('Attempts: 1 / 2')
            ->assertSee('Retake (2 of 2)');
    }

    public function test_assessment_list_offers_resume_for_an_in_progress_attempt(): void
    {
        $module = $this->createAssessment(2);
        QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $module->id,
            'attempt_count' => 1,
            'score' => 0,
            'total' => 0,
            'percentage' => 0,
            'passed' => false,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->student)
            ->get('/assessment')
            ->assertOk()
            ->assertSee('Resume');
    }

    public function test_take_screen_shows_the_upcoming_attempt_number(): void
    {
        $module = $this->createAssessment(3);
        $this->createCompletedAttempt($module, 1);

        $this->actingAs($this->student)
            ->get("/assessment/{$module->id}")
            ->assertOk()
            ->assertSee('Attempt 2 of 3')
            ->assertSee('Retaking replaces your previous score');
    }

    public function test_take_screen_flags_the_final_attempt(): void
    {
        $module = $this->createAssessment(2);
        $this->createCompletedAttempt($module, 1);

        $this->actingAs($this->student)
            ->get("/assessment/{$module->id}")
            ->assertOk()
            ->assertSee('last attempt');
    }
}
