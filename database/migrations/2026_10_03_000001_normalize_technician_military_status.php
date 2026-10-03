<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * یکسان‌سازی «وضعیت نظام وظیفه» با گزینه‌های اپ تکنسین.
 *
 * ستون قبلاً ENUM سه‌مقداری بود و مقادیر اپ (مثل «معافیت») در حالت strict ذخیره نمی‌شدند؛
 * به رشته تبدیل می‌شود و فهرست مجاز در Technician::MILITARY_STATUSES نگهداری می‌شود.
 * «در حال خدمت» معادلی در اپ ندارد و همان‌طور می‌ماند.
 */
return new class extends Migration
{
    private const MAP = [
        'معاف' => 'معافیت',
        'پایان خدمت' => 'اتمام خدمت',
    ];

    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->string('military_status', 50)->comment('وضعیت نظام وظیفه')->change();
        });

        foreach (self::MAP as $old => $new) {
            DB::table('technicians')->where('military_status', $old)->update(['military_status' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('technicians')->where('military_status', $new)->update(['military_status' => $old]);
        }

        // مقادیری که در ENUM قدیمی جا نمی‌شوند به نزدیک‌ترین معادل برمی‌گردند
        DB::table('technicians')
            ->whereNotIn('military_status', ['معاف', 'در حال خدمت', 'پایان خدمت'])
            ->update(['military_status' => 'معاف']);

        Schema::table('technicians', function (Blueprint $table) {
            $table->enum('military_status', ['معاف', 'در حال خدمت', 'پایان خدمت'])->comment('وضعیت نظام وظیفه')->change();
        });
    }
};
