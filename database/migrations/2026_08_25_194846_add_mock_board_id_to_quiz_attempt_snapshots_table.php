<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempt_snapshots', function (Blueprint $table) {
            $table->foreignId('mock_board_id')->nullable()->after('module_id')
                ->constrained('mock_boards')->nullOnDelete();
            $table->string('phase_type')->nullable()->after('mock_board_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempt_snapshots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mock_board_id');
            $table->dropColumn('phase_type');
        });
    }
};