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
            $table->string('role')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('signups', function (Blueprint $table) {
            $table->enum('role', ['psych', 'educ', 'accountancy', 'teacher', 'admin'])
                ->nullable(false)
                ->change();
        });
    }
};
