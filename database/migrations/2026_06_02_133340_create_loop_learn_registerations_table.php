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
        Schema::create('loop_learn_registerations', function (Blueprint $table) {
            $table->id();
            $table->string('class');
            $table->string('register_as');
            $table->string('mastery_soft_level');
            $table->string('mastery_hard_level');
            $table->string('goal');
            $table->string('name');
            $table->string('lname');
            $table->string('birth_date');
            $table->string('marriage');
            $table->string('gender');
            $table->string('nationality');
            $table->string('education');
            $table->string('phone');
            $table->string('telephone');
            $table->string('address');
            $table->string('vehicle');
            $table->string('certificate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loop_learn_registerations');
    }
};
