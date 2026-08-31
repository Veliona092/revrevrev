<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $accounts = [
            [
                'idnumber' => 'SUPERADMIN-001',
                'name' => 'Super Administrator',
                'email' => 'superadmin@reviso.edu',
                'password' => Hash::make('SuperAdmin@2025!'),
                'role' => 'superadmin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
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

        foreach ($accounts as $account) {
            $existing = DB::table('users')
                ->where('idnumber', $account['idnumber'])
                ->orWhere('email', $account['email'])
                ->first();

            if ($existing) {
                DB::table('users')
                    ->where('id', $existing->id)
                    ->update([
                        'idnumber' => $account['idnumber'],
                        'name' => $account['name'],
                        'email' => $account['email'],
                        'password' => $account['password'],
                        'role' => $account['role'],
                        'status' => 'active',
                        'email_verified_at' => $existing->email_verified_at ?? now(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('users')->insert(
                    $account + [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
