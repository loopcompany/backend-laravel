<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\MapRadius;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * Loads the 22 municipal districts (مناطق) of Tehran, with their borders,
 * into the `regions` table.
 *
 * Source: OpenStreetMap boundary relations at admin_level=9, simplified to ~25 m
 * by database/data/build-tehran-districts.py. Re-runnable: rows are matched on
 * (city_id, code) and updated in place, so existing region ids are never broken.
 */
class TehranDistrictSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/tehran-districts.geojson');

        if (! is_file($path)) {
            $this->command?->error("GeoJSON not found at {$path}");
            return;
        }

        $city = City::where('title', 'تهران')->first();

        if (! $city) {
            $province = Province::firstOrCreate(['title' => 'تهران'], ['code' => '21', 'is_show' => 1]);
            $city = City::create([
                'province_id' => $province->id,
                'title' => 'تهران',
                'latitude' => 35.6892,
                'longitude' => 51.3890,
                'is_show' => 1,
            ]);
            $this->command?->info("Created city تهران (id {$city->id}).");
        }

        $geojson = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $created = $updated = 0;

        foreach ($geojson['features'] as $feature) {
            $props = $feature['properties'];
            $code = str_pad((string) $props['district'], 2, '0', STR_PAD_LEFT);

            $region = Region::withTrashed()->firstOrNew([
                'city_id' => $city->id,
                'code' => $code,
            ]);

            $region->exists ? $updated++ : $created++;

            $region->fill([
                'title' => 'منطقه ' . $this->toPersianDigits($props['district']),
                'latitude' => $props['center_lat'],
                'longitude' => $props['center_lng'],
                'is_show' => 1,
            ]);
            $region->boundary = json_encode($feature['geometry'], JSON_UNESCAPED_UNICODE);
            $region->deleted_at = null;
            $region->save();
        }

        $this->command?->info("Tehran districts: {$created} created, {$updated} updated (city id {$city->id}).");

        // The admin panel edits a single pre-existing service-area row and has no
        // create action, so make sure one exists.
        if (! MapRadius::query()->exists()) {
            MapRadius::create([
                'radius' => null,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
            ]);
            $this->command?->info('Created the empty service-area record.');
        }
    }

    private function toPersianDigits(int $number): string
    {
        return str_replace(range(0, 9), ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], (string) $number);
    }
}
