<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Module;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherAssessmentEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_existing_assessment_with_preloaded_questions(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);

        $class = ClassModel::query()->create([
            'name' => 'Assessment Class',
            'code' => 'ASM101',
            'school_year' => now()->year,
            'description' => 'Class for editing assessments',
            'created_by' => $teacher->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Midterm Assessment',
            'description' => 'Assessment module',
            'is_quiz' => true,
            'is_formal_assessment' => true,
        ]);

        QuizQuestion::query()->create([
            'module_id' => $module->id,
            'question_text' => 'Original question text',
            'options' => ['A' => 'One', 'B' => 'Two', 'C' => 'Three', 'D' => 'Four'],
            'correct_option' => 'B',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('quiz.create', $module));

        $response->assertOk();
        $response->assertSee('Edit Assessment');
        $response->assertSee('Original question text');
        $response->assertSee('Midterm Assessment');
    }

    public function test_saving_edited_assessment_replaces_existing_questions(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);

        $class = ClassModel::query()->create([
            'name' => 'Assessment Class',
            'code' => 'ASM102',
            'school_year' => now()->year,
            'description' => 'Class for editing assessments',
            'created_by' => $teacher->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Final Assessment',
            'description' => 'Assessment module',
            'is_quiz' => true,
            'is_formal_assessment' => true,
        ]);

        QuizQuestion::query()->create([
            'module_id' => $module->id,
            'question_text' => 'Old question',
            'options' => ['A' => 'Old A', 'B' => 'Old B', 'C' => 'Old C', 'D' => 'Old D'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
        ]);

        $this->actingAs($teacher)
            ->post(route('quiz.store', $module), [
                'questions' => [
                    [
                        'text' => 'Updated question',
                        'options' => [
                            'A' => 'New A',
                            'B' => 'New B',
                            'C' => 'New C',
                            'D' => 'New D',
                        ],
                        'correct' => 'C',
                        'points' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('quiz.create', $module));

        $this->assertDatabaseMissing('quiz_questions', [
            'module_id' => $module->id,
            'question_text' => 'Old question',
        ]);

        $this->assertDatabaseHas('quiz_questions', [
            'module_id' => $module->id,
            'question_text' => 'Updated question',
            'correct_option' => 'C',
            'order' => 1,
        ]);

        $this->assertSame(1, QuizQuestion::query()->where('module_id', $module->id)->count());
    }

    private function createUser(array $overrides = []): User
    {
        static $counter = 4000;
        $counter++;

        return User::query()->create(array_merge([
            'idnumber' => 'ID'.$counter,
            'name' => 'User '.$counter,
            'email' => 'user'.$counter.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'program' => 'educ',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }
}
