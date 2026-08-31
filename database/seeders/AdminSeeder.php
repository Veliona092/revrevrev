<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // 1 Superadmin
            [
                'idnumber' => 'SUPERADMIN-001',
                'name' => 'Super Administrator',
                'email' => 'superadmin@reviso.edu',
                'password' => Hash::make('SuperAdmin@2025!'),
                'role' => 'superadmin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            // 3 Predetermined Admins
            [
                'idnumber' => 'ADMIN-001',
                'name' => 'Admin One',
                'email' => 'admin1@reviso.edu',
                'password' => Hash::make('Admin1@2025!'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'idnumber' => 'ADMIN-002',
                'name' => 'Admin Two',
                'email' => 'admin2@reviso.edu',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'idnumber' => 'ADMIN-003',
                'name' => 'Admin Three',
                'email' => 'admin3@reviso.edu',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($accounts as $data) {
            User::updateOrCreate(
                ['idnumber' => $data['idnumber']],
                $data
            );
        }
    }
}
