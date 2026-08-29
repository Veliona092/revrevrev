<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nullable, teacher-picked link from a mock board to the specific real
     * exam it should be compared against — manual linking rather than
     * automatic program+year matching, since the teacher knows which real
     * exam is the right comparison point.
     */
    public function up(): void
    {
        Schema::table('mock_boards', function (Blueprint $table) {
            $table->foreignId('historical_board_exam_result_id')
                ->nullable()
                ->after('passing_percentage')
                ->constrained('historical_board_exam_results')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mock_boards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('historical_board_exam_result_id');
        });
    }
};
