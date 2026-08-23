@extends('layout.main.header')
@section('content')

    <picture class="about-page-hero">
        <source
            media="(max-width: 767px)"
            srcset="{{ asset('assets/new-style/mobile/about.jpg') }}"
            width="1024"
            height="1536"
        >
        <img
            src="{{ asset('assets/new-style/about.jpg') }}"
            alt="درباره لوپ"
            width="1672"
            height="941"
            fetchpriority="high"
        >
    </picture>

    <section class="about-connection">
        <div class="about-connection-panel">
            <h1>هر اتصال، آغاز یک تجربه جدید است.</h1>
            <p lang="en" dir="ltr">Every connection is the beginning of a new experience.</p>
            <div class="about-connection-mark" aria-hidden="true">
                <span></span>
            </div>
        </div>
    </section>

    <section class="loop-journey">
        <div class="loop-journey-inner">
            <h2>مسیر شکل‌گیری <span dir="ltr">LOOP</span></h2>

            <svg class="loop-journey-path" viewBox="0 0 1600 120" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <filter id="journeyGlow" x="-20%" y="-100%" width="140%" height="300%">
                        <feGaussianBlur stdDeviation="3" result="blur"/>
                        <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                    <linearGradient id="journeyGold" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#9d6a16"/>
                        <stop offset=".5" stop-color="#f1bd4f"/>
                        <stop offset="1" stop-color="#9d6a16"/>
                    </linearGradient>
                </defs>
                <path d="M0 64 C95 15 180 20 270 64 S440 108 540 64 S710 17 810 64 S980 106 1080 64 S1250 18 1350 64 S1510 106 1600 58"
                      fill="none" stroke="rgba(217,169,64,.24)" stroke-width="7"/>
                <path d="M0 64 C95 15 180 20 270 64 S440 108 540 64 S710 17 810 64 S980 106 1080 64 S1250 18 1350 64 S1510 106 1600 58"
                      fill="none" stroke="url(#journeyGold)" stroke-width="2.2" stroke-dasharray="3 5" filter="url(#journeyGlow)"/>
            </svg>

            <div class="loop-journey-grid">
                <article class="loop-journey-item">
                    <div class="loop-journey-icon">
                        <svg viewBox="0 0 64 64" aria-hidden="true">
                            <path d="M22 38c-5-4-8-9-8-15a18 18 0 0 1 36 0c0 6-3 11-8 15-3 2-4 5-4 8H26c0-3-1-6-4-8Z"/>
                            <path d="M26 52h12M28 46h8M32 3v-6M10 12l-5-5M54 12l5-5M8 29H1M63 29h-7"/>
                            <path d="m27 25 4 4 8-9"/>
                        </svg>
                    </div>
                    <h3>ایده</h3>
                    <p>همه چیز از یک ایده<br>برای ساده‌سازی شروع شد.</p>
                </article>

                <article class="loop-journey-item">
                    <div class="loop-journey-icon">
                        <svg viewBox="0 0 64 64" aria-hidden="true">
                            <rect x="15" y="15" width="34" height="34" rx="2"/>
                            <rect x="24" y="24" width="16" height="16"/>
                            <path d="M22 7v8M32 7v8M42 7v8M22 49v8M32 49v8M42 49v8M7 22h8M7 32h8M7 42h8M49 22h8M49 32h8M49 42h8"/>
                        </svg>
                    </div>
                    <h3>فناوری</h3>
                    <p>توسعه زیرساخت‌های<br>هوشمند و پایدار</p>
                </article>

                <article class="loop-journey-item">
                    <div class="loop-journey-icon">
                        <svg viewBox="0 0 64 64" aria-hidden="true">
                            <path d="M8 54h48M14 54V23h14v31M28 54V10h17v44M45 54V31h9v23"/>
                            <path d="M19 30h4M19 38h4M19 46h4M34 18h5M34 27h5M34 36h5M34 45h5M49 38h2M49 45h2"/>
                        </svg>
                    </div>
                    <h3>راهکارهای سازمانی</h3>
                    <p>راهکارهای اختصاصی<br>برای کسب‌وکارها</p>
                </article>

                <article class="loop-journey-item">
                    <div class="loop-journey-icon">
                        <svg viewBox="0 0 64 64" aria-hidden="true">
                            <rect x="20" y="7" width="24" height="50" rx="4"/>
                            <path d="M27 12h10M29 51h6"/>
                        </svg>
                    </div>
                    <h3>اپلیکیشن‌های هوشمند</h3>
                    <p>تجربه‌ای ساده و یکپارچه<br>در دستان شما</p>
                </article>

                <article class="loop-journey-item">
                    <div class="loop-journey-icon">
                        <svg viewBox="0 0 64 64" aria-hidden="true">
                            <path d="M39 11a13 13 0 0 0-15 16L9 42l13 13 15-15a13 13 0 0 0 16-15l-9 9-9-9 9-9-5-5Z"/>
                            <path d="m9 42 13 13M18 46l-5 5"/>
                        </svg>
                    </div>
                    <h3>خدمات تخصصی</h3>
                    <p>پشتیبانی، آموزش و<br>اجرای حرفه‌ای</p>
                </article>

                <article class="loop-journey-item">
                    <div class="loop-journey-icon">
                        <svg viewBox="0 0 64 64" aria-hidden="true">
                            <path d="M14 22c8-12 18 1 18 10S42 54 50 42c8-12-2-22-10-14-4 4-5 13-8 18-5 9-14 13-20 5-7-9-2-21 7-22 8-1 13 8 17 14"/>
                        </svg>
                    </div>
                    <h3>اکوسیستم LOOP</h3>
                    <p>همه چیز در یک حلقه<br>بی‌نقص به هم متصل است.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="about-pillars">
        <div class="about-pillars-grid">
            <article class="about-pillar">
                <svg class="about-pillar-icon" viewBox="0 0 80 64" aria-hidden="true">
                    <path d="M5 32C15 14 27 8 40 8s25 6 35 24C65 50 53 56 40 56S15 50 5 32Z"/>
                    <circle cx="40" cy="32" r="12"/>
                    <circle cx="40" cy="32" r="3"/>
                </svg>
                <h2>چشم‌انداز ما</h2>
                <h3 dir="ltr">VISION</h3>
                <p>تبدیل شدن به پیشروترین اکوسیستم یکپارچه<br>در ارائه خدمات فناوری، آموزش و پشتیبانی</p>
            </article>

            <article class="about-pillar">
                <svg class="about-pillar-icon" viewBox="0 0 72 72" aria-hidden="true">
                    <circle cx="32" cy="38" r="25"/>
                    <circle cx="32" cy="38" r="16"/>
                    <circle cx="32" cy="38" r="7"/>
                    <path d="m32 38 24-24M48 14l9-1-1 9M53 9l9-1-1 9"/>
                </svg>
                <h2>ماموریت ما</h2>
                <h3 dir="ltr">MISSION</h3>
                <p>ارائه راهکارهای نوآورانه و خدماتی با کیفیت<br>که زندگی دیجیتال کاربران و بهره‌وری سازمان‌ها<br>را متحول کند.</p>
            </article>

            <article class="about-pillar">
                <svg class="about-pillar-icon" viewBox="0 0 72 72" aria-hidden="true">
                    <path d="M10 22h52L36 62 10 22Z"/>
                    <path d="m10 22 12-14h28l12 14M22 8l-5 14 19 40M50 8l5 14-19 40M17 22h38M28 8l-5 14M44 8l5 14"/>
                </svg>
                <h2>ارزش‌های ما</h2>
                <h3 dir="ltr">VALUES</h3>
                <ul>
                    <li>اعتماد</li>
                    <li>نوآوری</li>
                    <li>تعهد</li>
                    <li>کیفیت</li>
                    <li>مسئولیت‌پذیری</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="about-ecosystem">
        <div class="about-ecosystem-inner">
            <h2>اکوسیستم <span dir="ltr">LOOP</span></h2>

            <svg class="about-ecosystem-lines" viewBox="0 0 1400 560" preserveAspectRatio="none" aria-hidden="true">
                <g fill="none" stroke="#e0aa35" stroke-width="3" stroke-dasharray="5 7">
                    <path d="M700 280 310 90"/>
                    <path d="M700 280 275 280"/>
                    <path d="M700 280 330 475"/>
                    <path d="M700 280 1090 90"/>
                    <path d="M700 280 1125 280"/>
                    <path d="M700 280 1070 475"/>
                </g>
            </svg>

            <div class="about-ecosystem-center">
                <img src="{{ asset('assets/new-style/center.png') }}" alt="LOOP">
            </div>

            <article class="about-eco-node node-web">
                <div class="about-eco-icon">
                    <svg viewBox="0 0 64 64"><rect x="8" y="13" width="48" height="38" rx="3"/><path d="M8 22h48"/><circle cx="32" cy="36" r="10"/><path d="M22 36h20M32 26c4 5 4 15 0 20M32 26c-4 5-4 15 0 20"/></svg>
                </div>
                <div><h3>وب‌سایت</h3><p>درگاه اصلی ارتباط<br>با کاربران!</p></div>
            </article>

            <article class="about-eco-node node-app">
                <div class="about-eco-icon">
                    <svg viewBox="0 0 64 64"><rect x="21" y="7" width="22" height="50" rx="4"/><path d="M27 12h10"/><circle cx="32" cy="51" r="2"/></svg>
                </div>
                <div><h3>اپلیکیشن</h3><p>تجربه‌ای سریع، امن<br>و همیشه در دسترس</p></div>
            </article>

            <article class="about-eco-node node-users">
                <div class="about-eco-icon">
                    <svg viewBox="0 0 64 64"><circle cx="32" cy="22" r="10"/><path d="M13 54c2-12 9-18 19-18s17 6 19 18M28 19l4 4 7-8"/></svg>
                </div>
                <div><h3>کاربران</h3><p>هسته اصلی<br>این اکوسیستم</p></div>
            </article>

            <article class="about-eco-node node-service">
                <div class="about-eco-icon">
                    <svg viewBox="0 0 64 64"><path d="M16 38V27a16 16 0 0 1 32 0v11"/><rect x="10" y="31" width="8" height="15" rx="3"/><rect x="46" y="31" width="8" height="15" rx="3"/><path d="M46 45c-2 7-7 10-14 10"/><circle cx="29" cy="55" r="3"/></svg>
                </div>
                <div><h3>مرکز خدمات</h3><p>پشتیبانی تخصصی<br>و پاسخ‌گویی سریع</p></div>
            </article>

            <article class="about-eco-node node-tech">
                <div class="about-eco-icon">
                    <svg viewBox="0 0 64 64"><path d="m14 48 18-18M24 12l8 8-8 8-8-8 8-8ZM40 36l10 10M45 8a12 12 0 0 0-9 17l-7 7 7 7 7-7a12 12 0 0 0 13-15l-8 8-8-8 8-8-3-1Z"/></svg>
                </div>
                <div><h3>تکنسین‌ها</h3><p>شبکه‌ای از متخصصان<br>آماده خدمت‌رسانی</p></div>
            </article>

            <article class="about-eco-node node-org">
                <div class="about-eco-icon">
                    <svg viewBox="0 0 64 64"><path d="M8 55h48M14 55V25h14v30M28 55V10h18v45M46 55V31h9v24"/><path d="M19 32h4M19 40h4M19 48h4M34 18h6M34 27h6M34 36h6M34 45h6M50 38h2M50 46h2"/></svg>
                </div>
                <div><h3>سازمان‌ها</h3><p>راهکارهای سازمانی<br>متناسب با نیاز شما</p></div>
            </article>
        </div>
    </section>

    <section class="about-behind">
        <div class="about-behind-inner">
            <h2>پشت صحنه <span dir="ltr">LOOP</span></h2>
            <div class="about-behind-grid">
                <article>
                    <img src="{{ asset('assets/new-style/aboutsec.jpeg') }}" alt="نوآوری و طراحی در لوپ" loading="lazy">
                    <h3>نوآوری و طراحی</h3>
                    <p>ایده‌هایی که به راهکار تبدیل می‌شوند</p>
                </article>
                <article>
                    <img src="{{ asset('assets/new-style/academysec.jpeg') }}" alt="آموزش و توسعه در لوپ" loading="lazy">
                    <h3>آموزش و توسعه</h3>
                    <p>سرمایه‌گذاری روی دانش، سرمایه‌گذاری روی آینده</p>
                </article>
                <article>
                    <img src="{{ asset('assets/new-style/service02.jpg') }}" alt="خدمات تخصصی لوپ" loading="lazy">
                    <h3>خدمات تخصصی</h3>
                    <p>تجهیزات پیشرفته، استانداردهای جهانی</p>
                </article>
            </div>
        </div>
    </section>

    <section class="about-stats">
        <div class="about-stats-inner">
            <article>
                <strong dir="ltr">۲۵۰+</strong>
                <span>پروژه موفق</span>
            </article>
            <article>
                <strong dir="ltr">۴۰+</strong>
                <span>متخصص و کارشناس</span>
            </article>
            <article>
                <strong dir="ltr">۹۸٪</strong>
                <span>رضایت مشتریان</span>
            </article>
        </div>
    </section>

    <section class="about-belief pt-0">
        <div class="about-belief-panel">
            <p class="fa" style="font-family: Vazirmatn FD, Muli, sans-serif;">در LOOP، فناوری زمانی ارزشمند است <br> که انسان‌ها، کسب‌وکارها و خدمات را در یک تجربه یکپارچه به هم متصل کند.</p>
            <p class="en" lang="en" dir="ltr">LOOP believes technology is valuable only when it brings<br>people, businesses, and services together in one seamless experience.</p>
        </div>
    </section>

    <section class="about-final-cta">
        <div class="about-final-cta-inner">
            <h2>به اکوسیستم <span dir="ltr">LOOP</span> خوش آمدید.</h2>
            <p>آماده‌اید تجربه‌ای متفاوت را آغاز کنید؟</p>
            <a href="{{ route('web.contact') }}" class="about-final-button">
                شروع همکاری
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            </a>
        </div>
    </section>



     @foreach($abouts as $about)
    <div class="about_area pt-85 pb-70" @if($loop->iteration%2 == 0) dir="ltr" @endif>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-4">
                    <div class="about_thumb">
                        <img src="{{ asset('storage/'.$about->image_path) }}" alt="{{ $about->title }}" style="height:100%; width:100%; object-fit:cover;" >
                    </div>
                </div>
                <div class="col-lg-8 col-md-8 col-sm-12 col-xs-8">
                    <div class="section_title text_left mb-40 mt-3">
                        
                        <div class="section_main_title">
                            <h1>{{ $about->title }}</h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                        <div class=" pt-5">
                             {!! $about->des !!}
                        </div>
                    </div>
                
                </div>

            </div>
        </div>
    </div>
    @endforeach
    <!--==================================================-->
    <!----- End Techno About Area ----->
    <!--==================================================-->

   

    <!--==================================================-->
    <!----- Start Techno Accordion Area ----->
    <!--==================================================-->
  
    <!--==================================================-->
    <!----- End Techno Accordion Area ----->
    <!--==================================================-->

   
     <!--==================================================-->
    <!----- Start Techno Flipbox Top Feature Area ----->
    <!--==================================================-->

    <style>
        .about-page-hero,
        .about-page-hero img {
            display: block;
            width: 100%;
        }

        .about-page-hero img {
            height: auto;
        }

        .about-connection {
            width: 100%;
            padding: 110px 0;
            background: #fff;
        }

        .about-connection-panel {
            width: 100%;
            background: #fff;
            color: #050505;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .about-connection-panel h1 {
            margin: 0 0 26px;
            color: #050505;
            font-size: clamp(36px, 4.2vw, 30px);
            font-weight: 900;
            line-height: 1.5;
        }

        .about-connection-panel p {
            margin: 0;
            color: #e5a400;
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(21px, 2.45vw, 25px);
            font-weight: 400;
            line-height: 1.35;
        }

        .about-connection-mark {
            position: relative;
            width: 160px;
            height: 14px;
            margin-top: 5em;
        }

        .about-connection-mark::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            transform: translateY(-50%);
            background: linear-gradient(90deg, transparent, #d99700 22%, #f2b100 50%, #d99700 78%, transparent);
        }

        .about-connection-mark span {
            position: absolute;
            z-index: 1;
            top: 50%;
            left: 50%;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            background: #e39b00;
            box-shadow: 0 0 8px rgba(227, 155, 0, .35);
        }

        .about-contact-ways {
            width: 100%;
            padding: 48px 4.5% 58px;
            background: #fff;
            color: #111827;
        }

        .about-contact-inner {
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
        }

        .about-contact-heading {
            margin-bottom: 28px;
            text-align: center;
        }

        .about-contact-heading h2 {
            margin: 0;
            color: #111827;
            font-size: clamp(27px, 2.2vw, 38px);
            font-weight: 800;
        }

        .about-contact-title-mark {
            position: relative;
            width: 150px;
            height: 28px;
            margin: 5px auto 0;
            color: #d99b20;
            font-size: 25px;
            line-height: 28px;
        }

        .about-contact-title-mark::before,
        .about-contact-title-mark::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 48px;
            height: 1px;
            background: linear-gradient(90deg, transparent, #d99b20);
        }

        .about-contact-title-mark::before {
            left: 0;
        }

        .about-contact-title-mark::after {
            right: 0;
            transform: rotate(180deg);
        }

        .about-social-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 16px;
        }

        .about-social-card {
            min-width: 0;
            min-height: 220px;
            padding: 28px 14px 24px;
            border: 1px solid #edf0f4;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 9px;
            color: #111827;
            text-align: center;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .about-social-card:hover {
            color: #111827;
            transform: translateY(-4px);
            box-shadow: 0 13px 28px rgba(15, 23, 42, .12);
        }

        .about-social-card img {
            width: 76px;
            height: 76px;
            margin-bottom: 5px;
            object-fit: contain;
        }

        .about-social-card strong {
            font-size: 18px;
            font-weight: 800;
        }

        .about-social-card span {
            color: #4b5563;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }

        .about-direct-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            margin-top: 30px;
        }

        .about-direct-card {
            min-height: 145px;
            padding: 25px 9%;
            border: 1px solid #edf0f4;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 28px;
            color: #111827;
        }

        .about-direct-card:hover {
            color: #111827;
        }

        .about-direct-card svg {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.6;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .about-direct-card > span {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .about-direct-card strong {
            font-size: 18px;
            font-weight: 800;
        }

        .about-direct-card small {
            color: #374151;
            font-family: Arial, sans-serif;
            font-size: 17px;
        }

        .loop-journey {
            position: relative;
            width: 100%;
            padding: 92px 24px 120px;
            overflow: hidden;
            background:
                radial-gradient(circle at 50% 38%, rgba(23, 55, 91, .2), transparent 42%),
                linear-gradient(180deg, #020a16 0%, #061326 52%, #020a16 100%);
            color: #fff;
        }

        .loop-journey-inner {
            position: relative;
            width: 100%;
            max-width: 1740px;
            margin: 0 auto;
        }

        .loop-journey h2 {
            position: relative;
            z-index: 3;
            margin: 0 0 58px;
            color: #fff;
            font-size: clamp(34px, 3.3vw, 45px);
            font-weight: 800;
            text-align: center;
        }

        .loop-journey h2 span {
            margin-right: 8px;
            color: #e9b75b;
            font-family: Arial, sans-serif;
            font-weight: 500;
        }

        .loop-journey-path {
            position: absolute;
            z-index: 0;
            top: 125px;
            left: 0;
            width: 100%;
            height: 105px;
            overflow: visible;
            pointer-events: none;
        }

        .loop-journey-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 18px;
            direction: ltr;
        }

        .loop-journey-item {
            min-width: 0;
            direction: rtl;
            text-align: center;
        }

        .loop-journey-icon {
            width: clamp(100px, 8vw, 100px);
            height: clamp(100px, 8vw, 100px);
            margin: 0 auto 34px;
            border: 2px solid #e3ac39;
            border-radius: 50%;
            background:
                radial-gradient(circle at 50% 42%, rgba(27, 56, 87, .9), rgba(2, 10, 22, .98) 70%);
            box-shadow:
                0 0 10px rgba(227, 172, 57, .35),
                inset 0 0 22px rgba(0, 0, 0, .55);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loop-journey-icon svg {
            width: 58%;
            height: 58%;
            fill: none;
            stroke: #efbd58;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            filter: drop-shadow(0 0 4px rgba(239, 189, 88, .3));
        }

        .loop-journey-item h3 {
            min-height: 40px;
            margin: 0 0 13px;
            color: #fff;
            font-size: clamp(18px, 1.5vw, 20px);
            font-weight: 800;
            line-height: 1.5;
        }

        .loop-journey-item p {
            margin: 0;
            color: #e3e7ed;
            font-size: clamp(14px, 1.15vw, 15px);
            line-height: 2;
        }

        .about-pillars {
            width: 100%;
            padding: 105px 5%;
            background: #fff;
            color: #050505;
        }

        .about-pillars-grid {
            max-width: 1650px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            direction: rtl;
        }

        .about-pillar {
            min-height: 570px;
            padding: 0 6%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .about-pillar + .about-pillar {
            border-right: 1px solid #d9d9d9;
        }

        .about-pillar-icon {
            width: 104px;
            height: 104px;
            margin-bottom: 38px;
            fill: none;
            stroke: #d28a08;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .about-pillar h2 {
            margin: 0 0 11px;
            color: #050505;
            font-size: clamp(26px, 3vw, 26px);
            font-weight: 900;
        }

        .about-pillar h3 {
            margin: 0 0 48px;
            color: #d28a08;
            font-family: Arial, sans-serif;
            font-size: clamp(25px, 2.2vw, 25px);
            font-weight: 400;
        }

        .about-pillar p,
        .about-pillar li {
            color: #111;
            font-size: clamp(17px, 1.6vw, 20px);
            font-weight: 500;
            line-height: 2.15;
        }

        .about-pillar ul {
            margin: -8px 0 0;
            padding: 0;
            list-style: none;
        }

        .about-ecosystem {
            width: 100%;
            padding: 70px 4% 100px;
            overflow: hidden;
            background:
                radial-gradient(circle at 50% 48%, rgba(26, 61, 99, .22), transparent 42%),
                linear-gradient(180deg, #020a16, #061326 52%, #020a16);
            color: #fff;
        }

        .about-ecosystem-inner {
            position: relative;
            width: 100%;
            max-width: 1550px;
            height: 680px;
            margin: 0 auto;
        }

        .about-ecosystem h2 {
            position: relative;
            z-index: 5;
            margin: 0;
            color: #fff;
            font-size: clamp(37px, 3.5vw, 58px);
            font-weight: 800;
            text-align: center;
        }

        .about-ecosystem h2 span {
            margin-right: 8px;
            color: #e5ad42;
            font-family: Arial, sans-serif;
            font-weight: 500;
        }

        .about-ecosystem-lines {
            position: absolute;
            z-index: 0;
            inset: 74px 0 0;
            width: 100%;
            height: 560px;
        }

        .about-ecosystem-center {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: 50%;
            width: 310px;
            height: 310px;
            transform: translate(-50%, -43%);
            border: 2px solid #dca830;
            border-radius: 50%;
            background: radial-gradient(circle at 45% 38%, #132a46, #030b18 72%);
            box-shadow: 0 0 35px rgba(220, 168, 48, .23), inset 0 0 28px rgba(0,0,0,.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-ecosystem-center img {
            width: 78%;
            height: auto;
        }

        .about-eco-node {
            position: absolute;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 24px;
            color: #fff;
        }

        .about-eco-icon {
            width: 126px;
            height: 126px;
            flex: 0 0 126px;
            border: 2px solid #dca830;
            border-radius: 50%;
            background: radial-gradient(circle, #132a46, #030b18 72%);
            box-shadow: 0 0 18px rgba(220, 168, 48, .18);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-eco-icon svg {
            width: 58%;
            height: 58%;
            fill: none;
            stroke: #edbd58;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .about-eco-node h3 {
            margin: 0 0 10px;
            color: #fff;
            font-size: 25px;
            font-weight: 900;
        }

        .about-eco-node p {
            margin: 0;
            color: #eef1f5;
            font-size: 18px;
            line-height: 1.9;
        }

        .node-web{top:120px; left:4%;}
        .node-app{top:310px; left:2%;}
        .node-users{top:500px; left:7%;}
        .node-service{top:120px; right:3%; flex-direction:row-reverse;}
        .node-tech{top:310px; right:1%; flex-direction:row-reverse;}
        .node-org{top:500px; right:6%; flex-direction:row-reverse;}

        .about-behind {
            width: 100%;
            padding: 52px 3.5% 70px;
            background: #fff;
            color: #080808;
        }

        .about-behind-inner {
            max-width: 1600px;
            margin: 0 auto;
        }

        .about-behind h2 {
            margin: 0 0 30px;
            color: #080808;
            font-size: clamp(28px, 2.7vw, 45px);
            font-weight: 900;
            text-align: center;
        }

        .about-behind-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .about-behind-grid article {
            text-align: center;
        }

        .about-behind-grid img {
            width: 100%;
            height: 280px;
            border-radius: 14px;
            object-fit: cover;
            display: block;
        }

        .about-behind-grid h3 {
            margin: 18px 0 7px;
            color: #080808;
            font-size: 23px;
            font-weight: 900;
        }

        .about-behind-grid p {
            margin: 0;
            color: #333;
            font-size: 15px;
        }

        .about-stats {
            width: 100%;
            padding: 42px 1.5% 48px;
            /* background: #202020; */
        }

        .about-stats-inner {
            width: 100%;
            min-height: 190px;
            padding: 24px 8%;
            border-bottom: 2px solid rgba(255,255,255,.8);
            background:
                linear-gradient(rgba(3, 13, 25, .88), rgba(3, 13, 25, .88)),
                radial-gradient(circle at 50% 20%, rgba(218,166,57,.14), transparent 45%),
                repeating-linear-gradient(15deg, rgba(255,255,255,.025) 0 1px, transparent 1px 16px);
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: center;
        }

        .about-stats article {
            min-height: 110px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-align: center;
        }

        .about-stats article + article {
            border-left: 1px solid rgba(218,166,57,.35);
        }

        .about-stats strong {
            color: #e6ae49;
            font-size: clamp(35px, 3.5vw, 57px);
            font-weight: 500;
            line-height: 1;
        }

        .about-stats span {
            color: #fff;
            font-size: clamp(14px, 1.35vw, 21px);
            font-weight: 700;
        }

        .about-belief {
            width: 100%;
            padding: 88px 0;
            /* background: #202020; */
        }

        .about-belief-panel {
            width: 100%;
            min-height: 280px;
            padding: 56px 28px;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .about-belief-panel .fa {
            margin: 0 0 23px;
            color: #111;
            font-size: clamp(23px, 2.5vw, 39px);
            font-weight: 600;
            line-height: 1.8;
        }

        .about-belief-panel .en {
            margin: 0;
            color: #c78b22;
            font-family: Arial, sans-serif;
            font-size: clamp(13px, 1.25vw, 19px);
            line-height: 1.55;
        }

        .about-final-cta {
            position: relative;
            width: 100%;
            padding: 35px 20px 42px;
            overflow: hidden;
            background:
                radial-gradient(circle at 50% 50%, rgba(33,70,111,.3), transparent 46%),
                linear-gradient(110deg, #020914, #071629 50%, #020914);
            color: #fff;
        }

        .about-final-cta::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: .16;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 42px 42px;
        }

        .about-final-cta-inner {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .about-final-cta h2 {
            margin: 0 0 13px;
            color: #fff;
            font-size: clamp(25px, 2.5vw, 40px);
            font-weight: 700;
        }

        .about-final-cta p {
            margin: 0 0 22px;
            color: #d8dde5;
            font-size: clamp(14px, 1.3vw, 19px);
        }

        .about-final-button {
            min-width: 200px;
            min-height: 54px;
            padding: 13px 25px;
            border: 1px solid #efc56d;
            border-radius: 7px;
            background: linear-gradient(135deg, #f0c66e, #d99b31);
            box-shadow: 0 8px 20px rgba(0,0,0,.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            color: #101318;
            font-size: 16px;
            font-weight: 800;
        }

        .about-final-button:hover {
            color: #101318;
            filter: brightness(1.06);
        }

        .about-final-button svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @media (max-width: 767px) {
            .about-connection {
                padding: 48px 0;
            }

            .about-connection-panel {
                min-height: 390px;
                padding: 52px 22px;
            }

            .about-connection-panel h1 {
                margin-bottom: 20px;
                font-size: clamp(27px, 8vw, 38px);
                line-height: 1.65;
            }

            .about-connection-panel p {
                max-width: 290px;
                font-size: clamp(17px, 5vw, 22px);
            }

            .about-connection-mark {
                width: 120px;
                margin-top: 48px;
            }

            .about-contact-ways {
                padding: 42px 16px 48px;
            }

            .about-contact-heading {
                margin-bottom: 24px;
            }

            .about-contact-heading h2 {
                font-size: 27px;
            }

            .about-social-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .about-social-card {
                min-height: 180px;
                padding: 22px 10px;
            }

            .about-social-card:last-child {
                grid-column: 1 / -1;
                width: calc(50% - 6px);
                justify-self: center;
            }

            .about-social-card img {
                width: 62px;
                height: 62px;
            }

            .about-social-card strong {
                font-size: 16px;
            }

            .about-social-card span {
                font-size: 14px;
            }

            .about-direct-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-top: 22px;
            }

            .about-direct-card {
                min-height: 110px;
                padding: 20px 24px;
                justify-content: flex-start;
                gap: 22px;
            }

            .about-direct-card svg {
                width: 44px;
                height: 44px;
                flex-basis: 44px;
            }

            .about-direct-card strong {
                font-size: 16px;
            }

            .about-direct-card small {
                font-size: 15px;
            }

            .loop-journey {
                padding: 58px 16px 70px;
            }

            .loop-journey h2 {
                margin-bottom: 38px;
                font-size: 32px;
            }

            .loop-journey-path {
                display: none;
            }

            .loop-journey-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 34px 12px;
            }

            .loop-journey-icon {
                width: 100px;
                height: 100px;
                margin-bottom: 18px;
            }

            .loop-journey-item h3 {
                min-height: 0;
                margin-bottom: 8px;
                font-size: 17px;
            }

            .loop-journey-item p {
                font-size: 13px;
                line-height: 1.8;
            }

            .about-pillars {
                padding: 60px 18px;
            }

            .about-pillars-grid {
                grid-template-columns: 1fr;
            }

            .about-pillar {
                min-height: 0;
                padding: 48px 12px;
            }

            .about-pillar + .about-pillar {
                border-top: 1px solid #ddd;
                border-right: 0;
            }

            .about-pillar-icon {
                width: 84px;
                height: 84px;
                margin-bottom: 24px;
            }

            .about-pillar h2 {
                font-size: 34px;
            }

            .about-pillar h3 {
                margin-bottom: 28px;
                font-size: 25px;
            }

            .about-pillar p,
            .about-pillar li {
                font-size: 17px;
            }

            .about-ecosystem {
                padding: 54px 12px 70px;
            }

            .about-ecosystem-inner {
                height: auto;
            }

            .about-ecosystem h2 {
                margin-bottom: 42px;
                font-size: 35px;
            }

            .about-ecosystem-lines {
                display: none;
            }

            .about-ecosystem-center {
                position: relative;
                top: auto;
                left: auto;
                width: 190px;
                height: 190px;
                margin: 0 auto 42px;
                transform: none;
            }

            .about-eco-node,
            .node-web,.node-app,.node-users,
            .node-service,.node-tech,.node-org {
                position: relative;
                inset: auto;
                width: 100%;
                margin-bottom: 14px;
                padding: 14px 18px;
                border: 1px solid rgba(220,168,48,.25);
                border-radius: 18px;
                background: rgba(255,255,255,.025);
                flex-direction: row;
                gap: 18px;
            }

            .about-eco-icon {
                width: 82px;
                height: 82px;
                flex-basis: 82px;
            }

            .about-eco-node h3 {
                font-size: 20px;
            }

            .about-eco-node p {
                font-size: 14px;
            }

            .about-behind {
                padding: 48px 16px 60px;
            }

            .about-behind-grid {
                grid-template-columns: 1fr;
                gap: 34px;
            }

            .about-behind-grid img {
                height: 230px;
            }

            .about-behind-grid h3 {
                font-size: 21px;
            }

            .about-stats {
                padding: 28px 12px 34px;
            }

            .about-stats-inner {
                padding: 22px 12px;
                grid-template-columns: 1fr;
            }

            .about-stats article {
                min-height: 115px;
            }

            .about-stats article + article {
                border-top: 1px solid rgba(218,166,57,.3);
                border-left: 0;
            }

            .about-stats strong {
                font-size: 42px;
            }

            .about-stats span {
                font-size: 16px;
            }

            .about-belief {
                padding: 52px 0;
            }

            .about-belief-panel {
                min-height: 330px;
                padding: 45px 22px;
            }

            .about-belief-panel .fa {
                font-size: 22px;
            }

            .about-belief-panel .fa br,
            .about-belief-panel .en br {
                display: none;
            }

            .about-belief-panel .en {
                font-size: 13px;
            }

            .about-final-cta {
                padding: 48px 20px 54px;
            }

            .about-final-cta h2 {
                font-size: 27px;
                line-height: 1.6;
            }

            .about-final-cta p {
                font-size: 14px;
            }
        }
    </style>

@endsection
