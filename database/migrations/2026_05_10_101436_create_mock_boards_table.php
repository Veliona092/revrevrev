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
        Schema::create('mock_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->integer('passing_percentage')->default(75);
            // CRITICAL: Visibility columns per corrections-v2.md
            $table->enum('visibility', ['all', 'selected', 'except'])->default('all');
            $table->json('visible_to')->nullable();
            $table->timestamps();

            $table->index(['class_id', 'visibility']);
            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_boards');
    }
};
