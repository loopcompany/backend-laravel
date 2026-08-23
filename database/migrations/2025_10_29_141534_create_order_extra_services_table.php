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
        Schema::create('order_extra_services', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->unsignedBigInteger('extra_service_id');
            $table->foreign('extra_service_id')->references('id')->on('extra_services')->onDelete('cascade');

            $table->string('title')->nullable();

            $table->unsignedBigInteger('extra_service_detail_id')->nullable();
            $table->foreign('extra_service_detail_id')->references('id')->on('extra_service_details')->onDelete('cascade');
            
            $table->bigInteger('price')->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_extra_services');
    }
};
