<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserResetPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_teacher_password(): void
    {
        $admin = $this->createUser([
            'role' => 'admin',
            'program' => null,
        ]);

        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);

        $oldHash = $teacher->password;

        $this->actingAs($admin)
            ->post(route('admin.users.reset', $teacher->id))
            ->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertNotSame($oldHash, $teacher->password);
    }

    public function test_admin_cannot_reset_admin_password(): void
    {
        $admin = $this->createUser([
            'role' => 'admin',
            'program' => null,
        ]);

        $targetAdmin = $this->createUser([
            'role' => 'admin',
            'program' => null,
        ]);

        $oldHash = $targetAdmin->password;

        $this->actingAs($admin)
            ->post(route('admin.users.reset', $targetAdmin->id))
            ->assertSessionHasErrors('error');

        $targetAdmin->refresh();
        $this->assertSame($oldHash, $targetAdmin->password);
    }

    public function test_superadmin_can_reset_admin_password(): void
    {
        $superadmin = $this->createUser([
            'role' => 'superadmin',
            'program' => null,
        ]);

        $targetAdmin = $this->createUser([
            'role' => 'admin',
            'program' => null,
        ]);

        $oldHash = $targetAdmin->password;

        $this->actingAs($superadmin)
            ->post(route('admin.users.reset', $targetAdmin->id))
            ->assertSessionHasNoErrors();

        $targetAdmin->refresh();
        $this->assertNotSame($oldHash, $targetAdmin->password);
    }

    private function createUser(array $overrides = []): User
    {
        static $counter = 3000;
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
