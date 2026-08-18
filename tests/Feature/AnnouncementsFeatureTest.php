<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnnouncementsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 5000;

    private function createUser(array $overrides = []): User
    {
        $n = ++self::$counter;

        return User::query()->create(array_merge([
            'idnumber' => 'ID'.$n,
            'name' => 'User '.$n,
            'email' => 'user'.$n.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'status' => 'active',
            'program' => null,
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function createClass(User $teacher): ClassModel
    {
        static $classCounter = 100;
        $classCounter++;

        return ClassModel::query()->create([
            'name' => 'Class '.$classCounter,
            'code' => 'C'.$classCounter,
            'created_by' => $teacher->id,
        ]);
    }

    public function test_teacher_can_post_and_auto_unpin_previous_announcement(): void
    {
        $teacher = $this->createUser(['role' => 'teacher']);
        $class = $this->createClass($teacher);

        $this->actingAs($teacher)
            ->post(route('announcements.store', $class), [
                'message' => 'First important note',
                'is_pinned' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->post(route('announcements.store', $class), [
                'message' => 'Second important note',
                'is_pinned' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(
            1,
            Announcement::query()->where('class_id', $class->id)->where('is_pinned', true)->count()
        );

        $this->assertDatabaseHas('announcements', [
            'class_id' => $class->id,
            'message' => 'Second important note',
            'is_pinned' => true,
        ]);
    }

    public function test_student_cannot_post_announcement(): void
    {
        $teacher = $this->createUser(['role' => 'teacher']);
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'educ',
        ]);
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        $this->actingAs($student)
            ->post(route('announcements.store', $class), [
                'message' => 'I should not be able to post this',
            ])
            ->assertForbidden();
    }

    public function test_opening_announcements_marks_visible_items_as_read(): void
    {
        $teacher = $this->createUser(['role' => 'teacher']);
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'psych',
        ]);
        $class = $this->createClass($teacher);

        $student->classes()->attach($class->id);

        $announcement = Announcement::query()->create([
            'class_id' => $class->id,
            'user_id' => $teacher->id,
            'message' => 'Please read this announcement',
            'is_pinned' => false,
        ]);

        $this->actingAs($student)
            ->get(route('announcements.index', ['class_id' => $class->id]))
            ->assertOk();

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $student->id,
        ]);
    }
}
