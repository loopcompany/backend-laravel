<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('title');
            $table->text('meta_description')->nullable()->after('seo_title');
            $table->json('faqs')->nullable()->after('meta_description'); // JSON برای سوال و جواب‌ها
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'meta_description', 'faqs']);
        });
    }
};
