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
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['main', 'duration', 'time', 'file'])->comment('نوع گزینه: main=کوتاه/بلند مدت, duration=مدت زمان, time=ساعت, file=فایل');
            $table->string('term_type')->nullable()->comment('short_term یا long_term - برای type=main');
            $table->string('label')->comment('برچسب فارسی نمایشی');
            $table->string('value')->nullable()->comment('مقدار گزینه');
            $table->integer('sort_order')->default(0)->comment('ترتیب نمایش');
            $table->boolean('is_active')->default(true)->comment('فعال/غیرفعال');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
