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
        Schema::create('chart_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('field_chart_id');
            $table->foreign('field_chart_id')->references('id')->on('field_charts')->onDelete('cascade');
            $table->string('first');
            $table->string('second');
            $table->string('third')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_options');
    }
};
