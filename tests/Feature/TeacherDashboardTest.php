<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 2000;

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

    public function test_teacher_dashboard_requires_authentication(): void
    {
        $this->get(route('teacherDashboard'))->assertRedirect(route('login'));
    }

    public function test_teacher_dashboard_renders_with_expected_view_data(): void
    {
        $teacher = $this->createUser(['role' => 'teacher']);

        $response = $this->actingAs($teacher)
            ->get(route('teacherDashboard'))
            ->assertOk()
            ->assertViewIs('pages.teacher.teacher');

        $response->assertViewHasAll([
            'totalStudents',
            'quizzesPending',
            'avgClassScore',
            'classes',
            'recentActivity',
            'messages',
            'announcements',
        ]);
    }

    public function test_non_teacher_cannot_access_teacher_dashboard(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'educ',
        ]);

        $this->actingAs($student)
            ->get(route('teacherDashboard'))
            ->assertForbidden();
    }
}
