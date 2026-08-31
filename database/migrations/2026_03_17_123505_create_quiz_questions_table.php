<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->text('question_text');                // TinyMCE content
            $table->json('options');                      // ["A": "Paris", "B": "Florida", ...]
            $table->string('correct_option');             // "A", "B", "C", or "D"
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            $table->string('difficulty')->default('Normal'); // Easy/Normal/Hard (for reference)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
