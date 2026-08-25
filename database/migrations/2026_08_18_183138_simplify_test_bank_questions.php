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
        Schema::table('test_bank_questions', function (Blueprint $table) {
            $table->dropIndex(['subject']);
            $table->dropIndex(['topic']);
            $table->dropIndex(['learning_competency']);
            $table->dropColumn([
                'subject',
                'chapter',
                'topic',
                'subtopic',
                'learning_competency',
                'cognitive_level',
                'question_type',
                'explanation',
            ]);
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn([
                'subject',
                'chapter',
                'topic',
                'subtopic',
                'learning_competency',
                'cognitive_level',
                'question_type',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_bank_questions', function (Blueprint $table) {
            $table->string('subject')->nullable();
            $table->string('chapter')->nullable();
            $table->string('topic')->nullable();
            $table->string('subtopic')->nullable();
            $table->string('learning_competency')->nullable();
            $table->string('cognitive_level')->nullable();
            $table->string('question_type')->nullable();
            $table->text('explanation')->nullable();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('subject')->nullable();
            $table->string('chapter')->nullable();
            $table->string('topic')->nullable();
            $table->string('subtopic')->nullable();
            $table->string('learning_competency')->nullable();
            $table->string('cognitive_level')->nullable();
            $table->string('question_type')->nullable();
        });
    }
};
