<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subpart_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subpart_id')->constrained('module_subparts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('progress', 5, 2)->default(0);
            $table->unsignedInteger('scroll_position')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['subpart_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subpart_progress');
    }
};
