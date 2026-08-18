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
        Schema::create('mock_board_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_board_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            // Pre-Test stats
            $table->integer('pre_test_count')->default(0);
            $table->decimal('pre_test_mean', 5, 2)->nullable();
            $table->decimal('pre_test_std_dev', 5, 2)->nullable();
            // Pre-Boards stats
            $table->integer('pre_boards_count')->default(0);
            $table->decimal('pre_boards_mean', 5, 2)->nullable();
            $table->decimal('pre_boards_std_dev', 5, 2)->nullable();
            // ANOVA results
            $table->decimal('anova_f_statistic', 10, 4)->nullable();
            $table->decimal('anova_p_value', 10, 6)->nullable();
            $table->boolean('anova_significant')->nullable();
            $table->decimal('improvement_percentage', 5, 2)->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique('mock_board_id');
            $table->index('class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_board_statistics');
    }
};
