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
        Schema::create('delivery_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('technicians')->onDelete('cascade');
            $table->string('name');
            $table->string('melicode');
            $table->longText('product_info');
            $table->string('accessories')->nullable();
            $table->string('label_code')->nullable();
            $table->string('appearance_defect')->nullable();
            $table->longText('user_description')->nullable();
            $table->longText('technical_description')->nullable();
            $table->dateTime('user_verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_reports');
    }
};
