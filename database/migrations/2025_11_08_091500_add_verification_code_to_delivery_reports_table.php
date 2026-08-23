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
        Schema::table('delivery_reports', function (Blueprint $table) {
            $table->string('verification_code', 60)->nullable()->after('technical_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_reports', function (Blueprint $table) {
            $table->dropColumn('verification_code');
        });
    }
};
