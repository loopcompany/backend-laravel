<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * سند «نیازمندی‌های بک‌اند — تغییرات اپ کاربر (۱۴۰۵/۰۷/۲۶)»:
 * - cancelled_by / cancelled_at: منبع و زمان لغو سفارش (بخش ۱-۲)
 * - delivery_confirmed_at: تأیید و ارسال مشخصات تحویل برای کاربر از پنل ادمین (بخش ۲)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cancelled_by', 16)->nullable()->after('status')
                ->comment('user | technician | admin | system');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->timestamp('delivery_confirmed_at')->nullable()->after('delivered_at')
                ->comment('زمان تأیید و ارسال مشخصات تحویل برای کاربر از پنل');
        });

        // سفارش‌های لغوشده‌ی قبلی: منبع از کد وضعیت، زمان از تاریخ لغو کاربر یا آخرین به‌روزرسانی
        foreach ([3 => 'user', 4 => 'technician', 5 => 'admin', 6 => 'system'] as $status => $source) {
            DB::table('orders')->where('status', $status)->update([
                'cancelled_by' => $source,
                'cancelled_at' => DB::raw('COALESCE(user_cancellation_date, updated_at)'),
            ]);
        }

        // رسید سفارش‌هایی که قبلاً تحویل شده‌اند مثل قبل پر بماند
        DB::table('orders')->whereNotNull('delivered_at')->update([
            'delivery_confirmed_at' => DB::raw('delivered_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancelled_at', 'delivery_confirmed_at']);
        });
    }
};
