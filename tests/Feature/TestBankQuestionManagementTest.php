<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\QuizQuestion;
use App\Models\TestBankQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestBankQuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 4000;

    private function createTeacher(): User
    {
        $sequence = ++self::$sequence;

        return User::query()->create([
            'idnumber' => 'TCH'.$sequence,
            'name' => 'Teacher '.$sequence,
            'email' => 'teacher'.$sequence.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'program' => 'accountancy',
            'status' => 'active',
            'program_locked' => false,
        ]);
    }

    private function createQuizModule(User $teacher): Module
    {
        return Module::query()->create([
            'title' => 'Pre-Test Assessment',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'assessment_purpose' => 'pre_test',
            'created_by' => $teacher->id,
        ]);
    }

    public function test_teacher_can_save_a_question_to_the_test_bank(): void
    {
        $teacher = $this->createTeacher();

        $response = $this->actingAs($teacher)->post(route('test-bank.store'), [
            'program' => 'accountancy',
            'question_text' => 'Why is audit evidence evaluated?',
            'options' => ['A' => 'To support conclusions', 'B' => 'To skip planning', 'C' => 'To avoid testing', 'D' => 'To replace reports'],
            'correct_option' => 'A',
            'points' => 1,
            'difficulty' => 'Normal',
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('test-bank.index'));
        $this->assertDatabaseHas('test_bank_questions', [
            'created_by' => $teacher->id,
            'program' => 'accountancy',
            'difficulty' => 'Normal',
        ]);
    }

    public function test_teacher_can_copy_test_bank_questions_to_an_assessment_snapshot(): void
    {
        $teacher = $this->createTeacher();
        $module = $this->createQuizModule($teacher);
        $testBankQuestion = TestBankQuestion::factory()->create([
            'created_by' => $teacher->id,
            'question_text' => 'Original Test Bank Question',
            'difficulty' => 'Hard',
        ]);

        $this->actingAs($teacher)
            ->post(route('test-bank.modules.questions.store', $module), [
                'test_bank_question_ids' => [$testBankQuestion->id],
            ])
            ->assertRedirect(route('quiz.create', $module));

        $snapshot = QuizQuestion::query()->sole();
        $this->assertSame($testBankQuestion->id, $snapshot->test_bank_question_id);
        $this->assertSame('Original Test Bank Question', $snapshot->question_text);

        $testBankQuestion->update(['question_text' => 'Changed Test Bank Question']);

        $this->assertSame('Original Test Bank Question', $snapshot->refresh()->question_text);
    }

    public function test_teacher_can_import_existing_quiz_questions_into_the_test_bank(): void
    {
        $teacher = $this->createTeacher();
        $module = $this->createQuizModule($teacher);
        $question = QuizQuestion::query()->create([
            'module_id' => $module->id,
            'question_text' => 'Existing AI-generated question',
            'options' => ['A' => 'Correct', 'B' => 'Wrong', 'C' => 'Wrong', 'D' => 'Wrong'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
        ]);

        $this->actingAs($teacher)
            ->get(route('test-bank.index'))
            ->assertOk()
            ->assertSee('Test Bank');

        $this->actingAs($teacher)
            ->post(route('test-bank.modules.import', $module))
            ->assertRedirect(route('test-bank.index'));

        $this->assertDatabaseHas('test_bank_questions', [
            'created_by' => $teacher->id,
            'question_text' => 'Existing AI-generated question',
        ]);
        $this->assertNotNull($question->refresh()->test_bank_question_id);
    }
}
