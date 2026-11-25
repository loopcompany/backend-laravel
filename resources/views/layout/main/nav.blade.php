@php
    $headerPhone = $globalContacts->where('type', 'phone')->first();
    $primaryLinks = [
        ['label'=>'دانلود اپلیکیشن‌های هوشمند', 'url'=>config('downloads.site_paused') ? '#' : route('web.home').'#apps'],
        ['label'=>'دانلود نسخه ویندوز لوپ', 'url'=>config('downloads.site_paused') ? '#' : route('web.home').'#windows-app'],
        ['label'=>'پنل کاربر', 'url'=>rtrim(config('app.user_panel_url'), '/')],
        ['label'=>'پنل تکنسین', 'url'=>rtrim(config('app.technician_panel_url'), '/')],
        ['label'=>'ثبت‌نام تکنسین', 'url'=>route('web.technician.register-form')],
    ];
    $informationLinks = [
        ['label'=>'همکاری با لوپ', 'url'=>route('web.careers')],
        ['label'=>'سوالات متداول', 'url'=>route('web.faqs')],
        ['label'=>'درباره لوپ', 'desktop_label'=>'درباره ما', 'url'=>route('web.about')],
        ['label'=>'تماس با لوپ', 'desktop_label'=>'تماس با ما', 'url'=>route('web.contact')],
        ['label'=>'مقالات و آموزش', 'url'=>route('web.blogs')],
    ];
    $guides = [
        ['label'=>'راهنمای اپلیکیشن کاربر', 'url'=>asset('assets/guid/user.pdf')],
        ['label'=>'راهنمای اپلیکیشن سازمانی/شرکتی', 'url'=>asset('assets/guid/organ.pdf')],
        ['label'=>'راهنمای اپلیکیشن تکنسین', 'url'=>asset('assets/guid/tech.pdf')],
    ];
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/loop-site-header.css') }}?v=20261125">
<div class="loop-topbar">
    <div class="loop-topbar-inner">
        <nav class="loop-top-links" aria-label="{{ site_text('دسترسی‌های سایت') }}">
            @foreach (array_merge(array_slice($primaryLinks, 0, 2), array_slice($informationLinks, 0, 1)) as $link)
                <a href="{{ $link['url'] }}">{{ site_text($link['label']) }}</a>
            @endforeach
            <details class="loop-header-guides"><summary>{{ site_text('راهنما') }}</summary><div>@foreach ($guides as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ site_text($link['label']) }}</a>@endforeach</div></details>
            <a href="{{ route('web.faqs') }}">{{ site_text('سوالات متداول') }}</a>
            @foreach ([$informationLinks[4], $informationLinks[2], $informationLinks[3]] as $link)<a href="{{ $link['url'] }}">{{ site_text($link['desktop_label'] ?? $link['label']) }}</a>@endforeach
        </nav>
        <div class="loop-header-contact">
            @if ($headerPhone)<a href="{{ $headerPhone->link }}" class="loop-header-phone"><bdi>{{ $headerPhone->name }}</bdi></a>@endif
        </div>
    </div>
</div>
<header class="loop-glass-header" data-loop-header>
    <div class="loop-header-inner">
        <a class="loop-header-logo" href="{{ route('web.home') }}" aria-label="{{ site_text('صفحهٔ نخست لوپ') }}"><img src="{{ asset('assets/images/logo.png') }}" alt="LOOP" width="115" height="54"></a>
        <nav class="loop-primary-links" aria-label="{{ site_text('منوی اصلی') }}">@foreach ($primaryLinks as $link)<a href="{{ $link['url'] }}">{{ site_text($link['label']) }}</a>@endforeach</nav>
        <div class="loop-header-actions">
            <button type="button" class="loop-menu-toggle" data-loop-menu-open aria-controls="loop-mobile-menu" aria-expanded="false" aria-label="{{ site_text('باز کردن منوی سایت') }}"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16"/></svg></button>
            <button type="button" class="loop-language-toggle" data-loop-language-open aria-controls="loop-language-dialog" aria-haspopup="dialog" aria-label="{{ site_text('تغییر زبان') }}">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/></svg>
            </button>
        </div>
    </div>
</header>
<div class="loop-mobile-drawer" id="loop-mobile-menu" hidden>
    <button type="button" class="loop-menu-backdrop" data-loop-menu-close aria-label="{{ site_text('بستن منو') }}"></button>
    <div class="loop-menu-panel" role="dialog" aria-modal="true" aria-labelledby="loop-menu-title" tabindex="-1">
        <div class="loop-menu-title"><strong id="loop-menu-title">{{ site_text('لوپ') }}</strong><button type="button" data-loop-menu-close aria-label="{{ site_text('بستن منو') }}">×</button></div>
        <nav aria-label="{{ site_text('منوی موبایل') }}">
            @foreach (array_merge($primaryLinks, $informationLinks) as $link)<a href="{{ $link['url'] }}">{{ site_text($link['label']) }}</a>@endforeach
            <details><summary>{{ site_text('راهنما') }}</summary>@foreach ($guides as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ site_text($link['label']) }}</a>@endforeach</details>
        </nav>
        @if (app()->getLocale() === 'fa')<div class="loop-menu-visual"><img src="{{ asset('assets/new-style/mobile/hero-home.webp') }}" alt="{{ site_text('هر اتصال، یک شروع') }}" width="1023" height="1537" loading="lazy"></div>@endif
    </div>
</div>
<dialog class="loop-language-dialog" id="loop-language-dialog" aria-labelledby="loop-language-title">
    <div class="loop-language-panel">
        <div class="loop-language-title">
            <h2 id="loop-language-title">{{ site_text('تغییر زبان') }}</h2>
            <form method="dialog"><button type="submit" autofocus aria-label="{{ site_text('بستن') }}"><span aria-hidden="true">×</span></button></form>
        </div>
        <ul class="loop-language-options" data-loop-language-options data-no-translate>
            @foreach(app(\App\Services\LocalizationCatalog::class)->languages() as $language)
                <li><a href="{{ request()->fullUrlWithQuery(['lang' => $language['code']]) }}" lang="{{ $language['code'] }}" hreflang="{{ $language['code'] }}" dir="{{ $language['direction'] }}" @if(app()->getLocale() === $language['code']) aria-current="true" @endif>{{ $language['native_name'] }}</a></li>
            @endforeach
        </ul>
    </div>
</dialog>
<script src="{{ asset('assets/js/loop-site-header.js') }}?v=20261125" defer></script>
