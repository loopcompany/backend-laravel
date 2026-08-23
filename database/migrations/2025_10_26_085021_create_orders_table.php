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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('user_address_id');
            $table->foreign('user_address_id')->references('id')->on('user_addresses')->onDelete('cascade');
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');

            $table->tinyInteger('status')->default(0)->comment('0:pending- 1:processing- 2:done- 3:user cancel- 4:tech cancel- 5:admin cancel- 6:expire time');
            $table->tinyInteger('payment_status')->default(0);
            $table->tinyInteger('must_notify')->default(0);

            $table->bigInteger('pakar_price')->nullable();
            $table->bigInteger('technician_price')->nullable();
            $table->bigInteger('extra_price')->nullable();
            $table->bigInteger('discount_price')->nullable()->comment('درصورت نداشتن تخفیف null و درصورت داشتن میزان تخفیف ذخیره شود');

            $table->timestamp('set_off_at')->nullable()->comment('زمان راه افتادن تکنسین');
            $table->timestamp('arrived_at')->nullable()->comment('زمان رسیدن تکنسین');
            $table->timestamp('started_at')->nullable()->comment('شروع خدمت');
            $table->timestamp('finished_at')->nullable()->comment('پایان خدمت');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
