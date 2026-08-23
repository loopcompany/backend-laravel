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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->nullable()->after('region');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('set null');
            
            $table->unsignedBigInteger('city_id')->nullable()->after('province_id');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            
            $table->unsignedBigInteger('region_id')->nullable()->after('city_id');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['region_id']);
            $table->dropColumn(['province_id', 'city_id', 'region_id']);
        });
    }
};
