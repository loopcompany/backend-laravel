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
            // فیلدهای مربوط به زمان نگهداری و سرویس (فقط برای سازمان‌ها)
            $table->string('service_schedule_type', 20)->nullable()->comment('short_term یا long_term');
            
            // فیلدهای بلند مدت
            $table->string('service_schedule_long_duration', 50)->nullable()->comment('مدت زمان بلند مدت');
            $table->date('service_schedule_long_date')->nullable()->comment('تاریخ شروع بلند مدت');
            $table->string('service_schedule_long_time', 50)->nullable()->comment('ساعت بلند مدت');
            $table->string('service_schedule_long_file')->nullable()->comment('فایل بلند مدت');
            
            // فیلدهای کوتاه مدت
            $table->date('service_schedule_short_date')->nullable()->comment('تاریخ کوتاه مدت');
            $table->string('service_schedule_short_time', 50)->nullable()->comment('ساعت کوتاه مدت');
            $table->string('service_schedule_short_file')->nullable()->comment('فایل کوتاه مدت');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'service_schedule_type',
                'service_schedule_long_duration',
                'service_schedule_long_date',
                'service_schedule_long_time',
                'service_schedule_long_file',
                'service_schedule_short_date',
                'service_schedule_short_time',
                'service_schedule_short_file',
            ]);
        });
    }
};
