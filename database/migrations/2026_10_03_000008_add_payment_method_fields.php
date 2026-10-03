<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * روش و کانال پرداخت (بند ۷) روی سفارش و تراکنش.
 * payment_method: cash | card_to_card | sheba | bank_transfer | in_app | unpaid | remaining
 * payment_channel: app | site | in_person
 * برای رکوردهای قدیمی (null) مقدار از روی وضعیت پرداخت محاسبه می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('payment_status');
            $table->string('payment_channel', 20)->nullable()->after('payment_method');
            $table->unsignedBigInteger('remaining_amount')->nullable()->after('payment_channel');
        });

        Schema::table('user_transactions', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('type');
            $table->string('payment_channel', 20)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_channel']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_channel', 'remaining_amount']);
        });
    }
};
