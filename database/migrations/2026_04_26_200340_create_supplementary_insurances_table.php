<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplementary_insurances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('technician_name');
            $table->string('technician_code');
            $table->string('technician_melicode');
            $table->dateTime('history_start_at');
            $table->dateTime('history_end_at');
            $table->integer('duration');
            $table->dateTime('loop_start_at');
            $table->dateTime('loop_end_at');
            $table->string('loop_duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplementary_insurances');
    }
};
