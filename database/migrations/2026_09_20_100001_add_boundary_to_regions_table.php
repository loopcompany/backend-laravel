<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            // GeoJSON geometry (Polygon or MultiPolygon) of the region's border.
            // Kept as text rather than a spatial column so the same code runs on
            // MySQL and on the SQLite connection the test suite uses.
            $table->longText('boundary')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('boundary');
        });
    }
};
