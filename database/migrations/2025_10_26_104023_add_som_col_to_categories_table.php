<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->time('start_at')->default('06:00:00')->nullable()->after('meta');
            $table->time('end_at')->default('00:00:00')->nullable()->after('start_at');
            $table->integer('duration')->default(60)->comment('in minutes')->after('end_at');
            $table->integer('has_gender')->default(0)->after('has_subcategory');
            $table->integer('is_fixed')->default(0)->after('has_subcategory');
            $table->string('slug')->nullable()->after('title');
            $table->longText('seo_content')->nullable()->after('slug');
            $table->string('meta_title')->nullable()->after('seo_content');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->json('faq_schema')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'start_at',
                'end_at',
                'duration',
                'has_gender',
                'is_fixed',
                'slug',
                'seo_content',
                'meta_title',
                'meta_description',
                'faq_schema',
            ]);
        });
    }
};
