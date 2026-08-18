<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signups', function (Blueprint $table) {
            if (! Schema::hasColumn('signups', 'name')) {
                $table->string('name', 150)->nullable()->after('idnumber');
            }
        });
    }

    public function down(): void
    {
        Schema::table('signups', function (Blueprint $table) {
            if (Schema::hasColumn('signups', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
