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
        Schema::create('test_bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('program')->nullable()->index();
            $table->string('subject')->nullable()->index();
            $table->string('chapter')->nullable();
            $table->string('topic')->nullable()->index();
            $table->string('subtopic')->nullable();
            $table->string('learning_competency')->nullable()->index();
            $table->string('cognitive_level')->nullable();
            $table->string('question_type')->nullable();
            $table->text('question_text');
            $table->json('options');
            $table->string('correct_option');
            $table->unsignedTinyInteger('points')->default(1);
            $table->string('difficulty')->default('Average')->index();
            $table->text('explanation')->nullable();
            $table->string('status')->default('approved')->index();
            $table->boolean('is_archived')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_bank_questions');
    }
};
