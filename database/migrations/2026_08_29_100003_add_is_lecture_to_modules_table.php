<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('modules', 'is_lecture')) {
            Schema::table('modules', function (Blueprint $table): void {
                $table->boolean('is_lecture')->default(false)->after('is_assignment');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('modules', 'is_lecture')) {
            Schema::table('modules', function (Blueprint $table): void {
                $table->dropColumn('is_lecture');
            });
        }
    }
};
