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
        Schema::create('organization_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('contract_file_path')->comment('مسیر فایل PDF قرارداد امضا شده');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('وضعیت: pending/approved/rejected');
            $table->text('rejection_reason')->nullable()->comment('دلیل رد قرارداد');
            $table->timestamp('uploaded_at')->useCurrent()->comment('تاریخ آپلود');
            $table->timestamp('reviewed_at')->nullable()->comment('تاریخ بررسی توسط ادمین');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('شناسه ادمینی که بررسی کرده');
            $table->timestamps();
            $table->softDeletes();
            
            // Index برای جستجوی سریع‌تر
            $table->index(['organization_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_contracts');
    }
};
