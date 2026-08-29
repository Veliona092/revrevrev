<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Mirrors quiz_questions.quiz_stage. Existing rows stay NULL — they were
            // created before this feature and represent standalone quizzes / mock
            // board phase quizzes, which still only ever produce one attempt per
            // (user_id, module_id, mock_board_id). New lecture pre-test/post-test
            // attempts must include this in every lookup key (see QuizController)
            // or a post-test attempt will overwrite the pre-test attempt row for
            // the same module.
            $table->enum('quiz_stage', ['pre_test', 'post_test'])
                ->nullable()
                ->after('module_id');

            $table->index(['user_id', 'module_id', 'quiz_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'module_id', 'quiz_stage']);
            $table->dropColumn('quiz_stage');
        });
    }
};
