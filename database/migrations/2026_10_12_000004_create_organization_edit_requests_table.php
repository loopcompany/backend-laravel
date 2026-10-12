<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «درخواست ویرایش» فیلدهای قفل حساب سازمانی با نامه‌ی مهر و امضادار (بند ۱۰ سند اپ کاربر).
 * پس از تأیید پنل، فیلدهای باز شده یک بار قابل ویرایش‌اند و بعد از ذخیره دوباره قفل می‌شوند (used).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();     // pending | approved | rejected | used
            $table->text('description');
            $table->string('letter_path');
            $table->json('fields')->nullable();                             // فیلدهای درخواستی کاربر
            $table->json('unlocked_fields')->nullable();                    // فیلدهای باز شده توسط پنل (null = همه)
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_edit_requests');
    }
};
