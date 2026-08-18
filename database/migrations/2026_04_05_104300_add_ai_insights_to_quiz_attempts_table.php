<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->text('ai_strong')->nullable()->after('attempt_count');
            $table->text('ai_weak')->nullable()->after('ai_strong');
            $table->text('ai_recommendation')->nullable()->after('ai_weak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['ai_strong', 'ai_weak', 'ai_recommendation']);
        });
    }
};
