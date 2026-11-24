<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('settled_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
        });
        Schema::create('order_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->restrictOnDelete();
            $table->uuid('request_uuid')->unique();
            $table->char('payload_hash', 64);
            $table->string('method', 24);
            $table->unsignedBigInteger('amount');
            $table->string('collection_target', 20);
            $table->string('payment_channel', 20)->default('in_person');
            $table->string('item_description', 500)->nullable();
            $table->string('reference', 191)->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->string('custody_status', 16)->default('none')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->foreignId('order_receipt_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referral_commissions', function (Blueprint $table) { $table->dropConstrainedForeignId('approved_by'); });
        Schema::table('user_transactions', function (Blueprint $table) { $table->dropConstrainedForeignId('order_receipt_id'); });
        Schema::dropIfExists('order_receipts');
        Schema::table('orders', function (Blueprint $table) { $table->dropConstrainedForeignId('settled_by_admin_id'); $table->dropColumn('settled_at'); });
    }
};
