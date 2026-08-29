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
     * Allows a mock board to have more than one phase of the same
     * phase_type (e.g. multiple post-tests). Adds sequence_number/label so
     * multiple phases of the same type can be ordered and displayed
     * distinctly, and drops the old unique(mock_board_id, phase_type)
     * constraint that limited a board to exactly one phase per type.
     */
    public function up(): void
    {
        Schema::table('mock_board_phases', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')->default(1)->after('phase_type');
            $table->string('label')->nullable()->after('sequence_number');
        });

        Schema::table('mock_board_phases', function (Blueprint $table) {
            $table->dropUnique(['mock_board_id', 'phase_type']);
            $table->index(['mock_board_id', 'phase_type'], 'mock_board_phases_board_id_phase_type_idx');
        });

        // Backfill: number existing phases of the same type in creation order
        // (pre_test always stays singular in practice today, but this is
        // written generically so it's correct even if that changes later).
        $boards = DB::table('mock_board_phases')
            ->select('id', 'mock_board_id', 'phase_type')
            ->orderBy('mock_board_id')
            ->orderBy('phase_type')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($row) => $row->mock_board_id.'|'.$row->phase_type);

        foreach ($boards as $group) {
            $sequence = 1;
            foreach ($group as $row) {
                DB::table('mock_board_phases')
                    ->where('id', $row->id)
                    ->update(['sequence_number' => $sequence]);
                $sequence++;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mock_board_phases', function (Blueprint $table) {
            $table->dropIndex('mock_board_phases_board_id_phase_type_idx');
            $table->unique(['mock_board_id', 'phase_type']);
        });

        Schema::table('mock_board_phases', function (Blueprint $table) {
            $table->dropColumn(['sequence_number', 'label']);
        });
    }
};
