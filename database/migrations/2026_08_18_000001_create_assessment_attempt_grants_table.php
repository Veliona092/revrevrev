<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_attempt_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('extra_attempts')->default(1);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            // Isang grant record lang bawat estudyante bawat module — dinadagdagan
            // na lang ang extra_attempts kapag nag-grant ulit ang teacher.
            $table->unique(['module_id', 'user_id'], 'module_user_grant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempt_grants');
    }
};
