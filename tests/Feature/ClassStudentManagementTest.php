<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClassStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 8000;

    private function createUser(array $overrides = []): User
    {
        $number = ++self::$counter;

        return User::query()->create(array_merge([
            'idnumber' => 'ID'.$number,
            'name' => 'User '.$number,
            'email' => 'user'.$number.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'program' => 'educ',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function createClass(User $teacher): ClassModel
    {
        static $classCounter = 200;
        $classCounter++;

        return ClassModel::query()->create([
            'name' => 'Class '.$classCounter,
            'code' => 'CLS'.$classCounter,
            'created_by' => $teacher->id,
        ]);
    }

    public function test_teacher_cannot_add_themselves_or_another_teacher_to_a_class(): void
    {
        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $otherTeacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'accountancy',
        ]);
        $class = $this->createClass($teacher);

        $this->actingAs($teacher)
            ->from('/manageclass')
            ->post(route('classes.students.add', $class), [
                'student_ids' => [$teacher->id, $otherTeacher->id],
            ])
            ->assertRedirect('/manageclass')
            ->assertSessionHasErrors(['student_ids.0', 'student_ids.1']);

        $this->assertDatabaseMissing('class_user', [
            'class_id' => $class->id,
            'user_id' => $teacher->id,
        ]);

        $this->assertDatabaseMissing('class_user', [
            'class_id' => $class->id,
            'user_id' => $otherTeacher->id,
        ]);

        $this->actingAs($teacher)
            ->post(route('classes.students.add', $class), [
                'student_ids' => [$student->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_teacher_cannot_add_students_to_another_teachers_class(): void
    {
        $owner = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $intruder = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'psych',
        ]);
        $class = $this->createClass($owner);

        $this->actingAs($intruder)
            ->post(route('classes.students.add', $class), [
                'student_ids' => [$student->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_search_students_only_returns_student_accounts(): void
    {
        $teacher = $this->createUser([
            'role' => 'teacher',
            'name' => 'Teach Example',
            'program' => 'teacher',
        ]);
        $student = $this->createUser([
            'role' => 'student',
            'name' => 'Student Example',
            'program' => 'educ',
        ]);

        $response = $this->actingAs($teacher)
            ->getJson(route('students.search', ['q' => 'Example']));

        $response->assertOk();
        $results = $response->json('results');

        $this->assertCount(1, $results);
        $this->assertSame($student->id, $results[0]['id']);
    }

    public function test_teacher_can_remove_student_from_owned_class(): void
    {
        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $student = $this->createUser();
        $class = $this->createClass($teacher);
        $class->users()->attach($student->id);

        $this->actingAs($teacher)
            ->deleteJson(route('classes.students.remove', [$class, $student]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_teacher_cannot_remove_student_from_another_teachers_class(): void
    {
        $owner = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $intruder = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $student = $this->createUser();
        $class = $this->createClass($owner);
        $class->users()->attach($student->id);

        $this->actingAs($intruder)
            ->deleteJson(route('classes.students.remove', [$class, $student]))
            ->assertForbidden();

        $this->assertDatabaseHas('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_class_students_endpoint_excludes_non_student_accounts(): void
    {
        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);
        $student = $this->createUser([
            'name' => 'Student Visible',
        ]);
        $teacherAssistant = $this->createUser([
            'role' => 'teacher',
            'name' => 'Teacher Hidden',
            'program' => 'teacher',
        ]);
        $class = $this->createClass($teacher);
        $class->users()->attach([$student->id, $teacherAssistant->id]);

        $response = $this->actingAs($teacher)
            ->getJson(route('classes.students.get', $class));

        $response->assertOk();
        $response->assertJsonCount(1, 'students');
        $response->assertJsonPath('students.0.id', $student->id);
    }
}
