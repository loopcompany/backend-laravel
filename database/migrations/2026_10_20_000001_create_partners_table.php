<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * «سوابق همکاری» صفحه اصلی: شرکت‌ها و سازمان‌های همکار (نام، لوگو، لینک سایت پشت لوگو).
     * از پنل مدیریت › مدیریت محتوا › همکاران اضافه می‌شوند؛ اولین همکار (سماسد) همین‌جا ثبت می‌شود.
     */
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام شرکت یا سازمان');
            $table->string('logo')->comment('مسیر لوگو روی دیسک public');
            $table->string('website_url')->comment('سایت شرکت؛ پشت لوگو لینک می‌شود');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $logo = 'partners/samasad.png';
        if (!Storage::disk('public')->exists($logo)) {
            Storage::disk('public')->put($logo, file_get_contents(resource_path('images/partners/samasad.png')));
        }

        DB::table('partners')->insert([
            'name' => 'شرکت مهندسی سماسد',
            'logo' => $logo,
            'website_url' => 'https://samasad.com',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
