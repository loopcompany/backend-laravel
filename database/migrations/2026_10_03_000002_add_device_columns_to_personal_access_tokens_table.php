<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * هر «نشست» = یک ردیف personal_access_tokens و هر «دستگاه» = توکن‌های با device_id یکسان.
 * جدول بین کاربر، سازمان و تکنسین مشترک است؛ همه‌ی ستون‌ها nullable هستند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('device_id', 100)->nullable()->after('abilities');
            $table->string('platform', 20)->nullable()->after('device_id');       // android | ios | web
            $table->string('device_type', 20)->nullable()->after('platform');     // phone | tablet | desktop | web | browser | tv | unknown
            $table->string('device_brand', 100)->nullable()->after('device_type');
            $table->string('device_model', 100)->nullable()->after('device_brand');
            $table->string('os_name', 50)->nullable()->after('device_model');
            $table->string('os_version', 50)->nullable()->after('os_name');
            $table->string('app_version', 50)->nullable()->after('os_version');
            $table->string('ip_address', 45)->nullable()->after('app_version');   // IP هنگام ورود
            $table->string('last_ip', 45)->nullable()->after('ip_address');       // IP آخرین اتصال
            $table->text('user_agent')->nullable()->after('last_ip');

            $table->index(['tokenable_type', 'tokenable_id', 'device_id'], 'pat_tokenable_device_index');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('pat_tokenable_device_index');
            $table->dropColumn([
                'device_id', 'platform', 'device_type', 'device_brand', 'device_model',
                'os_name', 'os_version', 'app_version', 'ip_address', 'last_ip', 'user_agent',
            ]);
        });
    }
};
