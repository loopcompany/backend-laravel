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
        Schema::create('user_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('price', 15, 2); // مبلغ تراکنش
            $table->string('referenceId')->nullable(); // شماره پیگیری درگاه
            $table->tinyInteger('type')->default(1)->comment('1 wallet recharge, 2 gateway payment, 3 wallet payment');
            $table->integer('status')->comment('100: موفق، -200: ناموفق، 0: در انتظار');
            $table->text('description')->nullable(); // توضیحات اضافی
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_transactions');
    }
};
