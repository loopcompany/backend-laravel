@php
    $footerContacts = $globalContacts ?? $contacts ?? collect();
    $footerSocials = $globalSocials ?? $socials ?? collect();
    $footerPhone = $footerContacts->where('type', 'phone')->first();
    $footerEmail = $footerContacts->where('type', 'email')->first();
    $footerOffice = $footerContacts->where('type', 'office')->first();
@endphp

<style>
    .loop-site-footer,
    .loop-site-footer * { box-sizing: border-box; }
    .loop-site-footer {
        direction: rtl;
        background: #071426;
        border-top: 1px solid #1c304b;
        color: #f7f2e7;
        padding: 70px 0 0;
        font-family: inherit;
    }
    .loop-site-footer .loop-footer-container {
        width: min(1320px, calc(100% - 48px));
        margin: 0 auto;
    }
    .loop-site-footer .loop-footer-grid {
        display: grid;
        grid-template-columns: 1.35fr repeat(3, minmax(150px, .8fr)) 1.2fr;
        gap: 36px;
        padding-bottom: 48px;
    }
    .loop-site-footer .loop-footer-brand-name {
        display: inline-block;
        color: #e1b649;
        font-family: Arial, sans-serif;
        font-size: 28px;
        font-weight: 700;
        direction: ltr;
    }
    .loop-site-footer .loop-footer-brand p {
        max-width: 34ch;
        margin: 16px 0 20px;
        color: #a9b4c5;
        font-size: 13.5px;
        line-height: 1.9;
    }
    .loop-site-footer .loop-footer-socials {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .loop-site-footer .loop-footer-socials a {
        display: flex;
        width: 36px;
        height: 36px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #29415f;
        border-radius: 50%;
        background: #10223a;
        transition: border-color .25s, transform .25s;
    }
    .loop-site-footer .loop-footer-socials a:hover {
        border-color: #d9a940;
        transform: translateY(-2px);
    }
    .loop-site-footer .loop-footer-socials img {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        object-fit: contain;
    }
    .loop-site-footer .loop-footer-col h6 {
        margin: 0 0 18px;
        color: #f7f2e7;
        font-size: 14.5px;
        font-weight: 700;
    }
    .loop-site-footer .loop-footer-col ul {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .loop-site-footer .loop-footer-col a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #a9b4c5;
        font-size: 13.5px;
        line-height: 1.7;
        text-decoration: none;
        transition: color .25s;
    }
    .loop-site-footer .loop-footer-col a:hover { color: #e1b649; }
    .loop-site-footer .loop-footer-contact li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #a9b4c5;
        font-size: 13.5px;
        line-height: 1.8;
    }
    .loop-site-footer .loop-footer-contact svg {
        width: 16px;
        height: 16px;
        margin-top: 5px;
        flex: none;
        stroke: #d9a940;
    }
    .loop-site-footer .loop-footer-contact a { direction: ltr; }
    .loop-site-footer .loop-footer-trust {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
    }
    .loop-site-footer .loop-footer-trust img {
        width: 64px;
        height: 64px;
        padding: 4px;
        border-radius: 8px;
        background: #fff;
        object-fit: contain;
    }
    .loop-site-footer .loop-footer-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 68px;
        gap: 20px;
        border-top: 1px solid #1c304b;
        color: #77869b;
        font-size: 12.5px;
    }
    .loop-site-footer .loop-footer-bottom svg { width: 46px; height: 22px; flex: none; }
    @media (max-width: 1100px) {
        .loop-site-footer .loop-footer-grid { grid-template-columns: repeat(3, 1fr); }
        .loop-site-footer .loop-footer-brand { grid-column: span 2; }
    }
    @media (max-width: 720px) {
        .loop-site-footer { padding-top: 52px; }
        .loop-site-footer .loop-footer-container { width: min(100% - 32px, 1320px); }
        .loop-site-footer .loop-footer-grid { grid-template-columns: 1fr 1fr; gap: 34px 24px; }
        .loop-site-footer .loop-footer-brand { grid-column: 1 / -1; }
        .loop-site-footer .loop-footer-contact-col { grid-column: 1 / -1; }
    }
    @media (max-width: 470px) {
        .loop-site-footer .loop-footer-grid { grid-template-columns: 1fr; }
        .loop-site-footer .loop-footer-brand,
        .loop-site-footer .loop-footer-contact-col { grid-column: auto; }
        .loop-site-footer .loop-footer-bottom {
            flex-direction: column;
            justify-content: center;
            padding: 20px 0;
            text-align: center;
        }
    }
</style>

