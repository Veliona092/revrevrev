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

class QuizAnswerRecordingTest extends TestCase
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

    public function test_assessment_answer_is_recorded_with_correctness_and_normalized_option(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => true,
        ]);

        $question = QuizQuestion::create([
            'module_id' => $module->id,
            'question_text' => 'Which option is correct?',
            'options' => ['A' => 'Alpha', 'B' => 'Beta', 'C' => 'Gamma', 'D' => 'Delta'],
            'correct_option' => 'B',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
        ]);

        $this->actingAs($this->student)
            ->postJson(route('quiz.answer', $module), [
                'question_id' => $question->id,
                'selected_option' => 'b',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('isCorrect', true);

        $attempt = QuizAttempt::query()
            ->where('user_id', $this->student->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertNotNull($attempt);

        $answer = QuizAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($answer);
        $this->assertSame('B', $answer->selected_option);
        $this->assertTrue($answer->is_correct);
    }

    public function test_resubmitting_same_question_updates_existing_record_instead_of_creating_duplicate(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => true,
        ]);

        $question = QuizQuestion::create([
            'module_id' => $module->id,
            'question_text' => 'Pick one.',
            'options' => ['A' => 'Alpha', 'B' => 'Beta', 'C' => 'Gamma', 'D' => 'Delta'],
            'correct_option' => 'C',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
        ]);

        $this->actingAs($this->student)
            ->postJson(route('quiz.answer', $module), [
                'question_id' => $question->id,
                'selected_option' => 'A',
            ])
            ->assertOk();

        $this->actingAs($this->student)
            ->postJson(route('quiz.answer', $module), [
                'question_id' => $question->id,
                'selected_option' => 'C',
            ])
            ->assertOk()
            ->assertJsonPath('isCorrect', true);

        $attempt = QuizAttempt::query()
            ->where('user_id', $this->student->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertNotNull($attempt);

        $answers = QuizAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->get();

        $this->assertCount(1, $answers);
        $this->assertSame('C', $answers->first()->selected_option);
        $this->assertTrue($answers->first()->is_correct);
    }

    public function test_question_from_other_module_is_rejected(): void
    {
        $assessmentModule = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => true,
        ]);

        $otherModule = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => true,
        ]);

        $question = QuizQuestion::create([
            'module_id' => $otherModule->id,
            'question_text' => 'Foreign question',
            'options' => ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
        ]);

        $this->actingAs($this->student)
            ->postJson(route('quiz.answer', $assessmentModule), [
                'question_id' => $question->id,
                'selected_option' => 'A',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
