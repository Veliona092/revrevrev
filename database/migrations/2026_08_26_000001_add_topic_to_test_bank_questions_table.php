<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_bank_questions', function (Blueprint $table) {
            $table->string('topic')->nullable()->after('program');
        });
    }

    public function down(): void
    {
        Schema::table('test_bank_questions', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
    }
};