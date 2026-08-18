<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MyClassesViewResolutionTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 3000;

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

    public function test_psych_program_student_uses_psych_my_classes_view(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'psych',
        ]);

        $this->actingAs($student)
            ->get(route('student.classes'))
            ->assertOk()
            ->assertViewIs('pages.psych.my-classes');
    }

    public function test_educ_program_student_uses_educ_my_classes_view(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'educ',
        ]);

        $this->actingAs($student)
            ->get(route('student.classes'))
            ->assertOk()
            ->assertViewIs('pages.educ.my-classes');
    }

    public function test_accountancy_program_student_uses_accountancy_my_classes_view(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'program' => 'accountancy',
        ]);

        $this->actingAs($student)
            ->get(route('student.classes'))
            ->assertOk()
            ->assertViewIs('pages.accountancy.my-classes');
    }
}
