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
    Schema::table('classes', function (Blueprint $table) {
        // Adding the program column to group classes
        $table->string('program')->after('name')->nullable();
    });
}

public function down(): void
{
    Schema::table('classes', function (Blueprint $table) {
        $table->dropColumn('program');
    });
}
};
