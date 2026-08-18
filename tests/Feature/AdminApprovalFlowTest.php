<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_assign_admin_role(): void
    {
        $actor = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $pendingUser = $this->createUser([
            'role' => 'student',
            'program' => 'educ',
            'status' => 'pending',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.approvals.approve', $pendingUser), [
                'role' => 'admin',
                'program' => null,
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'pending',
            'role' => 'student',
        ]);
    }

    public function test_superadmin_can_assign_admin_role(): void
    {
        $actor = $this->createUser([
            'role' => 'superadmin',
            'program' => null,
            'status' => 'active',
        ]);

        $pendingUser = $this->createUser([
            'role' => 'student',
            'program' => 'psych',
            'status' => 'pending',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.approvals.approve', $pendingUser), [
                'role' => 'admin',
                'program' => null,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'active',
            'role' => 'admin',
            'program' => null,
            'program_locked' => false,
        ]);
    }

    public function test_student_role_rejects_non_student_programs(): void
    {
        $actor = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $pendingUser = $this->createUser([
            'role' => 'student',
            'program' => null,
            'status' => 'pending',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.approvals.approve', $pendingUser), [
                'role' => 'student',
                'program' => 'teacher',
            ])
            ->assertSessionHasErrors('program');

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_teacher_role_requires_one_of_the_course_programs(): void
    {
        $actor = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $pendingUser = $this->createUser([
            'role' => 'student',
            'program' => null,
            'status' => 'pending',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.approvals.approve', $pendingUser), [
                'role' => 'teacher',
                'program' => null,
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_teacher_role_can_be_assigned_a_course_program(): void
    {
        $actor = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $pendingUser = $this->createUser([
            'role' => 'student',
            'program' => null,
            'status' => 'pending',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.approvals.approve', $pendingUser), [
                'role' => 'teacher',
                'program' => 'educ',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'active',
            'role' => 'teacher',
            'program' => 'educ',
            'program_locked' => true,
        ]);
    }

    private function createUser(array $overrides = []): User
    {
        static $counter = 1000;
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
