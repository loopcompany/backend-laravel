@php
    $headerPhone = $globalContacts->where('type', 'phone')->first();
    $primaryLinks = [
        ['label'=>'دانلود اپلیکیشن‌های هوشمند', 'url'=>route('web.home').'#apps'],
        ['label'=>'دانلود نسخه ویندوز لوپ', 'url'=>route('web.home').'#windows-app'],
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
<link rel="stylesheet" href="{{ asset('assets/css/loop-site-header.css') }}?v=20261115-2">
<div class="loop-topbar">
    <div class="loop-topbar-inner">
        <nav class="loop-top-links" aria-label="دسترسی‌های سایت">
            @foreach (array_merge(array_slice($primaryLinks, 0, 2), array_slice($informationLinks, 0, 1)) as $link)
                <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
            @endforeach
            <details class="loop-header-guides"><summary>راهنما</summary><div>@foreach ($guides as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['label'] }}</a>@endforeach</div></details>
            <a href="{{ route('web.faqs') }}">سوالات متداول</a>
            @foreach ([$informationLinks[4], $informationLinks[2], $informationLinks[3]] as $link)<a href="{{ $link['url'] }}">{{ $link['desktop_label'] ?? $link['label'] }}</a>@endforeach
        </nav>
        <div class="loop-header-contact">
            @if ($headerPhone)<a href="{{ $headerPhone->link }}" class="loop-header-phone"><bdi>{{ $headerPhone->name }}</bdi></a>@endif
        </div>
    </div>
</div>
<header class="loop-glass-header" data-loop-header>
    <div class="loop-header-inner">
        <a class="loop-header-logo" href="{{ route('web.home') }}" aria-label="صفحهٔ نخست لوپ"><img src="{{ asset('assets/images/logo.png') }}" alt="LOOP" width="115" height="54"></a>
        <nav class="loop-primary-links" aria-label="منوی اصلی">@foreach ($primaryLinks as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach</nav>
        <button type="button" class="loop-menu-toggle" data-loop-menu-open aria-controls="loop-mobile-menu" aria-expanded="false" aria-label="باز کردن منوی سایت"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16"/></svg></button>
    </div>
</header>
<div class="loop-mobile-drawer" id="loop-mobile-menu" hidden>
    <button type="button" class="loop-menu-backdrop" data-loop-menu-close aria-label="بستن منو"></button>
    <div class="loop-menu-panel" role="dialog" aria-modal="true" aria-labelledby="loop-menu-title" tabindex="-1">
        <div class="loop-menu-title"><strong id="loop-menu-title">لوپ</strong><button type="button" data-loop-menu-close aria-label="بستن منو">×</button></div>
        <nav aria-label="منوی موبایل">
            @foreach (array_merge($primaryLinks, $informationLinks) as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach
            <details><summary>راهنما</summary>@foreach ($guides as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['label'] }}</a>@endforeach</details>
        </nav>
        <div class="loop-menu-visual"><img src="{{ asset('assets/new-style/mobile/hero-home.webp') }}" alt="هر اتصال، یک شروع" width="1023" height="1537" loading="lazy"></div>
    </div>
</div>
<script src="{{ asset('assets/js/loop-site-header.js') }}?v=20261115-2" defer></script>
