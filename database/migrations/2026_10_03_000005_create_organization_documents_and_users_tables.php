<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مدارک سازمان و کاربران مجاز سازمان (بخش ۳-۳ و ۳-۴ نیازمندی‌ها).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('file_path');                 // روی دیسک خصوصی local
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('mobile', 11);
            $table->string('role', 50)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'mobile']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_users');
        Schema::dropIfExists('organization_documents');
    }
};
