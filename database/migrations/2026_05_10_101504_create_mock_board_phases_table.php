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
        Schema::create('mock_board_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_board_id')->constrained()->onDelete('cascade');
            $table->enum('phase_type', ['pre_test', 'pre_boards']);
            $table->string('title');
            $table->foreignId('module_id')->nullable()->constrained()->onDelete('set null');
            $table->json('question_ids')->nullable();
            $table->boolean('is_same_questions')->default(false);
            $table->timestamps();

            $table->unique(['mock_board_id', 'phase_type']); // Only one pre_test and one pre_boards per mock board
            $table->index('mock_board_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_board_phases');
    }
};
