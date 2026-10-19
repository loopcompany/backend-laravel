<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * فرم ثبت‌نام (اپ و سایت) دیگر licence_date را نمی‌فرستد (به‌جایش certificate_* آمده)
     * و نوع وسیله نقلیه اختیاری است؛ بدون این تغییر insert با خطای 1364 شکست می‌خورد.
     */
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->date('licence_date')->nullable()->comment('تاریخ اعتبار گواهینامه')->change();
            $table->string('vehicle_type')->nullable()->comment('نوع وسیله نقلیه')->change();
        });
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->date('licence_date')->nullable(false)->comment('تاریخ اعتبار گواهینامه')->change();
            $table->string('vehicle_type')->nullable(false)->comment('نوع وسیله نقلیه')->change();
        });
    }
};
