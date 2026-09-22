<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some districts are only partly inside the service area (21 and 22 are cut
     * along Kerman Khodro / Darou Pakhsh / Ardestani). Such a district is split
     * into zones, and the admin selects zones instead of the whole district.
     */
    public function up(): void
    {
        Schema::create('service_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('title');
            // GeoJSON geometry, as on regions.boundary
            $table->longText('boundary');
            $table->timestamps();
        });

        Schema::create('map_radius_service_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_radius_id')->constrained('map_radii')->cascadeOnDelete();
            $table->foreignId('service_zone_id')->constrained('service_zones')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['map_radius_id', 'service_zone_id'], 'map_radius_service_zone_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_radius_service_zone');
        Schema::dropIfExists('service_zones');
    }
};
