<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, matches users.id and class_user.class_id

            $table->string('name', 100);
            $table->string('code', 20)->nullable()->unique();
            $table->year('school_year')->nullable();
            $table->text('description')->nullable();

            // Foreign key to users.id
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
