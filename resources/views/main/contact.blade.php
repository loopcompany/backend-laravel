@extends('layout.main.header')
@section('content')
<!-- ================= HERO ================= -->
<section class="hero">
  <picture>
    <source media="(max-width: 767px)" srcset="{{ asset('assets/new-style/contact/hero-mobile.png') }}" width="864" height="1821">
    <img class="hero-image" src="{{ asset('assets/new-style/contact/hero-desktop.png') }}" alt="LOOP — هر راهی، به ارتباط ختم می‌شود" width="1717" height="916" fetchpriority="high">
  </picture>
</section>

<!-- ================= CONNECT METHODS ================= -->
{{-- @php
  $contactSocialNetworks = [
    ['icon'=>'Tele.png', 'name' => 'تلگرام', 'handle' => '@clpiran'],
    ['icon'=>'x.png', 'name' => 'X', 'handle' => '@clpiran'],
    ['icon'=>'whatsapp2.png', 'name' => 'واتساپ', 'handle' => '@clpiran'],
    ['icon'=>'rubika-new.png', 'name' => 'روبیکا', 'handle' => '@clpiran'],
    ['icon'=>'instagram2.png', 'name' => 'اینستاگرام', 'handle' => '@clpiran'],
    ['icon'=>'bale.png', 'name' => 'بله', 'handle' => '@clpiran'],
    ['icon'=>'eitaa.png', 'name' => 'ایتا', 'handle' => '@clpiran'],
  ];
  $emails = $contacts->where('type', 'email');
  $phones = $contacts->where('type', 'phone');
@endphp
<section id="connect" class="about-contact-ways">
  <div class="about-contact-inner">
    <div class="about-contact-heading">
      <h2>راه‌های ارتباط با <span class="en">LOOP</span></h2>
      <div class="about-contact-title-mark" aria-hidden="true"><span>∞</span></div>
    </div>

    <div class="about-social-grid">
      @foreach ($contactSocialNetworks as $social)
        <a href="#" class="about-social-card" aria-label="{{ $social['name'] }} لوپ">
          <img src="{{ asset('assets/new-style/social/'.$social['icon']) }}" alt="">
          <strong>{{ $social['name'] }}</strong>
          <span class="en">{{ $social['handle'] }}</span>
        </a>
      @endforeach
    </div>

    <div class="about-direct-grid">
      <a href="https://clpiran.com" class="about-direct-card" target="_blank" rel="noopener noreferrer">
        <div class="direct-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.4 4.5 6.4 4.5 9S15 17.6 12 21M12 3c-3 3.4-4.5 6.4-4.5 9S9 17.6 12 21"/></svg></div>
        <span><strong>وب‌سایت</strong><small class="en">clpiran.com</small></span>
      </a>
      <a href="mailto:{{ $emails->first()->name ?? 'info@clpiran.com' }}" class="about-direct-card">
        <div class="direct-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></div>
        <span><strong>ایمیل سازمانی</strong><small class="en">{{ $emails->first()->name ?? 'info@clpiran.com' }}</small></span>
      </a>
      <a href="tel:{{ $phones->first()->name ?? '91693909' }}" class="about-direct-card">
        <div class="direct-icon"><svg viewBox="0 0 24 24"><path d="M7.5 3.5 10 8 7.8 9.8c1.3 2.8 3.6 5.1 6.4 6.4L16 14l4.5 2.5-.5 3.2c-.2 1-1.1 1.8-2.2 1.8C9.4 21.5 2.5 14.6 2.5 6.2c0-1.1.8-2 1.8-2.2l3.2-.5Z"/></svg></div>
        <span><strong>تماس تلفنی</strong><small class="en">{{ $phones->first()->name ?? '91693909' }}</small></span>
      </a>
    </div>
  </div>
</section> --}}


