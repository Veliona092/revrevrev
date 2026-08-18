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
        Schema::create('mock_board_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mock_board_id')->constrained()->onDelete('cascade');
            $table->enum('phase_type', ['pre_test', 'pre_boards']);
            $table->foreignId('quiz_attempt_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('score')->nullable();
            $table->integer('total')->nullable();
            $table->integer('percentage')->nullable();
            $table->boolean('passed')->default(false);
            $table->integer('attempt_count')->default(1);
            $table->text('ai_strong')->nullable();
            $table->text('ai_weak')->nullable();
            $table->text('ai_recommendation')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'mock_board_id', 'phase_type']);
            $table->index(['mock_board_id', 'phase_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_board_attempts');
    }
};
