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
        Schema::create('technician_order_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('technicians')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('name')->comment('نام و نام خانوادگی تحویل دهنده به تکنسین');
            $table->string('melicode')->comment('کد ملی تحویل دهنده به تکنسین');
            $table->string('product_name')->nullable()->comment('نام محصول تحویل داده شده به تکنسین');
            $table->string('product_brand')->nullable()->comment('برند محصول تحویل داده شده به تکنسین');
            $table->string('product_model')->nullable()->comment('مدل محصول تحویل داده شده به تکنسین');
            $table->string('product_color')->nullable()->comment('رنگ محصول تحویل داده شده به تکنسین');
            $table->string('product_serial_number')->nullable()->comment('شماره سریال محصول تحویل داده شده به تکنسین');
            $table->string('asset_label_code')->nullable()->comment('کد لیبل اموال');
            $table->string('accessories')->nullable()->comment('لوازم همراه');
            $table->string('user_reported_issues')->nullable()->comment('ایرادات گزارششده توسط کاربر');
            $table->string('technician_reported_issues')->nullable()->comment('ایرادات گزارش شده توسط تکنسین');
            $table->string('technician_observed_issues')->nullable()->comment('ایرادات ظاهری مشاهده شده توسط تکنسین');
            $table->string('user_requested_services')->nullable()->comment('توضیحات / خدمات درخواستی کاربر');
            $table->dateTime('user_confirmed_at')->nullable()->comment('زمانی که کاربر اطلاعات ثبت شده تکنسین رو تایید کنه اینجا میاد');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_order_reports');
    }
};
