<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->string('order_payment_kind', 12)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->dropColumn('order_payment_kind');
        });
    }
};
