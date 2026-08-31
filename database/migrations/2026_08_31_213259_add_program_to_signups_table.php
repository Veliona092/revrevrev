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
        Schema::table('signups', function (Blueprint $table) {
            if (! Schema::hasColumn('signups', 'program')) {
                $table->string('program')->nullable()->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signups', function (Blueprint $table) {
            if (Schema::hasColumn('signups', 'program')) {
                $table->dropColumn('program');
            }
        });
    }
};
