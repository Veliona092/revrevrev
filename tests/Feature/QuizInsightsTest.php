<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Module;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\CloudflareAI;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class QuizInsightsTest extends TestCase
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

    public function test_generate_insights_returns_fallback_when_ai_call_fails(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $this->student->id,
            'module_id' => $module->id,
            'score' => 1,
            'total' => 2,
            'percentage' => 50,
            'passed' => true,
        ]);

        $question = QuizQuestion::create([
            'module_id' => $module->id,
            'question_text' => 'What is bookkeeping?',
            'options' => ['A' => 'A process', 'B' => 'A place', 'C' => 'A person', 'D' => 'A color'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
        ]);

        QuizAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option' => 'B',
            'is_correct' => false,
        ]);

        $this->app->instance('App\\Services\\CloudflareAI', new class extends CloudflareAI
        {
            public function run(string $model, array $payload): array
            {
                throw new RuntimeException('AI service unavailable');
            }
        });

        $this->actingAs($this->student)
            ->postJson(route('quiz.insights', $module))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recommendation', 'Review the incorrect questions and revisit the related lesson sections before the next attempt.');

        $attempt->refresh();
        $this->assertNotNull($attempt->ai_strong);
        $this->assertNotNull($attempt->ai_weak);
        $this->assertNotNull($attempt->ai_recommendation);
    }

    public function test_generate_insights_returns_generic_fallback_when_attempt_is_missing(): void
    {
        $module = Module::factory()->create([
            'class_id' => $this->class->id,
            'is_quiz' => true,
            'is_formal_assessment' => false,
        ]);

        $this->actingAs($this->student)
            ->postJson(route('quiz.insights', $module))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('weak', 'Detailed answer analysis could not be loaded for this attempt.');
    }
}
