<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اطلاعات حساب سازمانی/شرکتی/نیمه‌دولتی (بند ۴): شماره ثبت، شماره اقتصادی و تعلیق حساب.
 * شناسه‌ی ملی سازمان همان users.melicode است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('registration_number', 30)->nullable()->after('organization_code')->comment('شماره ثبت شرکت');
            $table->string('economic_code', 20)->nullable()->after('registration_number')->comment('شماره اقتصادی');
            $table->timestamp('suspended_at')->nullable()->comment('تعلیق حساب توسط پنل مدیریت');
            $table->text('suspension_reason')->nullable();
        });

        Schema::table('edit_requests', function (Blueprint $table) {
            $table->string('melicode', 20)->nullable();
            $table->string('registration_number', 30)->nullable()->after('melicode');
            $table->string('economic_code', 20)->nullable()->after('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('edit_requests', function (Blueprint $table) {
            $table->dropColumn(['melicode', 'registration_number', 'economic_code']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['registration_number', 'economic_code', 'suspended_at', 'suspension_reason']);
        });
    }
};
