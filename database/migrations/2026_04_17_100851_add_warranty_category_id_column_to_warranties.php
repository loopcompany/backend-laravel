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
        Schema::table('warranties', function (Blueprint $table) {
            $table->unsignedBigInteger('warranty_category_id')->nullable()->after('id');
            $table->foreign('warranty_category_id')->references('id')->on('warranty_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranties', function (Blueprint $table) {
            $table->dropForeign(['warranty_category_id']);
            $table->dropColumn('warranty_category_id');
        });
    }
};
