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
        Schema::table('technician_order_reports', function (Blueprint $table) {
            $table->string('max_price')->after('accessories')->nullable();
            $table->string('min_price')->after('max_price')->nullable();
            $table->string('product_password')->after('min_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_order_reports', function (Blueprint $table) {
            $table->dropColumn('max_price');
            $table->dropColumn('min_price');
            $table->dropColumn('product_password');
        });
    }
};
