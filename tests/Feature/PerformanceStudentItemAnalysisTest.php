<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerformanceStudentItemAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_item_analysis_for_enrolled_student(): void
    {
        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);

        $student = $this->createUser([
            'role' => 'student',
            'program' => 'educ',
        ]);

        $class = ClassModel::query()->create([
            'name' => 'Sample Class',
            'code' => 'CLS100',
            'school_year' => now()->year,
            'description' => 'Class for analytics testing',
            'created_by' => $teacher->id,
        ]);

        $class->students()->attach($student->id);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Module 1',
            'description' => 'First module',
            'file_type' => 'pdf',
        ]);

        $questionId = DB::table('quiz_questions')->insertGetId([
            'module_id' => $module->id,
            'question_text' => 'What is 2 + 2?',
            'options' => json_encode([
                'A' => '4',
                'B' => '3',
                'C' => '5',
                'D' => '6',
            ], JSON_THROW_ON_ERROR),
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
            'difficulty' => 'Normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attempt = QuizAttempt::query()->create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'score' => 1,
            'total' => 1,
            'percentage' => 100,
            'passed' => true,
            'time_taken' => 1,
            'attempted_at' => now(),
        ]);

        DB::table('quiz_answers')->insert([
            'attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'selected_option' => 'A',
            'is_correct' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($teacher)
            ->getJson(route('student.performance.student-item-analysis', [
                'class' => $class->id,
                'student' => $student->id,
            ]));

        $response->assertOk();
        $response->assertJsonPath('student.id', $student->id);
        $response->assertJsonPath('attempt.id', $attempt->id);
        $response->assertJsonPath('answers.0.question_id', $questionId);
        $response->assertJsonPath('answers.0.selected_option', 'A');
    }

    public function test_teacher_cannot_view_item_analysis_for_other_teachers_class(): void
    {
        $ownerTeacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);

        $anotherTeacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);

        $student = $this->createUser([
            'role' => 'student',
            'program' => 'accountancy',
        ]);

        $class = ClassModel::query()->create([
            'name' => 'Restricted Class',
            'code' => 'CLS200',
            'school_year' => now()->year,
            'description' => 'Class access restricted to owner',
            'created_by' => $ownerTeacher->id,
        ]);

        $class->students()->attach($student->id);

        $this->actingAs($anotherTeacher)
            ->getJson(route('student.performance.student-item-analysis', [
                'class' => $class->id,
                'student' => $student->id,
            ]))
            ->assertForbidden();
    }

    private function createUser(array $overrides = []): User
    {
        static $counter = 2000;
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
