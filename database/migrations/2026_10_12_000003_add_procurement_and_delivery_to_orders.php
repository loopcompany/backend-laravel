<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بند ۱۱ و ۱۵ سند اپ کاربر:
 * - اقلام «تامین تجهیزات / کالا»، نحوه‌ی ارائه و جنسیت تکنسین که اپ سازمانی هنگام ثبت سفارش می‌فرستد.
 * - استعلام قیمت تامین کالا (پیش‌رسید، پیش‌پرداخت و باقی‌مانده).
 * - مشخصات تحویل کالا / محصولی که از لوپ به کاربر برمی‌گردد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('procurement_items')->nullable();
            $table->json('delivery_mode')->nullable();
            $table->string('technician_gender', 10)->nullable();

            $table->string('delivery_receiver_type', 20)->nullable();      // self | representative
            $table->string('delivery_receiver_name', 191)->nullable();
            $table->string('delivery_receiver_mobile', 20)->nullable();
            $table->string('delivery_receiver_national_id', 10)->nullable();
            $table->string('delivery_method', 30)->nullable();             // loop_technician | user | courier
            $table->string('delivery_status', 20)->nullable();             // pending | ready | shipped | delivered
            $table->timestamp('delivered_at')->nullable();
        });

        Schema::create('order_procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('awaiting_quote')->index();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('prepayment_percent')->default(60);
            $table->unsignedBigInteger('prepayment_amount')->nullable();
            $table->timestamp('prepayment_paid_at')->nullable();
            $table->unsignedBigInteger('remaining_amount')->nullable();
            $table->timestamp('remaining_paid_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_procurement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_procurement_id')->constrained()->cascadeOnDelete();
            $table->string('item_id', 100)->nullable();
            $table->string('title', 191);
            $table->string('condition', 10)->default('new');               // new | used
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price')->nullable();
            $table->string('description', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_procurement_items');
        Schema::dropIfExists('order_procurements');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'procurement_items', 'delivery_mode', 'technician_gender',
                'delivery_receiver_type', 'delivery_receiver_name', 'delivery_receiver_mobile',
                'delivery_receiver_national_id', 'delivery_method', 'delivery_status', 'delivered_at',
            ]);
        });
    }
};
