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
        Schema::table('letter_rates', function (Blueprint $table) {
            $table->unsignedBigInteger('letter_rate_category_id')->nullable()->after('id');
            $table->foreign('letter_rate_category_id')->references('id')->on('letter_rate_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letter_rates', function (Blueprint $table) {
            $table->dropForeign(['letter_rate_category_id']);
            $table->dropColumn('letter_rate_category_id');
        });
    }
};
