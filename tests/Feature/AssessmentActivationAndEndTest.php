<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Module;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssessmentActivationAndEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_take_upcoming_formal_assessment_before_activation_date(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser(['role' => 'student', 'program' => 'educ']);

        $class = ClassModel::query()->create([
            'name' => 'Active Test Class',
            'code' => 'ACT101',
            'school_year' => now()->year,
            'created_by' => $teacher->id,
        ]);
        $class->users()->attach($student->id);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Scheduled Midterm',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'available_at' => now()->addDays(2), // Future date
            'due_date' => now()->addDays(5),
            'is_active' => true,
            'created_by' => $teacher->id,
        ]);

        QuizQuestion::query()->create([
            'module_id' => $module->id,
            'question_text' => 'Sample Q1?',
            'options' => ['A' => '1', 'B' => '2'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
        ]);

        $this->assertTrue($module->isUpcoming());
        $this->assertFalse($module->isOpen());
        $this->assertSame('Upcoming', $module->statusLabel());

        // Attempting to visit take page should return 403
        $response = $this->actingAs($student)->get(route('assessment.take', $module));
        $response->assertForbidden();

        // Attempting to start attempt via API should return 403 JSON
        $apiResponse = $this->actingAs($student)->postJson(route('quiz.start', $module));
        $apiResponse->assertForbidden();
        $apiResponse->assertJson(['success' => false]);
    }

    public function test_student_can_take_active_assessment_when_opened(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser(['role' => 'student', 'program' => 'educ']);

        $class = ClassModel::query()->create([
            'name' => 'Active Test Class',
            'code' => 'ACT102',
            'school_year' => now()->year,
            'created_by' => $teacher->id,
        ]);
        $class->users()->attach($student->id);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Opened Midterm',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'available_at' => now()->subHour(), // In the past -> active now
            'due_date' => now()->addDays(3),
            'is_active' => true,
            'created_by' => $teacher->id,
        ]);

        QuizQuestion::query()->create([
            'module_id' => $module->id,
            'question_text' => 'Sample Q1?',
            'options' => ['A' => '1', 'B' => '2'],
            'correct_option' => 'A',
            'points' => 1,
            'order' => 1,
        ]);

        $this->assertFalse($module->isUpcoming());
        $this->assertTrue($module->isOpen());
        $this->assertSame('Active', $module->statusLabel());

        $response = $this->actingAs($student)->get(route('assessment.take', $module));
        $response->assertOk();

        $apiResponse = $this->actingAs($student)->postJson(route('quiz.start', $module));
        $apiResponse->assertOk();
        $apiResponse->assertJson(['success' => true]);
    }

    public function test_student_cannot_take_assessment_past_due_date(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser(['role' => 'student', 'program' => 'educ']);

        $class = ClassModel::query()->create([
            'name' => 'Active Test Class',
            'code' => 'ACT103',
            'school_year' => now()->year,
            'created_by' => $teacher->id,
        ]);
        $class->users()->attach($student->id);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Expired Midterm',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'available_at' => now()->subDays(5),
            'due_date' => now()->subHour(), // Overdue
            'is_active' => true,
            'created_by' => $teacher->id,
        ]);

        $this->assertTrue($module->isOverdue());
        $this->assertTrue($module->isClosed());
        $this->assertFalse($module->isOpen());
        $this->assertSame('Closed', $module->statusLabel());

        $response = $this->actingAs($student)->get(route('assessment.take', $module));
        $response->assertForbidden();

        $apiResponse = $this->actingAs($student)->postJson(route('quiz.start', $module));
        $apiResponse->assertForbidden();
    }

    public function test_student_cannot_take_inactive_assessment(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser(['role' => 'student', 'program' => 'educ']);

        $class = ClassModel::query()->create([
            'name' => 'Active Test Class',
            'code' => 'ACT104',
            'school_year' => now()->year,
            'created_by' => $teacher->id,
        ]);
        $class->users()->attach($student->id);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Draft Inactive Midterm',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'is_active' => false,
            'created_by' => $teacher->id,
        ]);

        $this->assertTrue($module->isClosed());
        $this->assertSame('Inactive', $module->statusLabel());

        $response = $this->actingAs($student)->get(route('assessment.take', $module));
        $response->assertForbidden();

        $apiResponse = $this->actingAs($student)->postJson(route('quiz.start', $module));
        $apiResponse->assertForbidden();
    }

    public function test_teacher_can_update_assessment_settings_via_api(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);

        $class = ClassModel::query()->create([
            'name' => 'Active Test Class',
            'code' => 'ACT105',
            'school_year' => now()->year,
            'created_by' => $teacher->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Configurable Exam',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'time_limit' => 30,
            'passing_grade' => 50,
            'max_attempts' => 1,
            'created_by' => $teacher->id,
        ]);

        $openTime = now()->addDay()->format('Y-m-d H:i:s');
        $endTime = now()->addDays(4)->format('Y-m-d H:i:s');

        $response = $this->actingAs($teacher)->putJson(route('modules.settings.update', $module), [
            'available_at' => $openTime,
            'due_date' => $endTime,
            'time_limit' => 45,
            'passing_grade' => 75,
            'max_attempts' => 3,
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $module->refresh();
        $this->assertSame(45, $module->time_limit);
        $this->assertSame(75, $module->passing_grade);
        $this->assertSame(3, $module->max_attempts);
        $this->assertNotNull($module->available_at);
        $this->assertNotNull($module->due_date);
        $this->assertTrue($module->is_active);
    }

    public function test_teacher_can_manually_end_and_reopen_exam(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);

        $class = ClassModel::query()->create([
            'name' => 'Active Test Class',
            'code' => 'ACT106',
            'school_year' => now()->year,
            'created_by' => $teacher->id,
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Live Exam',
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'is_active' => true,
            'created_by' => $teacher->id,
        ]);

        $this->assertTrue($module->isOpen());

        // Teacher ends exam
        $endResponse = $this->actingAs($teacher)->postJson(route('modules.toggle-status', $module), [
            'action' => 'end',
        ]);
        $endResponse->assertOk();
        $endResponse->assertJson(['success' => true]);

        $module->refresh();
        $this->assertTrue($module->isClosed());
        $this->assertFalse($module->isOpen());

        // Teacher reopens exam
        $reopenResponse = $this->actingAs($teacher)->postJson(route('modules.toggle-status', $module), [
            'action' => 'reopen',
        ]);
        $reopenResponse->assertOk();
        $reopenResponse->assertJson(['success' => true]);

        $module->refresh();
        $this->assertTrue($module->isOpen());
    }

    private function createUser(array $overrides = []): User
    {
        static $counter = 6000;
        $counter++;

        return User::query()->create(array_merge([
            'idnumber' => 'ACT'.$counter,
            'name' => 'Act User '.$counter,
            'email' => 'actuser'.$counter.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'program' => 'educ',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }
}
