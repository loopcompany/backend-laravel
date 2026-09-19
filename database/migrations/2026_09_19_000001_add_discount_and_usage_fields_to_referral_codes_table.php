<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_codes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('status');
            $table->foreignId('used_by_user_id')->nullable()->after('discount_percent')->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable()->after('used_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('referral_codes', function (Blueprint $table): void {
            $table->dropForeign(['used_by_user_id']);
            $table->dropColumn(['discount_percent', 'used_by_user_id', 'used_at']);
        });
    }
};
