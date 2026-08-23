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
        Schema::create('field_charts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('field_detail_id');
            $table->foreign('field_detail_id')->references('id')->on('field_details')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->integer('columns_count');
            $table->string('first_column');
            $table->string('second_column')->nullable();
            $table->string('third_column')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_charts');
    }
};
