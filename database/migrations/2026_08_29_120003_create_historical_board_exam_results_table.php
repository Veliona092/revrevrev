<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Holds manually-entered real-world board/licensure exam results (e.g.
     * "October 2024 CPA Licensure Exam") so a mock board's own performance
     * can be compared against an actual historical exam. This data is typed
     * in by a teacher/admin from a physical results bulletin — it never
     * comes from a student taking a quiz inside Reviso.
     */
    public function up(): void
    {
        Schema::create('historical_board_exam_results', function (Blueprint $table) {
            $table->id();
            $table->enum('program', ['psychology', 'education', 'accountancy']);
            $table->string('exam_label');
            $table->string('exam_period_or_year');
            $table->unsignedInteger('total_examinees');
            $table->unsignedInteger('passed_count');
            $table->text('source_note')->nullable();
            $table->foreignId('entered_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('program');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_board_exam_results');
    }
};
