<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('2004-02-07');

        // Create 3 teachers
        $teachers = [
            ['idnumber' => 'TCH001', 'name' => 'Prof. Maria Smith', 'email' => 'teacher1@school.edu'],
            ['idnumber' => 'TCH002', 'name' => 'Prof. John Johnson', 'email' => 'teacher2@school.edu'],
            ['idnumber' => 'TCH003', 'name' => 'Prof. Sarah Williams', 'email' => 'teacher3@school.edu'],
        ];

        foreach ($teachers as $teacher) {
            User::create([
                'idnumber' => $teacher['idnumber'],
                'name' => $teacher['name'],
                'email' => $teacher['email'],
                'password' => $password,
                'role' => 'teacher',
                'program' => null,
                'email_verified_at' => now(),
            ]);
        }

        // Create 7 students with random course assignments
        $studentNames = [
            'Alice Anderson',
            'Bob Brown',
            'Charlie Chen',
            'Diana Davis',
            'Eve Edwards',
            'Frank Foster',
            'Grace Garcia',
        ];

        $courses = ['psychology', 'education', 'accountancy'];
        $courseRoles = ['psych', 'educ', 'accountancy'];

        foreach ($studentNames as $i => $name) {
            // Randomly select a course
            $courseIndex = array_rand($courses);
            $course = $courses[$courseIndex];
            $role = $courseRoles[$courseIndex];

            User::create([
                'idnumber' => 'STU'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'email' => 'student'.($i + 1).'@school.edu',
                'password' => $password,
                'role' => $role,
                'program' => $course,
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('Created 3 teachers and 7 students successfully!');
        $this->command->info('Password for all users: 2004-02-07');
    }
}
