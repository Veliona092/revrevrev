<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_exam_subtopic_file_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('board_exam_subtopic_file_id')
                ->constrained('board_exam_subtopic_files', 'id', 'bem_subtopic_file_progress_file_fk')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'board_exam_subtopic_file_id'], 'user_file_progress_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_exam_subtopic_file_progress');
    }
};