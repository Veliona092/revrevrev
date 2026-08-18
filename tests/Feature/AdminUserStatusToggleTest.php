<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminUserStatusToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deactivate_teacher_with_reason(): void
    {
        $this->mockMailer();

        $admin = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-status', $teacher->id), [
                'reason' => 'Policy violation',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'status' => 'rejected',
            'rejection_reason' => 'Policy violation',
        ]);
    }

    public function test_admin_can_activate_teacher_and_clear_reason(): void
    {
        $this->mockMailer();

        $admin = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
            'status' => 'rejected',
            'rejection_reason' => 'Prior issue',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-status', $teacher->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'status' => 'active',
            'rejection_reason' => null,
        ]);
    }

    public function test_admin_cannot_deactivate_admin_account(): void
    {
        $this->mockMailer();

        $admin = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $targetAdmin = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-status', $targetAdmin->id), [
                'reason' => 'Not allowed',
            ])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', [
            'id' => $targetAdmin->id,
            'status' => 'active',
        ]);
    }

    public function test_superadmin_can_deactivate_admin_with_optional_reason(): void
    {
        $this->mockMailer();

        $superadmin = $this->createUser([
            'role' => 'superadmin',
            'program' => null,
            'status' => 'active',
        ]);

        $targetAdmin = $this->createUser([
            'role' => 'admin',
            'program' => null,
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.users.toggle-status', $targetAdmin->id), [
                'reason' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $targetAdmin->id,
            'status' => 'rejected',
            'rejection_reason' => null,
        ]);
    }

    private function mockMailer(): void
    {
        $this->mock(GmailService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->andReturn(true);
        });
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
