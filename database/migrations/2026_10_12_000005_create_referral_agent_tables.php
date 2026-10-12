<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کیف پول مأمور خرید (بند ۹ سند اپ کاربر):
 * - کاربر «مأمور خرید» صاحب یک کد معرف چندبارمصرف است.
 * - از هر سفارش با کد او پورسانت ثبت می‌شود (pending → available / canceled).
 * - برداشت به شبا (صف پنل) یا تهاتر به کیف پول لوپ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_referral_agent')->default(false);
        });

        Schema::table('referral_codes', function (Blueprint $table) {
            // کد مأمور خرید با هر سفارش مصرف نمی‌شود
            $table->boolean('multi_use')->default(false)->after('status');
        });

        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();          // مأمور خرید
            $table->foreignId('referral_code_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_amount')->default(0);
            $table->unsignedTinyInteger('commission_percent')->default(0);
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->string('status', 20)->default('pending')->index();               // pending | available | withdrawn | offset | canceled
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 10);                                            // sheba | offset
            $table->string('status', 20)->default('pending')->index();               // pending | approved | paid | done | rejected
            $table->string('sheba_number', 26)->nullable();
            $table->string('tracking_code', 100)->nullable();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('user_transaction_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_withdrawals');
        Schema::dropIfExists('referral_commissions');

        Schema::table('referral_codes', function (Blueprint $table) {
            $table->dropColumn('multi_use');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_referral_agent');
        });
    }
};
