<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_subparts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Deliberately NOT named "content" — modules.content already exists (mostly
            // unused) and reusing the name here would make the two easy to confuse in
            // queries and in the blade views that touch both models.
            $table->longText('body')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['module_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_subparts');
    }
};
