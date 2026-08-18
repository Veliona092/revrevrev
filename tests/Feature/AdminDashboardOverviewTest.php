<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 2000;

    private function createUser(string $role, string $status = 'active'): User
    {
        $n = ++self::$seq;

        return User::query()->create([
            'idnumber' => strtoupper($role[0]).$n,
            'name' => ucfirst($role).' '.$n,
            'email' => $role.$n.'@example.test',
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => $status,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_access_dashboard_and_sees_required_data_keys(): void
    {
        $admin = $this->createUser('admin');
        $this->createUser('student', 'pending');

        $response = $this->actingAs($admin)->get(route('adminDashboard'));

        $response->assertOk();
        $response->assertViewHasAll([
            'totalUsers',
            'pendingApprovals',
            'totalActiveClasses',
            'totalQuizAttempts',
            'pendingUsers',
            'roleDistribution',
            'roleBreakdown',
            'platformActivity',
            'recentClasses',
        ]);
    }

    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        $teacher = $this->createUser('teacher');

        $response = $this->actingAs($teacher)->get(route('adminDashboard'));

        $response->assertForbidden();
    }
}
