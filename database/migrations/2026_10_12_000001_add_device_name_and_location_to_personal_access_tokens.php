<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * امنیت حساب — مرحله‌ی دوم:
 * - device_name: نام تجاری دستگاه (مثل «Galaxy A22» برای SM-A225F)، یک بار هنگام ثبت دستگاه پر می‌شود.
 * - last_location: موقعیت تقریبی last_ip از GeoIP محلی، فقط وقتی IP عوض می‌شود.
 * - device_marketing_names: فهرست Google Play (Retail Branding, Marketing Name, Device, Model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('device_name', 100)->nullable()->after('device_model');
            $table->string('last_location', 150)->nullable()->after('last_ip');
        });

        Schema::create('device_marketing_names', function (Blueprint $table) {
            $table->id();
            $table->string('model', 100)->index();
            $table->string('brand', 100)->nullable();
            $table->string('marketing_name', 150);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_marketing_names');

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'last_location']);
        });
    }
};
