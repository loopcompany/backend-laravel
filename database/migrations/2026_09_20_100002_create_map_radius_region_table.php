<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_radius_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_radius_id')->constrained('map_radii')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['map_radius_id', 'region_id'], 'map_radius_region_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_radius_region');
    }
};
