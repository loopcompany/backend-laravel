<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('personnel_code', 50)->nullable()->unique()->after('email')
                ->comment('شناسه پرسنلی');
            $table->string('phone', 20)->nullable()->after('personnel_code')
                ->comment('شماره تلفن موبایل');
            $table->string('staff_type', 30)->nullable()->after('phone')
                ->comment('نوع پرسنل: اداری | میدانی | مدیر');
        });

        // همه‌ی ادمین‌های فعلی مدیر هستند
        DB::table('admins')->whereNull('staff_type')->update(['staff_type' => 'مدیر']);
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['personnel_code']);
            $table->dropColumn(['personnel_code', 'phone', 'staff_type']);
        });
    }
};
