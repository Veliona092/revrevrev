<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'psych')->update([
            'role' => 'student',
            'program' => 'psych',
            'program_locked' => true,
        ]);

        DB::table('users')->where('role', 'educ')->update([
            'role' => 'student',
            'program' => 'educ',
            'program_locked' => true,
        ]);

        DB::table('users')->where('role', 'accountancy')->update([
            'role' => 'student',
            'program' => 'accountancy',
            'program_locked' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', 'student')
            ->where('program', 'psych')
            ->update(['role' => 'psych']);

        DB::table('users')
            ->where('role', 'student')
            ->where('program', 'educ')
            ->update(['role' => 'educ']);

        DB::table('users')
            ->where('role', 'student')
            ->where('program', 'accountancy')
            ->update(['role' => 'accountancy']);
    }
};
