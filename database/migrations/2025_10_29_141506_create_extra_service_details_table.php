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
        Schema::create('extra_service_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extra_service_id')->nullable();
            $table->foreign('extra_service_id')->references('id')->on('extra_services')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->bigInteger('price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_service_details');
    }
};