@php
    $defaultSocialNetworks = collect([
        ['icon' => asset('assets/new-style/social/Tele.png'), 'name' => 'تلگرام', 'handle' => '@clpiran', 'link' => '#'],
        ['icon' => asset('assets/new-style/social/x.png'), 'name' => 'X', 'handle' => '@clpiran', 'link' => '#'],
        ['icon' => asset('assets/new-style/social/whatsapp2.png'), 'name' => 'واتساپ', 'handle' => '@clpiran', 'link' => '#'],
        ['icon' => asset('assets/new-style/social/rubika-new.png'), 'name' => 'روبیکا', 'handle' => '@clpiran', 'link' => '#'],
        ['icon' => asset('assets/new-style/social/instagram2.png'), 'name' => 'اینستاگرام', 'handle' => '@clpiran', 'link' => '#'],
        ['icon' => asset('assets/new-style/social/bale.png'), 'name' => 'بله', 'handle' => '@clpiran', 'link' => '#'],
        ['icon' => asset('assets/new-style/social/eitaa.png'), 'name' => 'ایتا', 'handle' => '@clpiran', 'link' => '#'],
    ]);

    $contactSocialNetworks =
        isset($globalSocials) && $globalSocials->isNotEmpty()
            ? $globalSocials->map(fn ($social) => [
                'icon' => $social->icon
                    ? asset('storage/' . $social->icon)
                    : null,
                'name' => $social->title ?? 'شبکه اجتماعی',
                'handle' => data_get($social, 'name')
                    ?: ($social->title ?? ''),
                'link' => $social->link ?: '#',
            ])
            : $defaultSocialNetworks;

    $emails = $contacts->where('type', 'email');
    $phones = $contacts->where('type', 'phone');
@endphp

