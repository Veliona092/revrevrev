<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersManagementTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 3000;

    private function createUser(string $role, string $status = 'active', array $overrides = []): User
    {
        $n = ++self::$seq;

        return User::query()->create(array_merge([
            'idnumber' => strtoupper($role[0]).$n,
            'name' => ucfirst($role).' '.$n,
            'email' => $role.$n.'@example.test',
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => $status,
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_admin_can_filter_users_by_role_and_status(): void
    {
        $admin = $this->createUser('admin');
        $this->createUser('student', 'active');
        $this->createUser('student', 'pending');
        $this->createUser('teacher', 'active');

        $response = $this->actingAs($admin)
            ->get(route('admin.users', ['role' => 'student', 'status' => 'pending']));

        $response->assertOk();
        $response->assertViewHas('users');

        $users = $response->viewData('users');
        foreach ($users as $user) {
            $this->assertEquals('student', $user->role);
            $this->assertEquals('pending', $user->status);
        }
    }

    public function test_admin_can_export_users_as_csv(): void
    {
        $admin = $this->createUser('admin');
        $student = $this->createUser('student', 'active', ['name' => 'Export TestStudent']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type', ''));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Export TestStudent', $content);
        $this->assertStringContainsString('ID Number', $content);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.users'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_admin_users(): void
    {
        $student = $this->createUser('student');

        $response = $this->actingAs($student)
            ->get(route('admin.users'));

        $response->assertForbidden();
    }
}
