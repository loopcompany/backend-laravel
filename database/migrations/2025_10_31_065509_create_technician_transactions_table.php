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
        Schema::create('technician_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technician_id');
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->decimal('price', 15, 2)->comment('مبلغ تراکنش');
            $table->integer('commission')->comment('درصد کمیسیون در زمان تراکنش');
            $table->string('referenceId')->nullable()->comment('شماره پیگیری');
            $table->tinyInteger('type')->default(1)
                  ->comment('1: واریز از سفارش، 2: برداشت، 3: کسر جریمه');
            $table->integer('status')->comment('100: موفق، -200: ناموفق، 0: در انتظار');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_transactions');
    }
};
