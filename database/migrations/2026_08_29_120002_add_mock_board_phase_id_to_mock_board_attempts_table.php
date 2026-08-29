<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds mock_board_phase_id to mock_board_attempts so a student's cached
     * result is keyed to a SPECIFIC phase, not just (mock_board_id,
     * phase_type). Without this, two post-test phases on the same board
     * would collide onto a single MockBoardAttempt row and silently
     * overwrite each other's score. phase_type is kept for backward-
     * compatible display/grouping, but stops being part of the identity key
     * once this ships.
     */
    public function up(): void
    {
        Schema::table('mock_board_attempts', function (Blueprint $table) {
            $table->foreignId('mock_board_phase_id')
                ->nullable()
                ->after('mock_board_id')
                ->constrained('mock_board_phases')
                ->nullOnDelete();

            $table->index(['user_id', 'mock_board_phase_id'], 'mock_board_attempts_user_phase_idx');
        });

        // Backfill: at migration time there is still at most one phase per
        // (mock_board_id, phase_type), so this match is unambiguous.
        $phases = DB::table('mock_board_phases')->select('id', 'mock_board_id', 'phase_type')->get();

        foreach ($phases as $phase) {
            DB::table('mock_board_attempts')
                ->where('mock_board_id', $phase->mock_board_id)
                ->where('phase_type', $phase->phase_type)
                ->whereNull('mock_board_phase_id')
                ->update(['mock_board_phase_id' => $phase->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mock_board_attempts', function (Blueprint $table) {
            $table->dropIndex('mock_board_attempts_user_phase_idx');
            $table->dropConstrainedForeignId('mock_board_phase_id');
        });
    }
};
