<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_exam_subtopic_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_exam_subtopic_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type', 20)->default('pdf');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['board_exam_subtopic_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_exam_subtopic_files');
    }
};