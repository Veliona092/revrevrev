<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('teacher123');

        $teachers = [
            [
                'idnumber' => '23-0801',
                'name' => 'Prof. Elena Ramos (Psychology)',
                'email' => 'teacher.psych@reviso.com',
                'program' => 'psych',
                'role' => 'teacher',
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
            [
                'idnumber' => '23-0802',
                'name' => 'Prof. Carlos Santos (Education)',
                'email' => 'teacher.educ@reviso.com',
                'program' => 'educ',
                'role' => 'teacher',
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
            [
                'idnumber' => '23-0803',
                'name' => 'Prof. Teresa Diaz (Accountancy)',
                'email' => 'teacher.acc@reviso.com',
                'program' => 'accountancy',
                'role' => 'teacher',
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($teachers as $teacher) {
            User::updateOrCreate(
                ['idnumber' => $teacher['idnumber']],
                [
                    'name' => $teacher['name'],
                    'email' => $teacher['email'],
                    'password' => $password,
                    'role' => $teacher['role'],
                    'program' => $teacher['program'],
                    'status' => $teacher['status'],
                    'email_verified_at' => $teacher['email_verified_at'],
                ]
            );
        }
    }
}