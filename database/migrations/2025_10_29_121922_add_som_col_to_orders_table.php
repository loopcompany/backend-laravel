<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('is_technician_verified')->after('status')->default('0')->comment('0 pending, 1 verified, 2 not verified');
            $table->dateTime('send_to_loop')->after('is_technician_verified')->nullable()->comment('اعزام به لوپ');
            $table->string('duration')->after('send_to_loop')->nullable()->comment('مدت زمان انجام سفارش');
            $table->string('loop_description')->after('duration')->nullable()->comment('توضیحات لوپ بعد از اعزام به لوپ');
            $table->string('loop_cost_estimate')->after('loop_description')->nullable()->comment('اعلام هزینه تقریبی');
            $table->string('user_cancellation_reason')->after('loop_cost_estimate')->nullable()->comment('توضیحات کاربر در صورت لغو یا پذیرش زمان و توضیحات لوپ');
            $table->dateTime('user_cancellation_date')->after('user_cancellation_reason')->nullable()->comment('در صورتی که کاربر درخواست بازگشت محصولش را بدهد تاریخ اینجا پر می شود');
            $table->dateTime('user_accept_date')->after('user_cancellation_date')->nullable()->comment('در صورتی که کاربر بپذیره هزینه و توضیحات رو اینجا پر میشه');
            $table->string('user_return_followup_description')->after('user_accept_date')->nullable()->comment('توضیحات پیگیری عودت محصول');
            $table->date('return_date')->after('user_return_followup_description')->nullable()->comment('تاریخ بازگشت');
            $table->string('return_time')->after('return_date')->nullable()->comment('زمان بازگشت');
            $table->string('user_final_description')->after('return_time')->nullable()->comment('توضیحات نهایی کاربر پس از بازگشت محصول');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('is_technician_verified');
            $table->dropColumn('send_to_loop');
            $table->dropColumn('duration');
            $table->dropColumn('loop_description');
            $table->dropColumn('loop_cost_estimate');
            $table->dropColumn('user_cancellation_reason');
            $table->dropColumn('user_accept_date');
            $table->dropColumn('user_return_followup_description');
            $table->dropColumn('return_date');
            $table->dropColumn('return_time');
            $table->dropColumn('user_final_description');
        });
    }
};
