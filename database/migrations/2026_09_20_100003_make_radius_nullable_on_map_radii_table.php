<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The service area is now defined by the selected districts, not by a circle.
     * The radius/centre columns stay so already-shipped app builds keep reading
     * them from /api/locations/radii, but they are no longer required.
     */
    public function up(): void
    {
        Schema::table('map_radii', function (Blueprint $table) {
            $table->integer('radius')->nullable()->default(null)->change();
            $table->float('latitude')->nullable()->change();
            $table->float('longitude')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('map_radii', function (Blueprint $table) {
            $table->integer('radius')->default(1000)->change();
            $table->float('latitude')->nullable(false)->change();
            $table->float('longitude')->nullable(false)->change();
        });
    }
};
