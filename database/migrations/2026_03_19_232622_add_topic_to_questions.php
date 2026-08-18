<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('quiz_questions', function (Blueprint $table) {
        $table->string('topic')->nullable()->after('question_text'); 
        // or simply: $table->string('topic')->nullable();
    });
}

public function down(): void
{
    Schema::table('quiz_questions', function (Blueprint $table) {
        $table->dropColumn('topic');
    });
}

};
