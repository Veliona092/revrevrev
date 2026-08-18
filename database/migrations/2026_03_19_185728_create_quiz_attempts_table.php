<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('module_id')
                  ->constrained('modules')
                  ->onDelete('cascade');

            $table->integer('score');           // number of correct answers
            $table->integer('total');           // total questions
            $table->integer('percentage');      // score percentage (0-100)

            $table->boolean('passed')->default(false);

            $table->integer('time_taken')->nullable();   // in minutes (optional)

            $table->timestamp('attempted_at')->useCurrent();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_attempts');
    }
};