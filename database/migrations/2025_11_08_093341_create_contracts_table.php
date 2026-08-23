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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('عنوان قرارداد');
            $table->text('description')->nullable()->comment('توضیحات قرارداد');
            $table->string('pdf_path')->comment('مسیر فایل PDF قرارداد');
            $table->boolean('is_active')->default(true)->comment('فعال/غیرفعال');
            $table->unsignedBigInteger('created_by')->nullable()->comment('ادمینی که قرارداد را ثبت کرده');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
