<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('referral_code_id')->nullable()->after('discount_price')->constrained('referral_codes')->nullOnDelete();
            $table->unsignedTinyInteger('referral_discount_percent')->default(0)->after('referral_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['referral_code_id']);
            $table->dropColumn(['referral_code_id', 'referral_discount_percent']);
        });
    }
};
