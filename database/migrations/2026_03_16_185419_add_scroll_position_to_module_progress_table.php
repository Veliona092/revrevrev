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
        Schema::table('module_progress', function (Blueprint $table) {
            $table->unsignedInteger('scroll_position')->default(0)->after('progress'); // last scrollTop value
            $table->unsignedInteger('scroll_height')->nullable()->after('scroll_position'); // total scrollable height
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_progress', function (Blueprint $table) {
            //
        });
    }
};
