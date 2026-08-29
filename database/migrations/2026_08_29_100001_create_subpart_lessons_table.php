<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subpart_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subpart_id')->constrained('module_subparts')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Same shape/naming reasoning as module_subparts.body — a Lesson is
            // essentially the same kind of leaf content, one level deeper.
            $table->longText('body')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['subpart_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subpart_lessons');
    }
};
