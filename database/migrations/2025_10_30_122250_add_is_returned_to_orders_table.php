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
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('returned_at')->nullable()->after('return_time')->comment('وقتی که محصول رو عودت دادند پر میشه');
            $table->integer('is_time_changed')->default(0)->after('time')->comment('اگر زمان سفارش توسط ادمین یا تکنسین عوض شده بود این ستون باید 1 بشه');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('returned_at');
            $table->dropColumn('is_time_changed');
        });
    }
};
