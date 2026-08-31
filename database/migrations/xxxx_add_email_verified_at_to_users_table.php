<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add email first (if it doesn't exist)
            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->unique()->nullable();
            }

            // Then add email_verified_at (if it doesn't exist)
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                // Place it after email ONLY if email now exists (safe fallback)
                if (Schema::hasColumn('users', 'email')) {
                    $table->timestamp('email_verified_at')->nullable()->after('email');
                } else {
                    $table->timestamp('email_verified_at')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'email_verified_at']);
        });
    }
};
