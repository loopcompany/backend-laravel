<?php

namespace App\Http\Middleware;

use App\Services\LocalizationCatalog;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Localize system messages only; customer/order content stays verbatim. */
class SetApiLocale
{
    public function handle(Request $request, Closure $next)
    {
        $catalog = app(LocalizationCatalog::class);
        $codes = array_column($catalog->languages(), 'code');
        $locale = $request->getPreferredLanguage($codes) ?: 'fa';
        app()->setLocale($locale);
        config(['app.fallback_locale' => 'fa']);
        $response = $next($request);
        if (!$response instanceof JsonResponse) {
            return $response;
        }

        $scope = $request->is('api/technician/*') ? 'technician' : 'customer';
        $selected = $catalog->resources($scope, $locale);
        $base = $catalog->resources($scope, 'fa');
        $translate = function ($value) use (&$translate, $selected, $base) {
            if (is_object($value)) {foreach ($value as $key => $item) $value->$key=$translate($item); return $value;}
            if (is_array($value)) {
                return array_map($translate, $value);
            }
            return is_string($value) ? ($selected[$value] ?? $base[$value] ?? $value) : $value;
        };
        $data = $response->getData();
        if (is_object($data)) {
            foreach (['message', 'errors'] as $field) {
                if (property_exists($data, $field)) {
                    $data->$field = $translate($data->$field);
                }
            }
            $response->setData($data);
        }
        $response->headers->set('Content-Language', $locale);
        return $response;
    }
}
