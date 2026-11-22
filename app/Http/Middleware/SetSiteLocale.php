<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Select an enabled public-site language without changing the admin locale. */
class SetSiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin', 'admin/*', 'livewire/*', 'filament/*')) {
            $languages=app(\App\Services\LocalizationCatalog::class)->languages();
            $codes=array_column($languages,'code');
            $locale=$request->query('lang',$request->cookie('site_locale','fa'));
            if(!is_string($locale) || !in_array($locale,$codes,true))$locale='fa';
            app()->setLocale($locale);
            $request->attributes->set('site_direction',collect($languages)->firstWhere('code',$locale)['direction']??'rtl');

        }

        $response=$next($request);
        if($request->has('lang') && !$request->is('admin','admin/*','livewire/*','filament/*'))$response->headers->setCookie(cookie('site_locale',app()->getLocale(),525600,null,null,$request->isSecure(),true,false,'lax'));
        return $response;
    }
}
