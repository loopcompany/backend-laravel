<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_quotes', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable();
            $table->index(['kind', 'status', 'reminded_at', 'sent_at'], 'order_quotes_reminder_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_quotes', function (Blueprint $table) {
            $table->dropIndex('order_quotes_reminder_index');
            $table->dropColumn('reminded_at');
        });
    }
};
