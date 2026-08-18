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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('mock_board_id')->nullable()->constrained()->onDelete('set null')->after('module_id');
            $table->enum('mock_board_phase_type', ['pre_test', 'pre_boards'])->nullable()->after('mock_board_id');
            $table->index(['mock_board_id', 'mock_board_phase_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['mock_board_id', 'mock_board_phase_type']);
            $table->dropColumn('mock_board_phase_type');
            $table->dropForeign(['mock_board_id']);
            $table->dropColumn('mock_board_id');
        });
    }
};