<section id="connect" class="about-contact-ways">
    <div class="about-contact-inner">
        <div class="about-contact-heading">
            <h2>راه‌های ارتباط با <span class="en">LOOP</span></h2>

            <div class="about-contact-title-mark" aria-hidden="true">
                <span>∞</span>
            </div>
        </div>

        <div class="about-social-grid">
            @foreach ($contactSocialNetworks as $social)
                <a
                    href="{{ $social['link'] }}"
                    class="about-social-card"
                    aria-label="{{ $social['name'] }} لوپ"
                    @if ($social['link'] !== '#')
                        target="_blank"
                        rel="noopener noreferrer"
                    @endif
                >
                    @if ($social['icon'])
                        <img
                            src="{{ $social['icon'] }}"
                            alt="{{ $social['name'] }}"
                        >
                    @else
                        <i class="fa fa-link" aria-hidden="true"></i>
                    @endif

                    <strong>{{ $social['name'] }}</strong>

                    @if (!empty($social['handle']))
                        <span class="en">{{ $social['handle'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="about-direct-grid">
          <a href="https://clpiran.com" class="about-direct-card" target="_blank" rel="noopener noreferrer">
            <div class="direct-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.4 4.5 6.4 4.5 9S15 17.6 12 21M12 3c-3 3.4-4.5 6.4-4.5 9S9 17.6 12 21"/></svg></div>
            <span><strong>وب‌سایت</strong><small class="en">clpiran.com</small></span>
          </a>
          <a href="mailto:{{ $emails->first()->name ?? 'info@clpiran.com' }}" class="about-direct-card">
            <div class="direct-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></div>
            <span><strong>ایمیل سازمانی</strong><small class="en">{{ $emails->first()->name ?? 'info@clpiran.com' }}</small></span>
          </a>
          <a href="tel:{{ $phones->first()->name ?? '91693909' }}" class="about-direct-card">
            <div class="direct-icon"><svg viewBox="0 0 24 24"><path d="M7.5 3.5 10 8 7.8 9.8c1.3 2.8 3.6 5.1 6.4 6.4L16 14l4.5 2.5-.5 3.2c-.2 1-1.1 1.8-2.2 1.8C9.4 21.5 2.5 14.6 2.5 6.2c0-1.1.8-2 1.8-2.2l3.2-.5Z"/></svg></div>
            <span><strong>تماس تلفنی</strong><small class="en">{{ $phones->first()->name ?? '91693909' }}</small></span>
          </a>
        </div>
    </div>
</section>

<!-- ================= FORM + MAP ================= -->
<section id="reach">
  <div class="container">
    <div class="reach-grid">
      <div class="reach-form">
        <h2>پیام خود را برای ما ارسال کنید</h2>
        <p>کارشناسان ما در اولین فرصت پاسخگوی پیام شما خواهند بود.</p>

        <form id="contact_form" action="{{ route('submit.contact') }}" method="POST">
          @csrf
          <div class="field">
            <input type="text" name="name" value="{{ old('name') }}" placeholder="نام و نام خانوادگی" required>
            <svg class="fic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
            @error('name')<div class="text-danger mt-1"><small>{{ $message }}</small></div>@enderror
          </div>
          <div class="field">
            <input type="email" name="email" value="{{ old('email') }}" placeholder="ایمیل" required>
            <svg class="fic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            @error('email')<div class="text-danger mt-1"><small>{{ $message }}</small></div>@enderror
          </div>
          <div class="field">
            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="شماره تماس (مثال: 09123456789)" required style="direction:ltr; text-align:right;">
            <svg class="fic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
            @error('phone')<div class="text-danger mt-1"><small>{{ $message }}</small></div>@enderror
          </div>
          <div class="field">
            <select name="title" required>
              <option value="" disabled {{ old('title') ? '' : 'selected' }}>موضوع پیام را انتخاب کنید</option>
              <option value="درخواست مشاوره" {{ old('title')=='درخواست مشاوره' ? 'selected' : '' }}>درخواست مشاوره</option>
              <option value="گزارش مشکل فنی" {{ old('title')=='گزارش مشکل فنی' ? 'selected' : '' }}>گزارش مشکل فنی</option>
              <option value="همکاری با LOOP" {{ old('title')=='همکاری با LOOP' ? 'selected' : '' }}>همکاری با LOOP</option>
              <option value="سایر موارد" {{ old('title')=='سایر موارد' ? 'selected' : '' }}>سایر موارد</option>
            </select>
            <svg class="fic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            @error('title')<div class="text-danger mt-1"><small>{{ $message }}</small></div>@enderror
          </div>
          <div class="field">
            <textarea name="message" placeholder="متن پیام شما" required>{{ old('message') }}</textarea>
            <svg class="fic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            @error('message')<div class="text-danger mt-1"><small>{{ $message }}</small></div>@enderror
          </div>

          <button type="submit" class="btn solid">ارسال پیام
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
          </button>
        </form>

        @if(session('success'))
          <div class="alert alert-success mt-3"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger mt-3"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif
      </div>

      <div>
        @php $office = $contacts->where('type','office')->first(); @endphp
        <h2 style="font-size:clamp(22px,2.4vw,28px); margin-bottom:10px;">دفتر مرکزی <span class="en">LOOP</span></h2>
        <p style="color:var(--text-mid); font-size:14px; margin-bottom:24px;">{{ $office->name ?? 'تهران، شهرک ولیعصر، خیابان ولی محمدی، پلاک ۶۱، همکف' }}</p>
        <a href="">
          <img src="{{asset('assets/new-style/contact/map.png')}}" />
        </a>
        {{-- <div class="reach-map">
          <div class="map-grid"></div>
          <svg class="pin" width="40" height="40" viewBox="0 0 24 24" fill="none">
            <path d="M12 21s-7-5-9-10a5 5 0 0 1 9-4 5 5 0 0 1 9 4c-2 5-9 10-9 10Z" fill="url(#pinGrad)" stroke="#f9edc7" stroke-width="1"/>
            <circle cx="12" cy="11" r="2.4" fill="#0a1526"/>
            <defs><linearGradient id="pinGrad" x1="0" y1="0" x2="24" y2="24"><stop offset="0" stop-color="#f4d477"/><stop offset="1" stop-color="#c08f2b"/></linearGradient></defs>
          </svg>
          <div class="tooltip">
            {{ $office->name ?? 'تهران، شهرک ولیعصر، خیابان ولی محمدی، پلاک ۶۱، طبقه همکف' }}
          </div>
          <div class="map-btn-wrap">
            <a class="btn" href="https://maps.google.com/?q={{ urlencode($office->name ?? 'تهران، شهرک ولیعصر') }}" target="_blank" rel="noopener">مشاهده در نقشه
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5-9-10a5 5 0 0 1 9-4 5 5 0 0 1 9 4c-2 5-9 10-9 10Z"/><circle cx="12" cy="11" r="2"/></svg>
            </a>
          </div>
        </div> --}}
      </div>
    </div>
  </div>
</section>

<!-- ================= WHY US ================= -->
<section id="whyus">
  <div class="container">
    <div class="whyus-head">
      <h2>چرا ارتباط با <span class="en">LOOP</span> تجربه‌ای متفاوت است؟</h2>
      <div class="gold-rule rule-center"></div>
    </div>
    <div class="whyus-grid">
      <div class="whyus-card">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Zm-18 0a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3Z"/></svg></div>
        <h4>پاسخگویی سریع</h4>
        <p>تیم ما در کوتاه‌ترین زمان پاسخگوی شماست.</p>
      </div>
      <div class="whyus-card">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01Z"/></svg></div>
        <h4>راهکارهای تخصصی</h4>
        <p>راهکارهایی متناسب با نیاز کسب‌وکار شما ارائه می‌دهیم.</p>
      </div>
      <div class="whyus-card">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg></div>
        <h4>اطمینان و امنیت</h4>
        <p>اطلاعات شما نزد ما محفوظ و محرمانه می‌ماند.</p>
      </div>
      <div class="whyus-card">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg></div>
        <h4>همراهی واقعی</h4>
        <p>ما فقط به حرف شما گوش می‌دهیم، در کنارتان هستیم.</p>
      </div>
    </div>
  </div>
</section>

<!-- ================= PROCESS ================= -->
<section id="process">
  <div class="container">
    <div class="process-head">
      <h2>قرآیند پاسخگویی ما</h2>
      <div class="gold-rule rule-center"></div>
    </div>

    <div class="process-row">
      <div class="process-connector"></div>

      <div class="process-step">
        <div class="circles">
          <div class="ic-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></div>
          <div class="num-circle en">01</div>
        </div>
        <h5>ارسال پیام</h5>
        <p>شما پیام خود را برای ما ارسال می‌کنید.</p>
      </div>

      <div class="process-step">
        <div class="circles">
          <div class="ic-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg></div>
          <div class="num-circle en">02</div>
        </div>
        <h5>بررسی و تحلیل</h5>
        <p>تیم ما پیام شما را بررسی و تحلیل می‌کند.</p>
      </div>

      <div class="process-step">
        <div class="circles">
          <div class="ic-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg></div>
          <div class="num-circle en">03</div>
        </div>
        <h5>تماس و مشاوره</h5>
        <p>کارشناسی ما با شما تماس می‌گیرد.</p>
      </div>

      <div class="process-step">
        <div class="circles">
          <div class="ic-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg></div>
          <div class="num-circle en">04</div>
        </div>
        <h5>آغاز همکاری</h5>
        <p>بهترین راهکار را ارائه و همکاری را آغاز می‌کنیم.</p>
      </div>
    </div>
  </div>
</section>

<!-- ================= CTA COLLAB ================= -->
<section id="cta-collab">
  <div class="cta-collab">
   
    <div class="cta-collab-text">
      <h2>بیایید آینده را با هم بسازیم.</h2>
      <p>یک قدم تا شروع همکاری حرفه‌ای با <span class="en">LOOP</span> فاصله دارید.</p>
      <a href="{{ route('web.contact') }}#reach" class="btn dark" style="width: fit-content;">شروع همکاری
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
      </a>
    </div>
    <div class="cta-collab-media">
      <img src="{{ asset('assets/new-style/contact/06.png') }}" alt="LOOP">
    </div>

  </div>
</section>

<style>
  :root{
    --navy-950:#050b16;
    --navy-900:#0a1526;
    --navy-800:#0f2038;
    --navy-700:#16304f;
    --navy-600:#1d3f66;
    --gold-100:#f9edc7;
    --gold-300:#ecc873;
    --gold-500:#d9a940;
    --gold-600:#d5aa65;
    --gold-700:#96701f;
    --text-hi:#f4f0e6;
    --text-mid:#c4cbda;
    --text-lo:#7c8aa3;
    --cream:#f7f4ef;
    --ink:#161a22;
    --ink-soft:#5b6472;
    --line-gold: linear-gradient(90deg, transparent, var(--gold-500), transparent);
    --radius-lg:22px;
    --radius-md:14px;
    --ease: cubic-bezier(.16,.84,.32,1);
  }

  *{box-sizing:border-box; margin:0; padding:0;}
  html{scroll-behavior:smooth;}
  body{
    background:var(--navy-950);
    color:var(--text-hi);
    font-family:'Vazirmatn', sans-serif;
    overflow-x:hidden;
    line-height:1.7;
  }
  .en{font-family:'Space Grotesk', sans-serif; direction:ltr; unicode-bidi:isolate;}
  img{max-width:100%; display:block;}
  a{color:inherit; text-decoration:none;}
  .container{max-width:1280px; margin:0 auto; padding:0 32px;}

  .eyebrow{
    display:inline-flex; align-items:center; gap:10px;
    font-family:'Space Grotesk', sans-serif;
    font-size:12px; letter-spacing:.28em; text-transform:uppercase;
    color:var(--gold-500);
  }
  .gold-rule{width:64px; height:2px; background:linear-gradient(90deg, var(--gold-500), transparent);}
  .rule-center{margin-inline:auto;}
  h1,h2,h3{font-weight:700; letter-spacing:-.01em;}
  .gold-text{
    background:linear-gradient(100deg, var(--gold-300), var(--gold-500) 45%, var(--gold-100) 65%, var(--gold-500));
    -webkit-background-clip:text; background-clip:text; color:transparent;
  }
  .btn{
    display:inline-flex; align-items:center; gap:12px;
    padding:15px 28px; border-radius:999px;
    border:1px solid var(--gold-600);
    font-family:'Space Grotesk', sans-serif;
    font-weight:500; font-size:14px; letter-spacing:.02em;
    color:var(--gold-300);
    background:rgba(217,169,64,.05);
    transition:all .35s var(--ease);
    white-space:nowrap; cursor:pointer;
  }
  .btn svg{transition:transform .35s var(--ease);}
  .btn:hover{background:var(--gold-500); color:var(--navy-950); border-color:var(--gold-500);}
  .btn:hover svg{transform:translateX(4px);}
  .btn.solid{background:linear-gradient(135deg, var(--gold-300), var(--gold-600)); color:var(--navy-950); border:none;}
  .btn.solid:hover{filter:brightness(1.08); transform:translateY(-2px);}
  .btn.dark{background:var(--navy-950); color:var(--gold-300); border:1px solid var(--navy-800);}
  .btn.dark:hover{background:#000; color:var(--gold-300);}

  section{position:relative; padding:110px 0;}
  @media (max-width:900px){ section{padding:70px 0;} }

  header.top{
    position:fixed; top:0; left:0; right:0; z-index:40;
    padding:22px 0; backdrop-filter:blur(10px);
    background:linear-gradient(var(--navy-950), transparent);
  }
  .brand{
    display:flex; align-items:center; gap:10px;
    font-family:'Space Grotesk', sans-serif; font-weight:700;
    font-size:22px; letter-spacing:.02em; direction:ltr;
  }
  .brand svg{width:30px; height:30px;}

  /* ================= HERO ================= */
  .hero{padding:0; background:var(--navy-950);}
  .hero-image{width:100%; height:auto; display:block;}

  /* ================= CONNECT METHODS (light) ================= */
  #connect{background:var(--cream); padding:90px 0; color:var(--ink);}
  .connect-head{text-align:center; max-width:560px; margin:0 auto 44px;}
  .connect-head h2{color:var(--ink); font-size:clamp(24px,2.8vw,34px); margin:14px 0 0;}
  .connect-head .gold-rule{margin:18px auto 0;}

  .social-grid{display:grid; grid-template-columns:repeat(7,1fr); gap:14px; margin-bottom:14px;}
  .social-card{
    background:#fff; border-radius:16px; padding:24px 10px; text-align:center;
    box-shadow:0 6px 18px rgba(20,24,31,.07); border:1px solid rgba(20,24,31,.05);
    transition:transform .3s var(--ease), box-shadow .3s var(--ease);
  }
  .social-card:hover{transform:translateY(-4px); box-shadow:0 14px 30px rgba(20,24,31,.12);}
  .social-card img{width:42px; height:42px; margin:0 auto 12px; border-radius:12px; object-fit:cover;}
  .social-card h4{font-size:13.5px; font-weight:700; color:var(--ink); margin-bottom:4px;}
  .social-card span{font-size:11.5px; color:var(--ink-soft); direction:ltr; display:inline-block;}

  .method-row{display:grid; grid-template-columns:repeat(3,1fr); gap:14px;}
  .method-card{
    background:#fff; border-radius:16px; padding:20px 24px; display:flex; align-items:center; gap:14px;
    box-shadow:0 6px 18px rgba(20,24,31,.07); border:1px solid rgba(20,24,31,.05);
  }
  .method-card .ic{
    width:44px; height:44px; border-radius:50%; flex:none;
    display:flex; align-items:center; justify-content:center;
    background:rgba(217,169,64,.12); color:var(--gold-700);
  }
  .method-card .ic svg{width:20px; height:20px;}
  .method-card h5{font-size:13.5px; font-weight:700; color:var(--ink); margin-bottom:2px;}
  .method-card span{font-size:12.5px; color:var(--ink-soft); direction:ltr; display:inline-block;}

  @media (max-width:900px){ .social-grid{grid-template-columns:repeat(4,1fr);} .method-row{grid-template-columns:1fr;} }
  @media (max-width:520px){ .social-grid{grid-template-columns:repeat(2,1fr);} }

  .about-contact-ways{width:100%; padding:48px 4.5% 58px; background:#fff; color:#111827;}
  .about-contact-inner{width:100%; max-width:1600px; margin:0 auto;}
  .about-contact-heading{margin-bottom:28px; text-align:center;}
  .about-contact-heading h2{margin:0; color:#111827; font-size:clamp(27px,2.2vw,38px); font-weight:800;}
  .about-contact-title-mark{position:relative; width:150px; height:28px; margin:5px auto 0; color:#d99b20; font-size:25px; line-height:28px;}
  .about-contact-title-mark::before,.about-contact-title-mark::after{content:''; position:absolute; top:50%; width:48px; height:1px; background:linear-gradient(90deg,transparent,#d99b20);}
  .about-contact-title-mark::before{left:0;}
  .about-contact-title-mark::after{right:0; transform:rotate(180deg);}
  .about-social-grid{display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:16px;}
  .about-social-card{
    min-width:0; min-height:220px; padding:28px 14px 24px; border:1px solid #edf0f4;
    border-radius:14px; background:#fff; box-shadow:0 8px 20px rgba(15,23,42,.08);
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:9px;
    color:#111827; text-align:center; transition:transform .25s ease,box-shadow .25s ease;
  }
  .about-social-card:hover{color:#111827; transform:translateY(-4px); box-shadow:0 13px 28px rgba(15,23,42,.12);}
  .about-social-card img{width:76px; height:76px; margin-bottom:5px; object-fit:contain;}
  .about-social-card strong{font-size:18px; font-weight:800;}
  .about-social-card span{color:#4b5563; font-size:16px;}
  .about-direct-grid{display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:22px; margin-top:30px;}
  .about-direct-card{
    min-height:145px; padding:25px 9%; border:1px solid #edf0f4; border-radius:14px;
    background:#fff; box-shadow:0 8px 20px rgba(15,23,42,.08);
    display:flex; align-items:center; justify-content:center; gap:28px; color:#111827;
  }
  .about-direct-card:hover{color:#111827;}
  .about-direct-card .direct-icon{width:54px; height:54px; flex:0 0 54px;}
  .about-direct-card svg{width:100%; height:100%; fill:none; stroke:currentColor; stroke-width:1.6; stroke-linecap:round; stroke-linejoin:round;}
  .about-direct-card > span{display:flex; flex-direction:column; gap:6px;}
  .about-direct-card strong{font-size:18px; font-weight:800;}
  .about-direct-card small{color:#374151; font-size:17px;}
  @media (max-width:767px){
    .about-contact-ways{padding:42px 16px 48px;}
    .about-contact-heading{margin-bottom:24px;}
    .about-contact-heading h2{font-size:27px;}
    .about-social-grid{grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;}
    .about-social-card{min-height:180px; padding:22px 10px;}
    .about-social-card:last-child{grid-column:1/-1; width:calc(50% - 6px); justify-self:center;}
    .about-social-card img{width:62px; height:62px;}
    .about-social-card strong{font-size:16px;}
    .about-social-card span{font-size:14px;}
    .about-direct-grid{grid-template-columns:1fr; gap:12px; margin-top:22px;}
    .about-direct-card{min-height:110px; padding:20px 24px; justify-content:flex-start; gap:22px;}
    .about-direct-card .direct-icon{width:44px; height:44px; flex-basis:44px;}
    .about-direct-card strong{font-size:16px;}
    .about-direct-card small{font-size:15px;}
  }

  /* ================= FORM + MAP (dark) ================= */
  #reach{background:var(--navy-950);}
  .reach-grid{display:grid; grid-template-columns:1fr 1fr; gap:56px; align-items:start;}
  .reach-form h2{font-size:clamp(22px,2.4vw,28px); margin-bottom:10px;}
  .reach-form > p{color:var(--text-mid); font-size:14px; margin-bottom:28px;}

  .field{margin-bottom:16px; position:relative;}
  .field input, .field select, .field textarea{
    width:100%; background:rgba(255,255,255,.03); border:1px solid var(--navy-700); border-radius:12px;
    padding:16px 46px 16px 16px; color:var(--text-hi); font-family:inherit; font-size:14px;
    transition:border-color .3s var(--ease);
  }
  .field select{appearance:none; cursor:pointer;}
  .field select option{background:var(--navy-900); color:var(--text-hi);}
  .field textarea{min-height:120px; resize:vertical;}
  .field input:focus, .field select:focus, .field textarea:focus{outline:none; border-color:var(--gold-500);}
  .field input::placeholder, .field textarea::placeholder{color:var(--text-lo);}
  .field .fic{position:absolute; top:17px; right:16px; width:18px; height:18px; color:var(--gold-500); pointer-events:none;}
  .field textarea ~ .fic{top:17px;}

  .reach-map{
    position:relative; border-radius:22px; overflow:hidden; border:1px solid var(--navy-700);
    min-height:460px; background:
      radial-gradient(circle at 30% 15%, rgba(217,169,64,.1), transparent 55%),
      linear-gradient(160deg,#0d1c30,#060d1a);
  }
  .reach-map .map-grid{
    position:absolute; inset:-20%; opacity:.55;
    background-image:
      linear-gradient(rgba(217,169,64,.22) 1px, transparent 1px),
      linear-gradient(90deg, rgba(217,169,64,.22) 1px, transparent 1px);
    background-size:40px 40px;
    transform:rotate(-6deg);
  }
  .reach-map .pin{position:absolute; top:38%; left:50%; transform:translate(-50%,-100%); z-index:2; filter:drop-shadow(0 6px 14px rgba(217,169,64,.5));}
  .reach-map .tooltip{
    position:absolute; top:42%; left:50%; transform:translateX(-50%); z-index:2;
    background:var(--navy-900); border:1px solid var(--gold-600); border-radius:14px;
    padding:18px 24px; text-align:center; font-size:13.5px; line-height:2; color:var(--text-hi);
    min-width:230px; box-shadow:0 14px 30px rgba(0,0,0,.4);
  }
  .reach-map .map-btn-wrap{position:absolute; bottom:22px; left:50%; transform:translateX(-50%); z-index:2;}

  @media (max-width:900px){
    .reach-grid{grid-template-columns:1fr; gap:36px;}
    .reach-map{min-height:340px;}
  }

  /* ================= WHY US (light) ================= */
  #whyus{background:var(--cream); color:var(--ink);}
  .whyus-head{text-align:center; max-width:600px; margin:0 auto 44px;}
  .whyus-head h2{color:var(--ink); font-size:clamp(22px,2.6vw,30px); margin:0 0 16px;}
  .whyus-head .gold-rule{margin:0 auto;}
  .whyus-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:16px;}
  .whyus-card{background:#fff; border-radius:16px; padding:36px 22px; text-align:center; box-shadow:0 6px 18px rgba(20,24,31,.07); border:1px solid rgba(20,24,31,.05);}
  .whyus-card .ic{width:44px; height:44px; margin:0 auto 18px; color:var(--gold-600);}
  .whyus-card h4{color:var(--ink); font-size:15.5px; margin-bottom:8px;}
  .whyus-card p{color:var(--ink-soft); font-size:13px; line-height:1.85;}
  @media (max-width:900px){ .whyus-grid{grid-template-columns:repeat(2,1fr);} }
  @media (max-width:560px){ .whyus-grid{grid-template-columns:1fr;} }

  /* ================= PROCESS (dark) ================= */
  #process{background:var(--navy-950);}
  .process-head{text-align:center; max-width:520px; margin:0 auto 60px;}
  .process-head h2{font-size:clamp(22px,2.6vw,30px); margin:0 0 16px;}
  .process-head .gold-rule{margin:0 auto;}
  .process-row{display:flex; align-items:flex-start; justify-content:space-between; gap:10px; position:relative;}
  .process-connector{
    position:absolute; top:41px; left:60px; right:60px; height:0;
    border-top:2px dashed rgba(217,169,64,.4); z-index:0;
  }
  .process-step{flex:1; display:flex; flex-direction:column; align-items:center; text-align:center; gap:16px; position:relative; z-index:1;}
  .process-step .circles{display:flex; align-items:center; gap:10px;}
  .process-step .ic-circle{
    width:82px; height:82px; border-radius:50%; border:1.5px solid var(--gold-600);
    display:flex; align-items:center; justify-content:center; background:var(--navy-900);
  }
  .process-step .ic-circle svg{width:30px; height:30px; stroke:var(--text-hi);}
  .process-step .num-circle{
    width:36px; height:36px; border-radius:50%; border:1.5px solid var(--gold-600);
    display:flex; align-items:center; justify-content:center; background:var(--navy-950);
    font-family:'Space Grotesk',sans-serif; font-weight:700; color:var(--gold-300); font-size:13px;
  }
  .process-step h5{font-size:16px;}
  .process-step p{color:var(--text-mid); font-size:13px; line-height:1.85; max-width:20ch;}
  @media (max-width:860px){
    .process-row{flex-direction:column; gap:36px;}
    .process-connector{display:none;}
  }

  /* ================= CTA COLLAB (light) ================= */
  #cta-collab{background:var(--cream); padding:0;}
  .cta-collab{display:flex; align-items:stretch; min-height:340px;}
  .cta-collab-media{flex:0 0 40%; position:relative; overflow:hidden;}
  .cta-collab-media img{position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:left center;}
  .cta-collab-text{flex:1; display:flex; flex-direction:column; justify-content:center; padding:60px;}
  .cta-collab-text h2{color:var(--ink); font-size:clamp(22px,2.6vw,30px); margin-bottom:14px;}
  .cta-collab-text p{color:var(--ink-soft); font-size:15px; margin-bottom:28px;}
  @media (max-width:768px){
    .cta-collab{flex-direction:column;}
    .cta-collab-media{flex:none; height:220px;}
    .cta-collab-text{padding:40px 24px;}
  }

  /* ================= FOOTER ================= */
  footer.site-footer{background:var(--navy-950); border-top:1px solid var(--navy-800); padding:70px 0 0;}
  .footer-grid{display:grid; grid-template-columns:1.4fr 1fr 1fr 1.1fr; gap:40px; padding-bottom:50px;}
  .footer-brand .en{font-size:28px; font-weight:700;}
  .footer-brand p{color:var(--text-mid); font-size:13.5px; line-height:1.9; margin:16px 0 20px; max-width:34ch;}
  .footer-social{display:flex; gap:10px; flex-wrap:wrap;}
  .footer-social a{width:36px; height:36px; border-radius:50%; background:var(--navy-900); border:1px solid var(--navy-700); display:flex; align-items:center; justify-content:center; overflow:hidden;}
  .footer-social img{width:18px; height:18px; border-radius:50%;}
  .footer-col h6{font-size:14.5px; margin-bottom:18px; color:var(--text-hi);}
  .footer-col ul{list-style:none; display:flex; flex-direction:column; gap:12px;}
  .footer-col a{color:var(--text-mid); font-size:13.5px; display:inline-flex; align-items:center; gap:6px; transition:color .3s;}
  .footer-col a:hover{color:var(--gold-300);}
  .footer-contact li{display:flex; align-items:center; gap:10px; color:var(--text-mid); font-size:13.5px; margin-bottom:14px;}
  .footer-contact svg{width:16px; height:16px; stroke:var(--gold-500); flex:none;}
  .footer-bottom{border-top:1px solid var(--navy-800); padding:20px 0; display:flex; align-items:center; justify-content:space-between; color:var(--text-lo); font-size:12.5px;}
  .footer-bottom svg{width:26px; height:14px;}
  @media (max-width:900px){ .footer-grid{grid-template-columns:1fr 1fr;} }
  @media (max-width:560px){ .footer-grid{grid-template-columns:1fr;} .footer-bottom{flex-direction:column; gap:10px; text-align:center;} }
</style>

@endsection