<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Module;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizPassingGradeTest extends TestCase
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

    private function createQuizQuestion(Module $module, string $correctOption = 'A'): QuizQuestion
    {
        static $questionNum = 0;
        $questionNum++;

        return QuizQuestion::create([
            'module_id' => $module->id,
            'question_text' => 'Test Question '.$questionNum,
            'options' => ['A' => 'Option A', 'B' => 'Option B', 'C' => 'Option C', 'D' => 'Option D'],
            'correct_option' => $correctOption,
            'points' => 1,
            'order' => $questionNum,
        ]);
    }

    private function createAttemptWithAnswers(Module $module, int $correctCount, int $totalCount): QuizAttempt
    {
        $attempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $module->id,
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

            $question = $this->createQuizQuestion($module, $correctOption);

            QuizAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option' => $selectedOption,
                'is_correct' => $i < $correctCount,
            ]);
        }

        return $attempt;
    }

    public function test_module_with_custom_passing_grade_uses_module_threshold(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
            'passing_grade' => 70, // Custom threshold: 70%
        ]);

        // Create 7/10 correct answers (70%) before submitting
        $this->createAttemptWithAnswers($module, 7, 10);

        $this->actingAs($this->student)
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'total' => 10,
            ])
            ->assertJsonPath('passed', true);

        $attempt = QuizAttempt::where('user_id', $this->student->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertTrue($attempt->passed);
        $this->assertEquals(70, $attempt->percentage);
    }

    public function test_module_with_custom_passing_grade_fails_below_threshold(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
            'passing_grade' => 80, // Custom threshold: 80%
        ]);

        // Create 75/100 correct answers (75%) - should fail with 80% threshold
        $this->createAttemptWithAnswers($module, 75, 100);

        $this->actingAs($this->student)
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'total' => 100,
            ])
            ->assertJsonPath('passed', false);

        $attempt = QuizAttempt::where('user_id', $this->student->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertFalse($attempt->passed);
    }

    public function test_module_without_passing_grade_uses_config_default(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
            'passing_grade' => null, // No custom threshold - use config
        ]);

        // Create 60/100 correct answers (60%) - should pass with default 50% threshold
        $this->createAttemptWithAnswers($module, 60, 100);

        $this->actingAs($this->student)
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'total' => 100,
            ])
            ->assertJsonPath('passed', true);

        $attempt = QuizAttempt::where('user_id', $this->student->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertTrue($attempt->passed);
    }

    public function test_null_passing_grade_falls_back_to_config_default(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
            'passing_grade' => null,
        ]);

        // Create 40/100 correct answers (40%) - should fail with default 50% threshold
        $this->createAttemptWithAnswers($module, 40, 100);

        $this->actingAs($this->student)
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'total' => 100,
            ])
            ->assertJsonPath('passed', false);

        $attempt = QuizAttempt::where('user_id', $this->student->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertFalse($attempt->passed);
    }

    public function test_formal_assessment_respects_passing_grade(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'passing_grade' => 75,
        ]);

        // Create 15/20 correct answers (75%) - should pass with 75% threshold
        $this->createAttemptWithAnswers($module, 15, 20);

        $this->actingAs($this->student)
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'total' => 20,
            ])
            ->assertJsonPath('passed', true);

        $attempt = QuizAttempt::where('user_id', $this->student->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertTrue($attempt->passed);
    }

    public function test_edge_case_100_percent_score_always_passes(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
            'passing_grade' => 100,
        ]);

        // Create 10/10 correct answers (100%) - should pass even with 100% threshold
        $this->createAttemptWithAnswers($module, 10, 10);

        $this->actingAs($this->student)
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'total' => 10,
            ])
            ->assertJsonPath('passed', true);
    }

    public function test_edge_case_zero_percent_score_always_fails(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
            'passing_grade' => 1, // Even lowest threshold
        ]);

        // Create 0/10 correct answers (0%) - should fail even with 1% threshold
        $this->createAttemptWithAnswers($module, 0, 10);

        $this->actingAs($this->student)
            ->postJson("/modules/{$module->id}/quiz/submit", [
                'total' => 10,
            ])
            ->assertJsonPath('passed', false);
    }
}
