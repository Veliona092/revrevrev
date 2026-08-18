<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('quiz_questions', function (Blueprint $table) {
        $table->string('domain')->nullable()->after('difficulty');
        $table->text('explanation')->nullable()->after('domain');
    });
}

public function down(): void
{
    Schema::table('quiz_questions', function (Blueprint $table) {
        $table->dropColumn(['domain', 'explanation']);
    });
}
};