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
        if (Schema::hasColumn('module_progress', 'scroll_position')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->dropColumn('scroll_position');
            });
        }

        if (Schema::hasColumn('module_progress', 'scroll_height')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->dropColumn('scroll_height');
            });
        }

        if (Schema::hasColumn('quiz_attempts', 'time_taken')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->dropColumn('time_taken');
            });
        }

        if (Schema::hasColumn('quiz_questions', 'topic')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropColumn('topic');
            });
        }

        if (Schema::hasColumn('chats', 'title')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('module_progress', 'scroll_position')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->unsignedInteger('scroll_position')->default(0)->after('progress');
            });
        }

        if (! Schema::hasColumn('module_progress', 'scroll_height')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->unsignedInteger('scroll_height')->nullable()->after('scroll_position');
            });
        }

        if (! Schema::hasColumn('quiz_attempts', 'time_taken')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->integer('time_taken')->nullable()->after('passed');
            });
        }

        if (! Schema::hasColumn('quiz_questions', 'topic')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->string('topic')->nullable()->after('question_text');
            });
        }

        if (! Schema::hasColumn('chats', 'title')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->string('title')->nullable()->after('class_id');
            });
        }
    }
};
