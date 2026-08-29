<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('modules', 'quiz_stage')) {
            Schema::table('modules', function (Blueprint $table): void {
                $table->enum('quiz_stage', ['pre_test', 'post_test'])->nullable()->after('is_lecture');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('modules', 'quiz_stage')) {
            Schema::table('modules', function (Blueprint $table): void {
                $table->dropColumn('quiz_stage');
            });
        }
    }
};
