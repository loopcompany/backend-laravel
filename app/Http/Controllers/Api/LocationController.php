<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\City;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * دریافت لیست استان‌ها
     * 
     * @return JsonResponse
     */
    public function getProvinces(): JsonResponse
    {
        try {
            $provinces = Province::where('is_show', 1)
                ->select('id', 'code', 'title', 'latitude', 'longitude')
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'لیست استان‌ها با موفقیت دریافت شد.',
                'data' => $provinces
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست استان‌ها',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دریافت لیست شهرهای یک استان
     * 
     * @param int $provinceId
     * @return JsonResponse
     */
    public function getCitiesByProvince(int $provinceId): JsonResponse
    {
        try {
            $province = Province::find($provinceId);
            
            if (!$province) {
                return response()->json([
                    'success' => false,
                    'message' => 'استان مورد نظر یافت نشد.'
                ], 404);
            }

            $cities = City::where('province_id', $provinceId)
                ->where('is_show', 1)
                ->select('id', 'province_id', 'title', 'latitude', 'longitude')
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'لیست شهرها با موفقیت دریافت شد.',
                'data' => [
                    'province' => [
                        'id' => $province->id,
                        'title' => $province->title
                    ],
                    'cities' => $cities
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست شهرها',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دریافت لیست مناطق یک شهر
     * 
     * @param int $cityId
     * @return JsonResponse
     */
    public function getRegionsByCity(int $cityId): JsonResponse
    {
        try {
            

                $city = City::with('province:id,title')->find($cityId);
                
                if (!$city) {
                    return response()->json([
                        'success' => false,
                        'message' => 'شهر مورد نظر یافت نشد.'
                    ], 404);
                }
                $regions = Region::where('city_id', $cityId)
                    ->where('is_show', 1)
                    ->select('id', 'city_id', 'code', 'title', 'latitude', 'longitude')
                    ->orderBy('id')
                    ->get();
            


            return response()->json([
                'success' => true,
                'message' => 'لیست مناطق با موفقیت دریافت شد.',
                'data' => [
                    'province' => [
                        'id' => $city->province->id,
                        'title' => $city->province->title
                    ],
                    'city' => [
                        'id' => $city->id,
                        'title' => $city->title
                    ],
                    'regions' => $regions
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست مناطق',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جستجو در استان‌ها، شهرها و مناطق
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q');
            
            if (!$query || strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'لطفاً حداقل 2 کاراکتر برای جستجو وارد کنید.'
                ], 400);
            }

            $provinces = Province::where('is_show', 1)
                ->where('title', 'like', "%{$query}%")
                ->select('id', 'code', 'title', 'latitude', 'longitude')
                ->limit(10)
                ->get()
                ->map(function($province) {
                    return [
                        'id' => $province->id,
                        'title' => $province->title,
                        'code' => $province->code,
                        'type' => 'province',
                        'latitude' => $province->latitude,
                        'longitude' => $province->longitude,
                    ];
                });

            $cities = City::with('province:id,title')
                ->where('is_show', 1)
                ->where('title', 'like', "%{$query}%")
                ->select('id', 'province_id', 'title', 'latitude', 'longitude')
                ->limit(10)
                ->get()
                ->map(function($city) {
                    return [
                        'id' => $city->id,
                        'title' => $city->title,
                        'province_id' => $city->province_id,
                        'province_title' => $city->province->title ?? null,
                        'type' => 'city',
                        'latitude' => $city->latitude,
                        'longitude' => $city->longitude,
                    ];
                });

            $regions = Region::with('city.province:id,title')
                ->where('is_show', 1)
                ->where('title', 'like', "%{$query}%")
                ->select('id', 'city_id', 'code', 'title', 'latitude', 'longitude')
                ->limit(10)
                ->get()
                ->map(function($region) {
                    return [
                        'id' => $region->id,
                        'title' => $region->title,
                        'code' => $region->code,
                        'city_id' => $region->city_id,
                        'city_title' => $region->city->title ?? null,
                        'province_id' => $region->city->province_id ?? null,
                        'province_title' => $region->city->province->title ?? null,
                        'type' => 'region',
                        'latitude' => $region->latitude,
                        'longitude' => $region->longitude,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'نتایج جستجو',
                'data' => [
                    'provinces' => $provinces,
                    'cities' => $cities,
                    'regions' => $regions,
                    'total' => $provinces->count() + $cities->count() + $regions->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجو',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دریافت اطلاعات کامل یک مکان (استان، شهر یا منطقه)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getLocationDetails(Request $request): JsonResponse
    {
        try {
            $provinceId = $request->input('province_id');
            $cityId = $request->input('city_id');
            $regionId = $request->input('region_id');

            $data = [];

            if ($provinceId) {
                $province = Province::find($provinceId);
                if ($province) {
                    $data['province'] = [
                        'id' => $province->id,
                        'code' => $province->code,
                        'title' => $province->title,
                        'latitude' => $province->latitude,
                        'longitude' => $province->longitude,
                    ];
                }
            }

            if ($cityId) {
                $city = City::with('province:id,code,title')->find($cityId);
                if ($city) {
                    $data['city'] = [
                        'id' => $city->id,
                        'title' => $city->title,
                        'latitude' => $city->latitude,
                        'longitude' => $city->longitude,
                    ];
                    if (!isset($data['province']) && $city->province) {
                        $data['province'] = [
                            'id' => $city->province->id,
                            'code' => $city->province->code,
                            'title' => $city->province->title,
                        ];
                    }
                }
            }

            if ($regionId) {
                $region = Region::with('city.province')->find($regionId);
                if ($region) {
                    $data['region'] = [
                        'id' => $region->id,
                        'code' => $region->code,
                        'title' => $region->title,
                        'latitude' => $region->latitude,
                        'longitude' => $region->longitude,
                    ];
                    if (!isset($data['city']) && $region->city) {
                        $data['city'] = [
                            'id' => $region->city->id,
                            'title' => $region->city->title,
                        ];
                    }
                    if (!isset($data['province']) && $region->city && $region->city->province) {
                        $data['province'] = [
                            'id' => $region->city->province->id,
                            'code' => $region->city->province->code,
                            'title' => $region->city->province->title,
                        ];
                    }
                }
            }

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'اطلاعات مکانی یافت نشد.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'اطلاعات مکانی با موفقیت دریافت شد.',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات مکانی',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getMapRadii(): JsonResponse
    {
        try {
            $radii = \App\Models\MapRadius::first();

            return response()->json([
                'success' => true,
                'message' => 'لیست شعاع‌های نقشه با موفقیت دریافت شد.',
                'data' => $radii
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست شعاع‌های نقشه',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
