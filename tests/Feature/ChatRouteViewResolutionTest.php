<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatRouteViewResolutionTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 4000;

    private function createUser(array $overrides = []): User
    {
        $n = ++self::$counter;

        return User::query()->create(array_merge([
            'idnumber' => 'ID'.$n,
            'name' => 'User '.$n,
            'email' => 'user'.$n.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'program' => 'accountancy',
            'status' => 'active',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_psych_program_student_gets_psych_chat_view(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'psych',
        ]);

        $this->actingAs($student)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertViewIs('pages.chat.psych');
    }

    public function test_educ_program_student_gets_educ_chat_view(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'educ',
        ]);

        $this->actingAs($student)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertViewIs('pages.chat.educ');
    }

    public function test_accountancy_program_student_gets_accountancy_chat_view(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'accountancy',
        ]);

        $this->actingAs($student)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertViewIs('pages.chat.accountancy');
    }
}