<footer class="loop-site-footer">
    <div class="loop-footer-container">
        <div class="loop-footer-grid">
            <div class="loop-footer-brand">
                <a href="{{ route('web.home') }}" class="loop-footer-brand-name">LOOP</a>
                <p>لوپ یک اکوسیستم یکپارچه از خدمات و تکنولوژی برای سازمان‌ها، کسب‌وکارها و تیم‌های پیشرو است.</p>
                <div class="loop-footer-socials">
                    @foreach ($footerSocials as $social)
                        <a href="{{ $social->link }}" target="_blank" rel="noopener noreferrer" title="{{ $social->title ?? '' }}">
                            @if ($social->icon)
                                <img src="{{ asset('storage/' . $social->icon) }}" alt="{{ $social->title ?? '' }}">
                            @endif
                        </a>
                    @endforeach
                </div>
                <div class="loop-footer-trust">
                    <a referrerpolicy="origin" target="_blank" href="https://trustseal.enamad.ir/?id=6860144&Code=JqYnk84jEHOMnmsQ6dG36pA2uEMBmdYa">
                        <img referrerpolicy="origin" src="https://trustseal.enamad.ir/logo.aspx?id=6860144&Code=JqYnk84jEHOMnmsQ6dG36pA2uEMBmdYa" alt="نماد اعتماد الکترونیکی">
                    </a>
                    <img src="{{ asset('assets/images/samandehi.png') }}" alt="نشان ساماندهی">
                    <img src="{{ asset('assets/images/logonama.png') }}" alt="لوگو نما">
                </div>
            </div>

            <div class="loop-footer-col">
                <h6>لوپ</h6>
                <ul>
                    <li><a href="{{ route('web.home') }}">خانه</a></li>
                    <li><a href="{{ route('web.about') }}">درباره ما</a></li>
                    <li><a href="{{ route('web.contact') }}">تماس با ما</a></li>
                    <li><a href="{{ route('web.blogs') }}">مقالات و آموزش</a></li>
                </ul>
            </div>

            <div class="loop-footer-col">
                <h6>خدمات مشتریان</h6>
                <ul>
                    <li><a href="{{ route('web.faqs') }}">سوالات متداول</a></li>
                    <li><a href="{{ route('web.privacy') }}">حریم خصوصی</a></li>
                    <li><a href="{{ route('web.terms') }}">قوانین و مقررات</a></li>
                </ul>
            </div>

            <div class="loop-footer-col">
                <h6>همکاری و راهنما</h6>
                <ul>
                    <li><a href="{{ config('app.technician_register_url') }}">ثبت‌نام تکنسین لوپ</a></li>
                    <li><a href="{{ route('loop.learn') }}">کلاس‌های آموزش رایگان</a></li>
                    <li><a href="{{ asset('assets/guid/tech.pdf') }}">راهنمای اپلیکیشن تکنسین</a></li>
                    <li><a href="{{ asset('assets/guid/user.pdf') }}">راهنمای اپلیکیشن کاربر</a></li>
                    <li><a href="{{ asset('assets/guid/organ.pdf') }}">راهنمای کاربر سازمانی</a></li>
                </ul>
            </div>

            <div class="loop-footer-col loop-footer-contact-col">
                <h6>اطلاعات تماس</h6>
                <ul class="loop-footer-contact">
                    @if ($footerPhone)
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                            <a href="{{ $footerPhone->link ?: 'tel:' . $footerPhone->name }}">{{ $footerPhone->name }}</a>
                        </li>
                    @endif
                    @if ($footerEmail)
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            <a href="{{ $footerEmail->link ?: 'mailto:' . $footerEmail->name }}">{{ $footerEmail->name }}</a>
                        </li>
                    @endif
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
                        <a href="https://clpiran.com">clpiran.com</a>
                    </li>
                    @if ($footerOffice)
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M12 21s-7-5-9-10a5 5 0 0 1 9-4 5 5 0 0 1 9 4c-2 5-9 10-9 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                            <span>{{ $footerOffice->name }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="loop-footer-bottom">
            <span>تمامی حقوق این وب‌سایت متعلق به LOOP می‌باشد.</span>
            <svg viewBox="0 0 46 22" fill="none" aria-hidden="true"><path d="M12 4a7 7 0 1 0 0 14 12 12 0 0 0 11-7 12 12 0 0 1 11-7 7 7 0 1 1 0 14 12 12 0 0 1-11-7 12 12 0 0 0-11-7Z" stroke="#d9a940" stroke-width="1.6"/></svg>
        </div>
    </div>
</footer>
