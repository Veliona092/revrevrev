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
    Schema::create('modules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
        $table->string('title');                    // e.g. "Module 1: Introduction"
        $table->text('description')->nullable();
        $table->string('file_path')->nullable();    // local path or URL
        $table->string('file_type')->nullable();    // pdf, pptx, docx, quiz, assignment
        $table->integer('order')->default(0);       // optional sort order
        $table->boolean('is_quiz')->default(false);
        $table->boolean('is_assignment')->default(false);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
