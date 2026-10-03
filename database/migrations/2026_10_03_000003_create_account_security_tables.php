<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * امنیت حساب (کاربر عادی، سازمانی/شرکتی و تکنسین) — سند «فیلد امنیت حساب».
 * همه‌ی جدول‌ها polymorphic هستند (account_type / account_id) تا برای هر نوع حساب کار کنند.
 */
return new class extends Migration
{
    public function up(): void
    {
        // فعالیت‌های اخیر حساب (رویدادهای امنیتی)
        Schema::create('account_activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('account');
            $table->string('type', 50);
            $table->string('title');
            $table->string('result', 10)->default('success'); // success | failed
            $table->string('device_id', 100)->nullable();
            $table->string('device')->nullable();
            $table->string('os')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('approx_location')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['account_type', 'account_id', 'created_at'], 'account_activities_account_created_index');
        });

        // هشدارهای امنیتی
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->morphs('account');
            $table->string('type', 50);
            $table->string('title');
            $table->text('message');
            $table->string('severity', 10)->default('medium'); // low | medium | high | critical
            $table->string('suggested_action')->nullable();
            $table->string('device')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['account_type', 'account_id', 'read_at'], 'security_alerts_account_read_index');
        });

        // تنظیمات امنیتی هر حساب (تأیید دومرحله‌ای، اعلان‌ها)
        Schema::create('account_security_settings', function (Blueprint $table) {
            $table->id();
            $table->morphs('account');
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_method', 10)->nullable(); // sms | app
            $table->text('two_factor_secret')->nullable();         // encrypted (روش app)
            $table->text('two_factor_pending_secret')->nullable(); // encrypted، تا تأیید فعال‌سازی
            $table->text('two_factor_recovery_codes')->nullable(); // encrypted JSON از hashها
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('security_alerts_enabled')->default(true);
            $table->timestamps();

            $table->unique(['account_type', 'account_id'], 'account_security_settings_account_unique');
        });

        // دستگاه‌هایی که تا حالا با این حساب وارد شده‌اند (برای هشدار «ورود از دستگاه جدید»)
        Schema::create('account_known_devices', function (Blueprint $table) {
            $table->id();
            $table->morphs('account');
            $table->string('device_id', 100);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->unique(['account_type', 'account_id', 'device_id'], 'account_known_devices_unique');
        });

        // درخواست حذف حساب کاربری
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_kind', 20); // individual | organization
            $table->string('status', 20)->default('pending'); // pending | approved | done | canceled | rejected
            $table->text('reason')->nullable();
            $table->string('phone_snapshot', 20)->nullable();
            $table->string('name_snapshot')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
        Schema::dropIfExists('account_known_devices');
        Schema::dropIfExists('account_security_settings');
        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('account_activities');
    }
};
