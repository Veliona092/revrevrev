<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            // Base na bilang ng attempts na pwede sa lahat ng estudyante (default 1).
            // Editable ng teacher/admin bawat module/assessment.
            $table->unsignedTinyInteger('max_attempts')->default(1)->after('passing_grade');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('max_attempts');
        });
    }
};