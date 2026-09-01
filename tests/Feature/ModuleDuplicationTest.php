<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModuleDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_duplicate_formal_assessment_with_questions(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser(['role' => 'student', 'program' => 'educ']);

        $class = ClassModel::query()->create([
            'name' => 'Duplication Test Class',
            'code' => 'DUP101',
            'school_year' => now()->year,
            'description' => 'Class for testing duplication',
            'created_by' => $teacher->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Final Board Exam',
            'description' => 'Comprehensive evaluation',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'time_limit' => 60,
            'passing_grade' => 75,
            'max_attempts' => 2,
            'created_by' => $teacher->id,
        ]);

        QuizQuestion::query()->create([
            'module_id' => $module->id,
            'question_text' => 'Sample question 1?',
            'options' => ['A' => 'Opt 1', 'B' => 'Opt 2', 'C' => 'Opt 3', 'D' => 'Opt 4'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
            'explanation' => 'Explanation 1',
        ]);

        QuizQuestion::query()->create([
            'module_id' => $module->id,
            'question_text' => 'Sample question 2?',
            'options' => ['A' => 'Opt A', 'B' => 'Opt B', 'C' => 'Opt C', 'D' => 'Opt D'],
            'correct_option' => 'C',
            'points' => 2,
            'order' => 2,
            'explanation' => 'Explanation 2',
        ]);

        // Create an attempt on the original module
        QuizAttempt::query()->create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'score' => 3,
            'total' => 2,
            'percentage' => 100,
        ]);

        ModuleProgress::query()->create([
            'module_id' => $module->id,
            'user_id' => $student->id,
            'progress' => 100,
            'completed' => true,
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('modules.duplicate', $module));

        $response->assertRedirect();

        // Check new module in DB
        $duplicated = Module::query()
            ->where('class_id', $class->id)
            ->where('id', '!=', $module->id)
            ->first();

        $this->assertNotNull($duplicated);
        $this->assertSame('Final Board Exam (Copy)', $duplicated->title);
        $this->assertTrue($duplicated->is_formal_assessment);
        $this->assertTrue($duplicated->is_quiz);
        $this->assertSame(60, $duplicated->time_limit);
        $this->assertSame(75, $duplicated->passing_grade);
        $this->assertSame(2, $duplicated->max_attempts);
        $this->assertNull($duplicated->due_date);

        // Check questions copied
        $this->assertSame(2, QuizQuestion::query()->where('module_id', $duplicated->id)->count());
        $this->assertDatabaseHas('quiz_questions', [
            'module_id' => $duplicated->id,
            'question_text' => 'Sample question 1?',
            'correct_option' => 'A',
            'points' => 1,
        ]);
        $this->assertDatabaseHas('quiz_questions', [
            'module_id' => $duplicated->id,
            'question_text' => 'Sample question 2?',
            'correct_option' => 'C',
            'points' => 2,
        ]);

        // Verify clean data (no attempts or progress on duplicated module)
        $this->assertSame(0, QuizAttempt::query()->where('module_id', $duplicated->id)->count());
        $this->assertSame(0, ModuleProgress::query()->where('module_id', $duplicated->id)->count());
    }

    public function test_teacher_can_duplicate_pre_test_or_post_test(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);

        $class = ClassModel::query()->create([
            'name' => 'Pre-Test Class',
            'code' => 'PRE101',
            'school_year' => now()->year,
            'created_by' => $teacher->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Diagnostic Assessment',
            'is_quiz' => true,
            'is_formal_assessment' => false,
            'quiz_stage' => 'pre_test',
            'created_by' => $teacher->id,
        ]);

        QuizQuestion::query()->create([
            'module_id' => $module->id,
            'quiz_stage' => 'pre_test',
            'question_text' => 'Pre-test Q1',
            'options' => ['A' => '1', 'B' => '2'],
            'correct_option' => 'B',
            'points' => 1,
            'order' => 1,
        ]);

        $response = $this->actingAs($teacher)
            ->postJson(route('modules.duplicate', $module));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $duplicated = Module::query()->where('id', '!=', $module->id)->first();
        $this->assertNotNull($duplicated);
        $this->assertSame('pre_test', $duplicated->quiz_stage);
        $this->assertFalse($duplicated->is_formal_assessment);
        $this->assertSame(1, QuizQuestion::query()->where('module_id', $duplicated->id)->count());
    }

    public function test_non_owner_teacher_cannot_duplicate_module(): void
    {
        $owner = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $otherTeacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);

        $class = ClassModel::query()->create([
            'name' => 'Owner Class',
            'code' => 'OWN101',
            'school_year' => now()->year,
            'created_by' => $owner->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Protected Module',
            'is_quiz' => true,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($otherTeacher)
            ->post(route('modules.duplicate', $module));

        $response->assertForbidden();
    }

    public function test_admin_can_duplicate_any_module(): void
    {
        $owner = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $admin = $this->createUser(['role' => 'admin', 'program' => 'admin']);

        $class = ClassModel::query()->create([
            'name' => 'Admin Test Class',
            'code' => 'ADM101',
            'school_year' => now()->year,
            'created_by' => $owner->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Admin Module',
            'is_quiz' => true,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('modules.duplicate', $module));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    private function createUser(array $overrides = []): User
    {
        static $counter = 5000;
        $counter++;

        return User::query()->create(array_merge([
            'idnumber' => 'DUP'.$counter,
            'name' => 'Dup User '.$counter,
            'email' => 'dupuser'.$counter.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'program' => 'educ',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }
}
