<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('referral_codes', function (Blueprint $table) {
            $table->string('policy_version')->nullable();
            $table->string('commission_mode')->default('table');
            $table->unsignedBigInteger('manual_commission_amount')->nullable();
            $table->unsignedBigInteger('used_order_id')->nullable()->unique();
            $table->unsignedBigInteger('issued_by_admin_id')->nullable();
        });
        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->string('calculation_note')->nullable();
            $table->unsignedBigInteger('manual_override_amount')->nullable();
        });
        Schema::create('code_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('code_type');
            $table->unsignedBigInteger('code_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('category')->nullable();
            $table->string('channel');
            $table->string('recipient')->nullable();
            $table->string('status')->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamps();
            $table->index(['code_type', 'code_id']);
        });
        Schema::table('promo_codes', fn (Blueprint $table) => $table->string('category')->nullable());
        Schema::table('incentive_plans', function (Blueprint $table) {
            // Existing descriptive plans must not silently become cash entitlements.
            $table->string('benefit_type')->default('legacy');
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('benefit_code')->nullable();
            $table->string('benefit_location')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->unsignedBigInteger('fulfilled_by_admin_id')->nullable();
            $table->unsignedBigInteger('technician_transaction_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('incentive_plans', fn (Blueprint $table) => $table->dropColumn(['benefit_type','amount','benefit_code','benefit_location','requested_at','redeemed_at','fulfilled_by_admin_id','technician_transaction_id']));
        Schema::table('promo_codes', fn (Blueprint $table) => $table->dropColumn('category'));
        Schema::dropIfExists('code_deliveries');
        Schema::table('referral_commissions', fn (Blueprint $table) => $table->dropColumn(['calculation_note','manual_override_amount']));
        Schema::table('referral_codes', fn (Blueprint $table) => $table->dropColumn(['policy_version','commission_mode','manual_commission_amount','used_order_id','issued_by_admin_id']));
    }
};
