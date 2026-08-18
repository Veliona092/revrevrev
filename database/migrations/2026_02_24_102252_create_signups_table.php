<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signups', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();                  // gmail or any email
            $table->string('idnumber')->unique();               // matches users.idnumber later
            $table->string('password');                         // hashed
            $table->enum('role', ['psych', 'educ', 'accountancy', 'teacher', 'admin']);
                                     // adjust default if needed
            $table->string('verification_token')->unique()->nullable();
            $table->timestamp('verified_at')->nullable();       // optional: when verified
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signups');
    }
};