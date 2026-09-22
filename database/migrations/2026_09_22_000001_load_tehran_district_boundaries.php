<?php

use App\Models\MapRadius;
use App\Services\DistrictLocator;
use Database\Seeders\TehranDistrictSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The district picker is empty until the district borders are in `regions`.
     * Load them (and the zones of the split districts) as part of the deploy
     * instead of relying on someone running the seeder by hand. The seeder
     * upserts, so re-running it after the GeoJSON changes is safe.
     */
    public function up(): void
    {
        (new TehranDistrictSeeder)->run();

        // A district that is now split can no longer be selected whole. Where it
        // was, select all of its zones, so coverage is unchanged until an admin
        // deselects the part that is out of service.
        MapRadius::with('regions.zones')->get()->each(function (MapRadius $area) {
            $split = $area->regions->filter(fn ($region) => $region->zones->isNotEmpty());

            if ($split->isEmpty()) {
                return;
            }

            $area->zones()->syncWithoutDetaching($split->flatMap->zones->pluck('id')->all());
            $area->regions()->detach($split->pluck('id')->all());
        });

        DistrictLocator::flushCache();
    }

    public function down(): void
    {
        // Region rows are referenced by addresses and orders; leave them.
    }
};
