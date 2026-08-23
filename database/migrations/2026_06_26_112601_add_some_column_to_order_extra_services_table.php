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
        Schema::table('order_extra_services', function (Blueprint $table) {
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('warranty')->nullable();
            $table->string('test_duration')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('number')->default(1);
            $table->integer('unit_price')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_extra_services', function (Blueprint $table) {
            $table->dropColumn(['brand', 'model', 'warranty', 'test_duration', 'barcode', 'number', 'unit_price']);
        });
    }
};
