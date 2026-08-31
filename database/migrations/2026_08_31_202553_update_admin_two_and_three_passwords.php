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
        DB::table('users')
            ->whereIn('idnumber', ['ADMIN-002', 'ADMIN-003'])
            ->orWhereIn('email', ['admin2@reviso.edu', 'admin3@reviso.edu'])
            ->update([
                'password' => Hash::make('admin123'),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
