<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SignupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'id_number' => '23-0811',
                'name' => 'KYLE',
                'email' => 'coolpineda@gmail.com',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0801',
                'name' => 'Prof. Maria Smith',
                'email' => 'teacher1@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'teacher',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0802',
                'name' => 'Prof. John Johnson',
                'email' => 'teacher2@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0803',
                'name' => 'Prof. Sarah Williams',
                'email' => 'teacher3@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'teacher',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0804',
                'name' => 'Alice Anderson',
                'email' => 'student1@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0805',
                'name' => 'Bob Brown',
                'email' => 'student2@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0806',
                'name' => 'Charlie Chen',
                'email' => 'student3@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0807',
                'name' => 'Diana Davis',
                'email' => 'student4@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0808',
                'name' => 'Eve Edwards',
                'email' => 'student5@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_number' => '23-0809',
                'name' => 'Frank Foster',
                'email' => 'student6@school.edu',
                'password' => bcrypt('2004-02-07'),
                'role' => 'student',
                'verification_token' => Str::random(60),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('signups')->insert($users);
    }
}
