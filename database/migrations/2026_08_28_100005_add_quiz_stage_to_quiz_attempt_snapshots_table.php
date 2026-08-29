<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempt_snapshots', function (Blueprint $table) {
            // Kept separate from the existing `phase_type` column (mock board
            // pre_test/pre_boards) on purpose — a given module is either a mock
            // board phase OR a lecture with pre/post test stages, never both, but
            // both columns stay so history stays queryable without conditional
            // logic on which "kind" of stage a row represents.
            $table->enum('quiz_stage', ['pre_test', 'post_test'])
                ->nullable()
                ->after('module_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempt_snapshots', function (Blueprint $table) {
            $table->dropColumn('quiz_stage');
        });
    }
};
