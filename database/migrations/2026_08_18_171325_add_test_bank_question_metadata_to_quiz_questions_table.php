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
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->foreignId('test_bank_question_id')
                ->nullable()
                ->after('module_id')
                ->constrained('test_bank_questions')
                ->nullOnDelete();
            $table->string('subject')->nullable()->after('difficulty');
            $table->string('chapter')->nullable()->after('subject');
            $table->string('topic')->nullable()->after('chapter');
            $table->string('subtopic')->nullable()->after('topic');
            $table->string('learning_competency')->nullable()->after('subtopic');
            $table->string('cognitive_level')->nullable()->after('learning_competency');
            $table->string('question_type')->nullable()->after('cognitive_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('test_bank_question_id');
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
};
