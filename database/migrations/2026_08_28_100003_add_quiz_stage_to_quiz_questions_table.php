<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            // NULL = ordinary standalone quiz / mock-board-phase question (unchanged
            // behavior). Set only for questions that belong to a lecture module's
            // pre-test or post-test.
            $table->enum('quiz_stage', ['pre_test', 'post_test'])
                ->nullable()
                ->after('module_id');

            $table->index(['module_id', 'quiz_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropIndex(['module_id', 'quiz_stage']);
            $table->dropColumn('quiz_stage');
        });
    }
};
