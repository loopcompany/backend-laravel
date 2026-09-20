<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedTinyInteger('discount_percent');
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('promo_code_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('discount_amount')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();

            $table->unique(['promo_code_id', 'user_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('promo_code_id')
                ->nullable()
                ->after('referral_discount_percent')
                ->constrained('promo_codes')
                ->nullOnDelete();
            $table->unsignedTinyInteger('promo_discount_percent')
                ->default(0)
                ->after('promo_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'promo_discount_percent']);
        });

        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('promo_codes');
    }
};
