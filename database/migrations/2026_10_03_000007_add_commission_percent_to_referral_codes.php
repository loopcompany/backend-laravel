<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کد معرف با دو درصد جدا (بند ۳): تخفیف خریدار (discount_percent، از قبل موجود) و پورسانت صاحب کد.
 * درصد پورسانت هنگام ثبت سفارش روی خود سفارش هم ذخیره می‌شود تا تغییر بعدی کد، سوابق را عوض نکند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_codes', function (Blueprint $table) {
            $table->unsignedTinyInteger('commission_percent')->default(0)->after('discount_percent');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('referral_commission_percent')->default(0)->after('referral_discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('referral_commission_percent');
        });

        Schema::table('referral_codes', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });
    }
};
