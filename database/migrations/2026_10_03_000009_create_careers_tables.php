<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * همکاری با لوپ: درخواست همکاری سایت، گزینش و مصاحبه، و پرونده‌ی استخدامی.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code', 20)->unique();         // PCS-xxxxxx
            $table->string('status', 30)->default('new');
            $table->string('source', 20)->default('site');

            // ۱. اطلاعات فردی
            $table->string('full_name');
            $table->string('mobile', 11);
            $table->string('national_code', 10);
            $table->string('shahkar_status', 20)->default('unverified'); // verified | mismatch | unverified
            $table->string('city', 100);
            $table->string('district', 100)->nullable();
            $table->unsignedTinyInteger('age');
            $table->string('gender', 10);
            $table->string('marital_status', 10);
            $table->string('military_status', 20)->nullable();
            $table->string('military_status_other')->nullable();

            // ۲. موقعیت شغلی
            $table->string('job_title', 30);
            $table->string('job_title_other')->nullable();
            $table->string('cooperation_type', 20);

            // ۳. تحصیلات و سابقه
            $table->string('education_level', 20);
            $table->string('education_level_other')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('work_experience', 10);
            $table->string('related_experience', 10);

            // ۴ و ۵. مهارت‌ها و تخصص‌ها
            $table->json('skills')->nullable();                     // {skill_key: level}
            $table->json('interest_areas')->nullable();
            $table->string('interest_other')->nullable();
            $table->boolean('has_certificates')->default(false);
            $table->text('extra_skills')->nullable();

            // ۶. تکنسین میدانی
            $table->json('field_info')->nullable();

            // ۷. شرایط همکاری
            $table->string('start_availability', 20);
            $table->string('salary_type', 20);
            $table->unsignedBigInteger('salary_amount')->nullable();
            $table->string('overtime', 20);
            $table->string('shift_work', 20);

            // ۸. رزومه و مدارک (دیسک خصوصی)
            $table->string('resume_path')->nullable();
            $table->string('certificates_path')->nullable();
            $table->string('portfolio_path')->nullable();
            $table->string('portfolio_link')->nullable();

            // ۹. تأییدها
            $table->timestamp('confirmed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('sms_sent')->default(false);

            // فرم مدیریت گزینش و مصاحبه (بخش‌های ۲ تا ۸ و ۱۱)
            $table->json('recruitment')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('mobile');
            $table->index('national_code');
        });

        // ۱۰. سوابق و تاریخچه‌ی گزینش
        Schema::create('cooperation_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperation_request_id')->constrained('cooperation_requests')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('action', 50);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->string('result')->nullable();
            $table->text('note')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // پرونده‌ی استخدامی: بخش عمومی + بخش محرمانه‌ی رمزنگاری‌شده (مالی/بانکی)
        Schema::create('employment_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperation_request_id')->nullable()->constrained('cooperation_requests')->nullOnDelete();
            $table->string('personnel_code', 30)->nullable()->unique();
            $table->string('person_type', 20)->default('staff');
            $table->string('full_name');
            $table->string('national_code', 10)->nullable();
            $table->string('mobile', 11)->nullable();
            $table->string('employment_status', 20)->default('awaiting_start');
            $table->string('file_status', 20)->default('draft');
            $table->json('general')->nullable();
            $table->longText('confidential')->nullable();   // encrypted:array
            $table->json('attachments')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employment_file_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_file_id')->constrained('employment_files')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('action', 50);
            $table->json('changed_fields')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_file_logs');
        Schema::dropIfExists('employment_files');
        Schema::dropIfExists('cooperation_request_logs');
        Schema::dropIfExists('cooperation_requests');
    }
};
