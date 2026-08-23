

@extends('layout.main.header')
@section('content')


<!-- ================= HERO ================= -->
<section class="hero" id="hero">
  <a
    href="https://user-panel.clpiran.com/"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="ورود به پنل کاربری لوپ"
  >
    <picture>
      <source
        media="(max-width: 767px)"
        srcset="{{ asset('assets/new-style/mobile/1.png') }}"
        width="1023"
        height="1537"
      >
      <img
        class="hero-image"
        src="{{ asset('assets/new-style/1.jpg') }}"
        alt="LOOP — Every connection creates a new path"
        width="1776"
        height="888"
        fetchpriority="high"
      >
    </picture>
  </a>
  <div class="container hero-inner">

    <div class="road-wrap reveal">
      <svg viewBox="0 0 1200 630" fill="none">
        <defs>
          <linearGradient id="skyGlow" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#0a1526"/>
            <stop offset=".6" stop-color="#14263f"/>
            <stop offset="1" stop-color="#2a3a2c" stop-opacity="0"/>
          </linearGradient>
          <radialGradient id="sunGlow" cx="50%" cy="52%" r="50%">
            <stop offset="0" stop-color="#f9edc7"/>
            <stop offset=".35" stop-color="#d9a940" stop-opacity=".6"/>
            <stop offset="1" stop-color="#d9a940" stop-opacity="0"/>
          </radialGradient>
          <linearGradient id="roadGold" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#f9edc7"/>
            <stop offset="1" stop-color="#c08f2b" stop-opacity=".2"/>
          </linearGradient>
        </defs>

        <rect x="0" y="0" width="1200" height="360" fill="url(#skyGlow)"/>
        <circle cx="600" cy="330" r="260" fill="url(#sunGlow)"/>

        <!-- side walls -->
        <polygon points="0,120 260,150 190,560 0,630" fill="#0f2038" opacity=".9"/>
        <polygon points="1200,120 940,150 1010,560 1200,630" fill="#0f2038" opacity=".9"/>
        <polyline points="0,120 260,150" stroke="#d9a940" stroke-width="2" opacity=".7"/>
        <polyline points="1200,120 940,150" stroke="#d9a940" stroke-width="2" opacity=".7"/>

        <!-- converging path lines -->
        <g class="flow-line" stroke="url(#roadGold)" stroke-width="2" opacity=".8">
          <line x1="120" y1="630" x2="560" y2="335"/>
          <line x1="330" y1="630" x2="575" y2="335"/>
          <line x1="600" y1="630" x2="600" y2="330"/>
          <line x1="870" y1="630" x2="625" y2="335"/>
          <line x1="1080" y1="630" x2="640" y2="335"/>
        </g>

        <!-- vanishing point ring -->
        <circle class="pulse-ring" cx="600" cy="335" r="10" fill="none" stroke="#f9edc7" stroke-width="2"/>
        <circle class="pulse-ring" cx="600" cy="335" r="34" fill="none" stroke="#d9a940" stroke-width="1.5" opacity=".6"/>
      </svg>
    </div>

    <div class="hero-copy">
      <div class="col en">
        <span class="eyebrow">LOOP</span>
        <h1 class="en">Every connection<br><span class="gold-text">creates a new path.</span></h1>
        <p class="en">LOOP is where opportunities connect, trust grows, and the future is reimagined.</p>
        <a href="#ecosystem" class="btn">Explore the Journey
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>

      <div class="hero-divider"></div>

      <div class="col fa" dir="rtl">
        <span class="eyebrow" style="direction:ltr; display:inline-flex;">LOOP</span>
        <h1>هر اتصال،<br><span class="gold-text">یک شروع</span></h1>
        <p>LOOP جایی است که ارتباط‌ها به فرصت، اعتماد و آینده تبدیل می‌شوند.</p>
        <a href="#ecosystem" class="btn">شروع مسیر
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        </a>
      </div>
    </div>

    <div class="hero-tagline reveal">
      <span>با LOOP: تا بی‌نهایت در کنار هم هستیم.</span>
      <svg class="infinity" viewBox="0 0 46 22" fill="none">
        <path d="M12 4a7 7 0 1 0 0 14 12 12 0 0 0 11-7 12 12 0 0 1 11-7 7 7 0 1 1 0 14 12 12 0 0 1-11-7 12 12 0 0 0-11-7Z" stroke="#d9a940" stroke-width="1.6"/>
      </svg>
    </div>
  </div>
</section>

<!-- ================= ABOUT ================= -->
<section id="about">
  <div class="about-banner reveal">
    <svg class="about-banner-radar" viewBox="0 0 200 200" fill="none">
      <circle cx="100" cy="100" r="40" stroke="#d9a940" stroke-opacity=".35" stroke-width="1"/>
      <circle cx="100" cy="100" r="70" stroke="#d9a940" stroke-opacity=".22" stroke-width="1"/>
      <circle cx="100" cy="100" r="100" stroke="#d9a940" stroke-opacity=".12" stroke-width="1"/>
      <line x1="100" y1="0" x2="100" y2="200" stroke="#d9a940" stroke-opacity=".1" stroke-width="1"/>
      <line x1="0" y1="100" x2="200" y2="100" stroke="#d9a940" stroke-opacity=".1" stroke-width="1"/>
    </svg>

    <div class="about-banner-text">
      <span class="eyebrow">LOOP</span>
      <h2>درباره <span class="gold-text en">LOOP</span></h2>
      <p>لوپ یک شرکت متعهد و متخصص می‌باشد که با استفاده از تکنولوژی و دانش روز، بهترین راهکارها را برای شما فراهم می‌کند.</p>
      <a href="{{ route('web.about') }}" class="btn solid">بیشتر درباره ما
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
      </a>
    </div>

    <div class="about-banner-media">
      <img src="{{ asset('assets/new-style/aboutsec1.jpeg') }}" alt="تیم LOOP" loading="lazy">
    </div>
  </div>
</section>

<!-- ================= ECOSYSTEM ================= -->
<section id="ecosystem" style="background-image:url('assets/new-style/back.png')">
  <div class="eco-atmosphere">
    <div class="layer-base"></div>
    <div class="layer-grid"></div>
    <div class="layer-diagonal"></div>
    <svg class="layer-skyline" viewBox="0 0 1200 220" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <g fill="#0d1c30">
        <rect x="0" y="120" width="60" height="100"/><rect x="70" y="80" width="45" height="140"/>
        <rect x="125" y="140" width="35" height="80"/><rect x="170" y="60" width="55" height="160"/>
        <rect x="235" y="110" width="40" height="110"/><rect x="285" y="90" width="30" height="130"/>
        <rect x="325" y="150" width="60" height="70"/><rect x="395" y="70" width="45" height="150"/>
        <rect x="450" y="130" width="35" height="90"/><rect x="495" y="100" width="50" height="120"/>
        <rect x="555" y="150" width="30" height="70"/><rect x="595" y="60" width="55" height="160"/>
        <rect x="660" y="115" width="40" height="105"/><rect x="710" y="85" width="35" height="135"/>
        <rect x="755" y="140" width="55" height="80"/><rect x="820" y="65" width="45" height="155"/>
        <rect x="875" y="120" width="35" height="100"/><rect x="920" y="95" width="50" height="125"/>
        <rect x="980" y="150" width="30" height="70"/><rect x="1020" y="70" width="55" height="150"/>
        <rect x="1085" y="130" width="40" height="90"/><rect x="1135" y="100" width="60" height="120"/>
      </g>
      <g stroke="#d9a940" stroke-opacity=".25" stroke-width="1">
        <line x1="90" y1="90" x2="90" y2="215"/><line x1="190" y1="70" x2="190" y2="215"/>
        <line x1="420" y1="80" x2="420" y2="215"/><line x1="620" y1="70" x2="620" y2="215"/>
        <line x1="840" y1="75" x2="840" y2="215"/><line x1="1045" y1="80" x2="1045" y2="215"/>
      </g>
    </svg>
    <div class="layer-glow"></div>
    <div class="layer-vignette"></div>
  </div>
  <div class="container">
    <div class="eco-head reveal">
      <h2>اکوسیستم <span class="gold-text en">LOOP</span></h2>
      <p class="sub">همه چیز به هم متصل است.</p>
      <div class="gold-rule"></div>
      <p>اکوسیستم LOOP مجموعه‌ای یکپارچه از وب‌سایت، اپلیکیشن‌ها، خدمات، آموزش و پشتیبانی است.<br>که برای ایجاد تجربه‌ای هوشمند، سریع و مطمئن طراحی شده است.</p>
    </div>

    <div class="eco-wrap reveal">
      <svg class="rings rings-desktop" viewBox="0 0 1100 640" preserveAspectRatio="none" style="z-index:1;">
        <defs>
          <filter id="dotGlow" x="-200%" y="-200%" width="500%" height="500%">
            <feGaussianBlur stdDeviation="2" result="b"/>
            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
          <filter id="lineGlow" x="-40%" y="-40%" width="180%" height="180%">
            <feGaussianBlur stdDeviation="1.2" result="b"/>
            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
          <filter id="lineGlowStrong" x="-60%" y="-60%" width="220%" height="220%">
            <feGaussianBlur stdDeviation="2.2" result="b1"/>
            <feMerge><feMergeNode in="b1"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
          <radialGradient id="hubHalo" cx="50%" cy="50%" r="50%">
            <stop offset="0" stop-color="#d9a940" stop-opacity=".3"/>
            <stop offset="55%" stop-color="#d9a940" stop-opacity=".09"/>
            <stop offset="100%" stop-color="#d9a940" stop-opacity="0"/>
          </radialGradient>
          <linearGradient id="lineGold" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#dba84f"/>
            <stop offset="50%" stop-color="#c9932f"/>
            <stop offset="100%" stop-color="#96701f"/>
          </linearGradient>
          <linearGradient id="vAxisFade" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#f2c968" stop-opacity="0"/>
            <stop offset="50%" stop-color="#f2c968" stop-opacity=".5"/>
            <stop offset="100%" stop-color="#f2c968" stop-opacity="0"/>
          </linearGradient>
          <g id="ecoPathTL"><path d="M438,272 C 370,278 305,250 282,204"/></g>
          <g id="ecoPathTR"><path d="M662,272 C 730,278 795,250 818,204"/></g>
          <g id="ecoPathML"><path d="M428,320 L222,320"/></g>
          <g id="ecoPathMR"><path d="M672,320 L878,320"/></g>
          <g id="ecoPathBL"><path d="M438,368 C 370,362 305,390 282,436"/></g>
          <g id="ecoPathBR"><path d="M662,368 C 730,362 795,390 818,436"/></g>
        </defs>

        <!-- soft, contained halo behind the hub -->
        <ellipse class="eco-halo" cx="550" cy="320" rx="172" ry="160" fill="url(#hubHalo)"/>

        <!-- short, faded radial guide axes -->
        <line x1="550" y1="148" x2="550" y2="192" stroke="url(#vAxisFade)" stroke-width="1.2"/>
        <line x1="550" y1="448" x2="550" y2="492" stroke="url(#vAxisFade)" stroke-width="1.2"/>
        <g fill="#f2c968" filter="url(#dotGlow)">
          <circle cx="550" cy="160" r="2"/><circle cx="550" cy="180" r="2.6"/>
          <circle cx="550" cy="480" r="2"/><circle cx="550" cy="460" r="2.6"/>
        </g>

        <!-- concentric ring system: six rings, mixed styles, crisp -->
        <circle cx="550" cy="320" r="122" fill="none" stroke="#f9edc7" stroke-opacity=".62" stroke-width="1.5" filter="url(#lineGlow)"/>
        <circle cx="550" cy="320" r="140" fill="none" stroke="#d9a940" stroke-opacity=".4" stroke-width="1.1"/>
        <circle cx="550" cy="320" r="160" fill="none" stroke="#d9a940" stroke-opacity=".32" stroke-width="1" stroke-dasharray="2 6"/>
        <circle class="eco-spin" cx="550" cy="320" r="182" fill="none" stroke="#f2c968" stroke-opacity=".46" stroke-width="1.3" stroke-dasharray="2 10"/>
        <circle cx="550" cy="320" r="205" fill="none" stroke="#d9a940" stroke-opacity=".2" stroke-width="1" stroke-dasharray="1 5"/>
        <circle class="eco-spin-rev" cx="550" cy="320" r="228" fill="none" stroke="#d9a940" stroke-opacity=".3" stroke-width="1" stroke-dasharray="1 4 1 14"/>

        <!-- scattered network particles -->
        <g fill="#f2c968" filter="url(#dotGlow)">
          <circle class="eco-twinkle" cx="644" cy="158" r="2.2"/>
          <circle class="eco-twinkle" cx="456" cy="158" r="2.6"/>
          <circle class="eco-twinkle" cx="656" cy="504" r="2.4"/>
          <circle class="eco-twinkle" cx="444" cy="504" r="2"/>
          <circle class="eco-twinkle" cx="795" cy="320" r="2.4"/>
          <circle class="eco-twinkle" cx="305" cy="320" r="2.2"/>
        </g>

        <!-- curved connective paths hub → nodes: thin blurred glow + sharp golden core + faint warm highlight -->
        <g>
          <use href="#ecoPathTL" fill="none" stroke="url(#lineGold)" stroke-width="2.2" stroke-linecap="round" stroke-opacity=".4" filter="url(#lineGlowStrong)"/>
          <use href="#ecoPathTR" fill="none" stroke="url(#lineGold)" stroke-width="2.2" stroke-linecap="round" stroke-opacity=".4" filter="url(#lineGlowStrong)"/>
          <use href="#ecoPathML" fill="none" stroke="url(#lineGold)" stroke-width="2.2" stroke-linecap="round" stroke-opacity=".4" filter="url(#lineGlowStrong)"/>
          <use href="#ecoPathMR" fill="none" stroke="url(#lineGold)" stroke-width="2.2" stroke-linecap="round" stroke-opacity=".4" filter="url(#lineGlowStrong)"/>
          <use href="#ecoPathBL" fill="none" stroke="url(#lineGold)" stroke-width="2.2" stroke-linecap="round" stroke-opacity=".4" filter="url(#lineGlowStrong)"/>
          <use href="#ecoPathBR" fill="none" stroke="url(#lineGold)" stroke-width="2.2" stroke-linecap="round" stroke-opacity=".4" filter="url(#lineGlowStrong)"/>

          <use href="#ecoPathTL" class="eco-line" fill="none" stroke="#d9a940" stroke-width="1.3" stroke-linecap="round" stroke-opacity=".92"/>
          <use href="#ecoPathTR" class="eco-line" fill="none" stroke="#d9a940" stroke-width="1.3" stroke-linecap="round" stroke-opacity=".92"/>
          <use href="#ecoPathML" class="eco-line" fill="none" stroke="#d9a940" stroke-width="1.3" stroke-linecap="round" stroke-opacity=".92"/>
          <use href="#ecoPathMR" class="eco-line" fill="none" stroke="#d9a940" stroke-width="1.3" stroke-linecap="round" stroke-opacity=".92"/>
          <use href="#ecoPathBL" class="eco-line" fill="none" stroke="#d9a940" stroke-width="1.3" stroke-linecap="round" stroke-opacity=".92"/>
          <use href="#ecoPathBR" class="eco-line" fill="none" stroke="#d9a940" stroke-width="1.3" stroke-linecap="round" stroke-opacity=".92"/>

          <use href="#ecoPathTL" fill="none" stroke="#fff3d0" stroke-width=".45" stroke-linecap="round" stroke-opacity=".4"/>
          <use href="#ecoPathTR" fill="none" stroke="#fff3d0" stroke-width=".45" stroke-linecap="round" stroke-opacity=".4"/>
          <use href="#ecoPathBL" fill="none" stroke="#fff3d0" stroke-width=".45" stroke-linecap="round" stroke-opacity=".4"/>
          <use href="#ecoPathBR" fill="none" stroke="#fff3d0" stroke-width=".45" stroke-linecap="round" stroke-opacity=".4"/>
        </g>

        <!-- glowing junction dots along every path -->
        <g fill="#f2c968" filter="url(#dotGlow)">
          <!-- hub anchors -->
          <circle cx="438" cy="272" r="4.8"/><circle cx="662" cy="272" r="4.8"/>
          <circle cx="428" cy="320" r="4.8"/><circle cx="672" cy="320" r="4.8"/>
          <circle cx="438" cy="368" r="4.8"/><circle cx="662" cy="368" r="4.8"/>
          <!-- top curves -->
          <circle cx="405" cy="276" r="2.2"/><circle cx="355" cy="264" r="3.2"/><circle cx="315" cy="240" r="2.4"/><circle cx="292" cy="212" r="1.8"/>
          <circle cx="695" cy="276" r="2.2"/><circle cx="745" cy="264" r="3.2"/><circle cx="785" cy="240" r="2.4"/><circle cx="808" cy="212" r="1.8"/>
          <!-- bottom curves -->
          <circle cx="405" cy="364" r="2.2"/><circle cx="355" cy="376" r="3.2"/><circle cx="315" cy="400" r="2.4"/><circle cx="292" cy="428" r="1.8"/>
          <circle cx="695" cy="364" r="2.2"/><circle cx="745" cy="376" r="3.2"/><circle cx="785" cy="400" r="2.4"/><circle cx="808" cy="428" r="1.8"/>
          <!-- horizontal axis: solid segments, dots and intersection nodes -->
          <circle cx="410" cy="320" r="2"/><circle cx="390" cy="320" r="1.6"/><circle cx="368" cy="320" r="3.6"/>
          <circle cx="345" cy="320" r="2"/><circle cx="322" cy="320" r="3"/><circle cx="290" cy="320" r="1.8"/><circle cx="255" cy="320" r="2.4"/>
          <circle cx="690" cy="320" r="2"/><circle cx="710" cy="320" r="1.6"/><circle cx="732" cy="320" r="3.6"/>
          <circle cx="755" cy="320" r="2"/><circle cx="778" cy="320" r="3"/><circle cx="810" cy="320" r="1.8"/><circle cx="845" cy="320" r="2.4"/>
          <!-- node anchors -->
          <circle cx="282" cy="204" r="5.6"/><circle cx="818" cy="204" r="5.6"/>
          <circle cx="222" cy="320" r="5.6"/><circle cx="878" cy="320" r="5.6"/>
          <circle cx="282" cy="436" r="5.6"/><circle cx="818" cy="436" r="5.6"/>
        </g>

        <!-- larger outlined circular nodes at key intersections -->
        <g fill="none" stroke="#f2c968" stroke-opacity=".5" stroke-width="1">
          <circle cx="355" cy="264" r="7.5"/><circle cx="745" cy="264" r="7.5"/>
          <circle cx="355" cy="376" r="7.5"/><circle cx="745" cy="376" r="7.5"/>
          <circle cx="368" cy="320" r="8"/><circle cx="732" cy="320" r="8"/>
          <circle cx="322" cy="320" r="6"/><circle cx="778" cy="320" r="6"/>
        </g>
      </svg>

      <svg class="rings rings-mobile" viewBox="0 0 332 470" preserveAspectRatio="none">
        <defs>
          <filter id="dotGlowM" x="-200%" y="-200%" width="500%" height="500%">
            <feGaussianBlur stdDeviation="1.5" result="b"/>
            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
          <filter id="lineGlowM" x="-60%" y="-60%" width="220%" height="220%">
            <feGaussianBlur stdDeviation="1.6" result="b"/>
            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
          <radialGradient id="hubHaloM" cx="50%" cy="50%" r="50%">
            <stop offset="0" stop-color="#d9a940" stop-opacity=".36"/>
            <stop offset="55%" stop-color="#d9a940" stop-opacity=".11"/>
            <stop offset="100%" stop-color="#d9a940" stop-opacity="0"/>
          </radialGradient>
          <linearGradient id="lineGoldM" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#e9c06a"/>
            <stop offset="100%" stop-color="#a97a1e"/>
          </linearGradient>
        </defs>

        <ellipse cx="166" cy="235" rx="86" ry="86" fill="url(#hubHaloM)"/>

        <!-- decorative concentric rings: solid + dotted, between hub and side circles -->
        <g fill="none" stroke="#d9a940">
          <circle cx="166" cy="235" r="76" stroke-opacity=".42" stroke-width="1.1" filter="url(#lineGlowM)"/>
          <circle cx="166" cy="235" r="87" stroke-opacity=".28" stroke-width="1" stroke-dasharray="2 5"/>
          <circle class="eco-spin" cx="166" cy="235" r="98" stroke-opacity=".32" stroke-width="1" stroke-dasharray="1 4 1 10" style="transform-origin:166px 235px;"/>
        </g>

        <!-- small twinkling particles around the rings -->
        <g fill="#f2c968" filter="url(#dotGlowM)">
          <circle class="eco-twinkle" cx="204" cy="150" r="1.8"/>
          <circle class="eco-twinkle" cx="128" cy="150" r="1.6"/>
          <circle class="eco-twinkle" cx="204" cy="320" r="1.8"/>
          <circle class="eco-twinkle" cx="128" cy="320" r="1.6"/>
        </g>

        <!-- curved connectors hub -> nodes -->
        <g fill="none" stroke="url(#lineGoldM)" stroke-width="1.5" stroke-linecap="round" filter="url(#lineGlowM)">
          <path d="M131.8,176.2 Q98,140 95.1,113"/>
          <path d="M200.2,176.2 Q234,140 236.9,113"/>
          <path d="M98,235 L94,235"/>
          <path d="M234,235 L238,235"/>
          <path d="M131.8,293.8 Q98,330 95.1,357"/>
          <path d="M200.2,293.8 Q234,330 236.9,357"/>
        </g>

        <g fill="#f2c968" filter="url(#dotGlowM)">
          <circle cx="131.8" cy="176.2" r="2.8"/><circle cx="95.1" cy="113" r="3.4"/><circle cx="112" cy="141" r="1.8"/>
          <circle cx="200.2" cy="176.2" r="2.8"/><circle cx="236.9" cy="113" r="3.4"/><circle cx="220" cy="141" r="1.8"/>
          <circle cx="98" cy="235" r="2.8"/><circle cx="94" cy="235" r="3.4"/>
          <circle cx="234" cy="235" r="2.8"/><circle cx="238" cy="235" r="3.4"/>
          <circle cx="131.8" cy="293.8" r="2.8"/><circle cx="95.1" cy="357" r="3.4"/><circle cx="112" cy="329" r="1.8"/>
          <circle cx="200.2" cy="293.8" r="2.8"/><circle cx="236.9" cy="357" r="3.4"/><circle cx="220" cy="329" r="1.8"/>
        </g>
      </svg>

      <svg class="rings rings-mobile-v2" viewBox="0 0 626 531" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
        <defs>
          <filter id="ecoGlowMobileV2" x="-100%" y="-100%" width="300%" height="300%">
            <feGaussianBlur stdDeviation="3" result="blur"/>
            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
          <radialGradient id="ecoHaloMobileV2" cx="50%" cy="50%" r="50%">
            <stop offset="0" stop-color="#d9a940" stop-opacity=".28"/>
            <stop offset=".62" stop-color="#d9a940" stop-opacity=".07"/>
            <stop offset="1" stop-color="#d9a940" stop-opacity="0"/>
          </radialGradient>
          <linearGradient id="ecoLineMobileV2" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#fff0ae"/>
            <stop offset=".5" stop-color="#e2ad3f"/>
            <stop offset="1" stop-color="#a97418"/>
          </linearGradient>
        </defs>

        <circle cx="313" cy="266" r="166" fill="url(#ecoHaloMobileV2)"/>

        <g fill="none" stroke="#d9a940">
          <circle cx="313" cy="266" r="108" stroke-width="1.6" stroke-opacity=".8"/>
          <circle cx="313" cy="266" r="122" stroke-width="1.2" stroke-opacity=".55"/>
          <circle cx="313" cy="266" r="137" stroke-width="1" stroke-opacity=".44"/>
          <circle cx="313" cy="266" r="151" stroke-width="1" stroke-opacity=".32" stroke-dasharray="2 5"/>
          <circle cx="313" cy="266" r="165" stroke-width="1" stroke-opacity=".25" stroke-dasharray="1 7"/>
          <circle cx="313" cy="266" r="179" stroke-width=".8" stroke-opacity=".18" stroke-dasharray="2 9"/>
        </g>

        <g fill="none" stroke="url(#ecoLineMobileV2)" stroke-width="2" stroke-linecap="round">
          <path d="M244 193 C210 166 187 125 151 104"/>
          <path d="M382 193 C416 166 439 125 475 104"/>
          <path d="M216 266 L171 266"/>
          <path d="M410 266 L455 266"/>
          <path d="M244 339 C210 366 187 407 151 428"/>
          <path d="M382 339 C416 366 439 407 475 428"/>
        </g>

        <g fill="#f7cf70" filter="url(#ecoGlowMobileV2)">
          <circle cx="244" cy="193" r="5"/><circle cx="151" cy="104" r="5"/>
          <circle cx="382" cy="193" r="5"/><circle cx="475" cy="104" r="5"/>
          <circle cx="216" cy="266" r="5"/><circle cx="171" cy="266" r="5"/>
          <circle cx="410" cy="266" r="5"/><circle cx="455" cy="266" r="5"/>
          <circle cx="244" cy="339" r="5"/><circle cx="151" cy="428" r="5"/>
          <circle cx="382" cy="339" r="5"/><circle cx="475" cy="428" r="5"/>

          <circle cx="205" cy="157" r="3"/><circle cx="187" cy="129" r="2.3"/>
          <circle cx="421" cy="157" r="3"/><circle cx="439" cy="129" r="2.3"/>
          <circle cx="194" cy="266" r="2.6"/><circle cx="432" cy="266" r="2.6"/>
          <circle cx="205" cy="375" r="3"/><circle cx="187" cy="403" r="2.3"/>
          <circle cx="421" cy="375" r="3"/><circle cx="439" cy="403" r="2.3"/>
        </g>

        <g stroke="#d9a940" stroke-opacity=".32" fill="none">
          <line x1="313" y1="62" x2="313" y2="158"/>
          <line x1="313" y1="374" x2="313" y2="470"/>
        </g>
        <g fill="#f7cf70" filter="url(#ecoGlowMobileV2)">
          <circle cx="313" cy="78" r="3.5"/>
          <circle cx="313" cy="111" r="2.4"/>
          <circle cx="313" cy="142" r="4"/>
          <circle cx="313" cy="390" r="4"/>
          <circle cx="313" cy="421" r="2.4"/>
          <circle cx="313" cy="454" r="3.5"/>
        </g>
      </svg>

      <div class="eco-hub">
        {{-- <span class="en">LOOP</span>
        <small>CONNECTING EVERYTHING</small> --}}
        <img src="{{asset('assets/new-style/center.png')}}" style="width:85%" />
      </div>

      <div class="eco-node pos-tl">
        <div class="icon">
          {{-- <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg> --}}
          <img src="{{ asset('assets/new-style/icon/2.png') }}"/>
        </div>
        <h4>Website</h4><p>دسترسی سریع<br>به خدمات<br><span class="site en">clpiran.com</span></p>
      </div>
      <div class="eco-node pos-tr">
        <div class="icon">
          {{-- <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg> --}}
          <img src="{{ asset('assets/new-style/icon/4.png') }}"/>
        </div>
        <h4>User<br>App</h4><p>درخواست خدمات<br>مدیریت کاربران</p>
      </div>
      <div class="eco-node pos-ml">
        <div class="icon">
          {{-- <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 4v5"/></svg> --}}
          <img src="{{ asset('assets/new-style/icon/1.png') }}"/>
        </div>
        <h4>Enterprise</h4><p>سازمان‌ها و شرکت‌ها<br>مدیریت پروژه‌ها</p>
      </div>
      <div class="eco-node pos-mr">
        <div class="icon">
          {{-- <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 1 5 5l-2.4-.7-1.9 1.9-2-2 1.9-1.9-.6-2.3z"/><path d="M15.4 12.6 20 17.2a2 2 0 0 1-2.8 2.8l-4.6-4.6"/><path d="M9 6.5 6.5 9 3 5.5 5.5 3 9 6.5z"/><path d="m9 6.5 8.5 8.5"/><path d="M8 14.5 3.5 19a2 2 0 0 0 2.8 2.8l4.5-4.5"/></svg> --}}
          <img src="{{ asset('assets/new-style/icon/5.png') }}"/>
        </div>
        <h4>Technician<br>App</h4><p>مدیریت مأموریت‌ها<br>عملیات میدانی</p>
      </div>
      <div class="eco-node pos-bl">
        <div class="icon">
          {{-- <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/></svg> --}}
          <img src="{{ asset('assets/new-style/icon/3.png') }}"/>
        </div>
        <h4>Academy</h4><p>آموزش تخصصی<br>کلاس‌ها و دوره‌ها</p>
      </div>
      <div class="eco-node pos-br">
        <div class="icon">
          {{-- <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5-9-10a5 5 0 0 1 9-4 5 5 0 0 1 9 4c-2 5-9 10-9 10Z"/><circle cx="12" cy="11" r="2"/></svg> --}}
          <img src="{{ asset('assets/new-style/icon/6.png') }}"/>
        </div>
        <h4>Service<br>Center</h4><p>پشتیبانی حضوری<br>عملیات فیزیکی</p>
      </div>
    </div>

    <div class="eco-ready reveal">
      <div class="ln" style="background:linear-gradient(90deg, transparent, var(--gold-600));"></div>
      <span>آماده‌اید متصل شوید؟</span>
      <div class="ln"></div>
    </div>
    <div class="eco-cta reveal">
      <a href="#apps" class="btn solid">شروع تجربه LOOP
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
      <a href="#apps" class="btn">مشاهده اپلیکیشن‌ها
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- ================= ACADEMY BANNER ================= -->
<section class="academy-banner reveal">
  <div class="academy-banner-shell">
    <div class="academy-banner-content">
      <div class="academy-banner-dots"></div>
      <div class="academy-banner-text">
        <h2>آکادمی <span class="gold-text en">LOOP</span></h2>
        <p class="sub">دانش، کلید پیشرفت است</p>
        <p class="desc">دوره‌های تخصصی از مقدماتی تا پیشرفته</p>
        <a href="{{ route('loop.learn') }}" class="btn solid">مشاهده دوره‌ها</a>
      </div>
    </div>
    <div
      class="academy-banner-media"
      role="img"
      aria-label="کلاس آموزشی آکادمی لوپ"
      style="background-image:url('{{ asset('assets/new-style/academysec1.jpeg') }}')"
    >
      <div class="academy-banner-fade"></div>
    </div>
    
  </div>
</section>



<!-- ================= APPS ================= -->
@php
  $existingMockupImage = asset('assets/new-style/mockup.png');
  $existingMockupImage2 = asset('assets/new-style/mockup2.png');
  $existingMockupImage3 = asset('assets/new-style/mockup3.png');
  $applications = [
    [
      'gold' => 'LOOP',
      'name' => 'User',
      'tag' => 'اپلیکیشن کاربران',
      'desc' => 'درخواست خدمات، پیگیری، پرداخت',
      'mockupImage' => $existingMockupImage,
    ],
    [
      'gold' => 'LOOP',
      'name' => 'Tech',
      'tag' => 'اپلیکیشن تکنسین',
      'desc' => 'مدیریت سفارش‌ها، ابزارها، آموزش',
      'mockupImage' => $existingMockupImage2,
    ],
    [
      'gold' => 'LOOP',
      'name' => 'Enterprise',
      'tag' => 'اپلیکیشن سازمانی',
      'desc' => 'مدیریت ناوگان، گزارش‌ها، تحلیل‌ها',
      'mockupImage' => $existingMockupImage3,
    ],
  ];
@endphp
<section id="apps">
  <div class="apps-bg">
    <div class="layer-base"></div>
    <div class="layer-grid"></div>
    <div class="layer-glow"></div>
  </div>
  <div class="apps-wide">
    <div class="apps-head reveal">
      <span class="eyebrow rule-center">اپلیکیشن‌ها</span>
      <h2>اپلیکیشن‌های <span class="gold-text en">LOOP</span></h2>
      <p>همراه شما در هر لحظه و هر زمان</p>
    </div>

    <div class="apps-grid reveal">
      @foreach ($applications as $app)
      <div class="app-card">
        <div class="app-card-text">
          <div>
            <h3><span class="en gold-text">{{ $app['gold'] }}</span><span class="en">{{ $app['name'] }}</span></h3>
            <p class="tag">{{ $app['tag'] }}</p>
            <p class="desc">{{ $app['desc'] }}</p>
          </div>

          <div class="qr-block">
            <img class="qr" src="{{ asset('assets/new-style/qr.jpeg') }}" alt="QR کد دریافت {{ $app['gold'] }} {{ $app['name'] }}">
            <div class="store-col">
              <div class="store-btn">
                <img src="{{ asset('assets/new-style/google-play.png') }}" alt="Google Play">
                <span class="st-text en"><small>GET IT ON</small><strong>Google Play</strong></span>
              </div>
              <div class="store-btn">
                <img src="{{ asset('assets/new-style/app-store.png') }}" alt="App Store">
                <span class="st-text en"><small>Download on the</small><strong>App Store</strong></span>
              </div>
            </div>
          </div>

          <a href="#" class="btn get-btn">دریافت اپ
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v13m0 0-4-4m4 4 4-4M5 21h14"/></svg>
          </a>
        </div>

        <div class="app-card-phone">
          <img src="{{ $app['mockupImage'] }}" alt="نمای اپلیکیشن {{ $app['gold'] }} {{ $app['name'] }}">
        </div>
      </div>
      @endforeach
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

  /* ---------- shared ---------- */
  .eyebrow{
    display:inline-flex; align-items:center; gap:10px;
    font-family:'Space Grotesk', sans-serif;
    font-size:12px; letter-spacing:.28em; text-transform:uppercase;
    color:var(--gold-500);
  }
  .gold-rule{
    width:64px; height:2px; background:var(--line-gold);
    background:linear-gradient(90deg, var(--gold-500), transparent);
  }
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
    white-space:nowrap;
  }
  .btn svg{transition:transform .35s var(--ease);}
  .btn:hover{background:var(--gold-500); color:var(--navy-950); border-color:var(--gold-500);}
  .btn:hover svg{transform:translateX(-4px);}
  .btn.solid{background:linear-gradient(135deg, var(--gold-300), var(--gold-600)); color:var(--navy-950); border:none;    box-shadow: 0 0 18px rgb(255 197 67), 0 0 40px rgb(255 212 117 / 38%), inset 0 0 22px rgb(218 178 105), inset 0 1px 0 rgba(255, 255, 255, .06);}
  .btn.solid:hover{filter:brightness(1.08); transform:translateY(-2px);}
  [dir="rtl"] .btn:hover svg{transform:translateX(4px);}

  section{position:relative; padding:140px 0;}
  @media (max-width:900px){ section{padding:88px 0;} }

  .reveal{opacity:0; transform:translateY(28px); transition:opacity .9s var(--ease), transform .9s var(--ease);}
  .reveal.in{opacity:1; transform:translateY(0);}

  /* faint circuit texture used across the page as connective tissue */
  .circuit-bg{
    position:absolute; inset:0; pointer-events:none; opacity:.35;
    background-image:
      linear-gradient(var(--navy-700) 1px, transparent 1px),
      linear-gradient(90deg, var(--navy-700) 1px, transparent 1px);
    background-size:56px 56px;
    -webkit-mask-image:radial-gradient(ellipse 60% 60% at 50% 30%, #000 0%, transparent 70%);
    mask-image:radial-gradient(ellipse 60% 60% at 50% 30%, #000 0%, transparent 70%);
  }

  /* ---------- nav dots ---------- */
  .side-nav{
    position:fixed; left:26px; top:50%; transform:translateY(-50%);
    z-index:50; display:flex; flex-direction:column; gap:16px;
  }
  .side-nav a{
    width:9px; height:9px; border-radius:50%;
    background:var(--navy-600); border:1px solid var(--navy-600);
    transition:all .35s var(--ease);
  }
  .side-nav a.active, .side-nav a:hover{
    background:var(--gold-500); border-color:var(--gold-500);
    box-shadow:0 0 12px var(--gold-500);
  }
  @media (max-width:900px){ .side-nav{display:none;} }

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
  .hero{
    display:block; min-height:0; padding:0;
    background:var(--navy-950);
  }
  .hero > .hero-inner{display:none;}
  .hero > a{display:block;}
  .hero-image{width:100%; height:auto; display:block;}
  .hero-inner{position:relative; z-index:2;}
  .road-wrap{position:relative; width:100%; max-width:1200px; margin:0 auto 56px; aspect-ratio:16/8.4;}
  .road-wrap svg{width:100%; height:100%;}
  .pulse-ring{ animation:pulseRing 3.2s ease-in-out infinite; transform-origin:center; }
  @keyframes pulseRing{
    0%,100%{opacity:.55; transform:scale(1);}
    50%{opacity:1; transform:scale(1.06);}
  }
  .flow-line{ stroke-dasharray:6 10; animation:flowMove 2.4s linear infinite; }
  @keyframes flowMove{ to{ stroke-dashoffset:-64; } }

  .hero-copy{
    display:grid; grid-template-columns:1fr auto 1fr; align-items:start; gap:36px;
  }
  .hero-copy .col{max-width:420px;}
  .hero-copy .col.en{text-align:left;}
  .hero-copy .col.fa{text-align:right; margin-inline-start:auto;}
  .hero-copy h1{font-size:clamp(28px, 3.4vw, 42px); line-height:1.22; margin:18px 0 20px;}
  .hero-copy p{color:var(--text-mid); font-size:15px; margin-bottom:26px;}
  .hero-divider{width:1px; align-self:stretch; background:linear-gradient(var(--gold-700), transparent);}
  .hero-tagline{
    text-align:center; margin-top:64px; color:var(--text-lo); font-size:14px;
    display:flex; flex-direction:column; align-items:center; gap:14px;
  }
  .infinity{width:46px; height:22px;}
  @media (max-width:860px){
    .hero-copy{grid-template-columns:1fr;}
    .hero-copy .col.fa{text-align:right; margin:0;}
    .hero-copy .col.en{text-align:left; order:2;}
    .hero-divider{display:none;}
  }

  /* ================= ABOUT BANNER ================= */
  #about{
    padding:70px 0;
    overflow:hidden;
    background:
      radial-gradient(circle at 16% 48%, rgba(19,51,82,.18), transparent 38%),
      #030a13;
  }
  .about-banner{
    position:relative;
    display:flex;
    direction:ltr;
    align-items:stretch;
    width:100%;
    min-height:460px;
    overflow:hidden;
    background:#040b16;
  }
  .about-banner::before, .about-banner::after{
    display:none;
  }
  .about-banner-radar{
    position:absolute; left:-80px; top:50%; width:390px; height:390px;
    transform:translateY(-50%);
    pointer-events:none; opacity:.22;
  }
  .about-banner-text{
    position:relative; z-index:2; flex:0 0 40%;
    direction:rtl;
    padding:58px clamp(42px,5vw,92px);
    display:flex; flex-direction:column; justify-content:center; align-items:flex-start;
    text-align:right;
  }
  .about-banner-text .eyebrow{display:none;}
  .about-banner-text h2{font-size:clamp(32px,3.2vw,46px); margin:0 0 28px;}
  .about-banner-text p{
    color:var(--text-hi);
    font-size:clamp(15px,1.35vw,20px);
    line-height:2.15;
    margin-bottom:34px;
    max-width:36ch;
  }
  .about-banner-text .btn{min-width:210px; justify-content:center;}
  .about-banner-media{position:relative; flex:0 0 60%; min-height:460px;}
  .about-banner-media img{position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block;}
  .about-banner-media::before{
    content:''; position:absolute; inset:0;
    background:
      linear-gradient(90deg, #040b16 0%, rgba(4,11,22,.55) 8%, transparent 24%),
      linear-gradient(180deg, rgba(3,9,18,.08), rgba(3,9,18,.18));
    z-index:1; pointer-events:none;
  }
  .about-banner-media::after{
    content:''; position:absolute; inset:0;
    box-shadow:inset 0 0 70px rgba(0,0,0,.32);
    z-index:1; pointer-events:none;
  }
  @media (max-width:860px){
    #about{padding:48px 0;}
    .about-banner{flex-direction:column; min-height:0;}
    .about-banner-text{
      flex:none;
      order:2;
      width:100%;
      padding:38px 24px 44px;
      align-items:stretch;
    }
    .about-banner-text h2{font-size:32px;}
    .about-banner-text p{font-size:15px; max-width:none;}
    .about-banner-text .btn{align-self:flex-start;}
    .about-banner-media{order:1; flex:none; width:100%; min-height:280px;}
    .about-banner-media::before{
      background:linear-gradient(180deg, transparent 62%, #040b16 100%);
    }
  }

  /* ================= ABOUT (legacy grid, unused) ================= */
  .about-grid{
    display:grid; grid-template-columns:.9fr 1.1fr; gap:64px; align-items:center;
  }
  .about-card{
    background:var(--navy-900); border:1px solid var(--navy-700); border-radius:var(--radius-lg);
    padding:56px 48px;
  }
  .about-card h2{font-size:clamp(26px,3vw,36px); margin:16px 0 22px;}
  .about-card p{color:var(--text-mid); font-size:15.5px; margin-bottom:34px; max-width:46ch;}
  .about-visual{
    position:relative; border-radius:var(--radius-lg); overflow:hidden;
    background:radial-gradient(ellipse at 60% 40%, #132845, #0a1526 75%);
    border:1px solid var(--navy-700); aspect-ratio:4/3; min-height:340px;
  }
  .about-visual svg{position:absolute; inset:0; width:100%; height:100%;}
  .node-label{
    position:absolute; font-family:'Space Grotesk',sans-serif; font-size:11px; color:var(--gold-300);
    background:rgba(5,11,22,.75); border:1px solid var(--navy-600); border-radius:8px; padding:5px 10px;
  }
  @media (max-width:860px){ .about-grid{grid-template-columns:1fr;} .about-card{padding:36px 28px;} }

  /* ================= ECOSYSTEM ================= */
  #ecosystem{position:relative; isolation:isolate; padding-top:100px; padding-bottom:110px; height:auto;}
  .eco-atmosphere{position:absolute; inset:0; z-index:0; pointer-events:none; overflow:hidden;}
  .eco-atmosphere .layer-base{
    position:absolute; inset:0;
    background:
      radial-gradient(ellipse 58% 46% at 50% 36%, rgb(70 128 206 / 0%), transparent 68%), linear-gradient(180deg, #04070d 0%, #081222 26%, #162e4d33 52%, #071018 80%, #03060c 100%);
  }
  .eco-atmosphere .layer-vignette{
    position:absolute; inset:0;
    background:radial-gradient(ellipse 75% 70% at 50% 44%, transparent 45%, rgba(2,5,10,.55) 100%);
  }
  .eco-atmosphere .layer-grid{
    position:absolute; inset:0; opacity:.26;
    background-image:
      linear-gradient(rgba(120,165,225,.14) 1px, transparent 1px),
      linear-gradient(90deg, rgba(120,165,225,.14) 1px, transparent 1px);
    background-size:46px 46px;
    -webkit-mask-image:radial-gradient(ellipse 62% 58% at 50% 42%, #000 0%, transparent 78%);
    mask-image:radial-gradient(ellipse 62% 58% at 50% 42%, #000 0%, transparent 78%);
  }
  .eco-atmosphere .layer-diagonal{
    position:absolute; inset:0; opacity:.1;
    background-image:repeating-linear-gradient(58deg, rgba(217,169,64,.5) 0 1px, transparent 1px 90px);
    -webkit-mask-image:radial-gradient(ellipse 60% 55% at 50% 40%, #000 0%, transparent 75%);
    mask-image:radial-gradient(ellipse 60% 55% at 50% 40%, #000 0%, transparent 75%);
  }
  .eco-atmosphere .layer-skyline{position:absolute; left:0; right:0; bottom:0; width:100%; height:34%; opacity:.42;}
  .eco-atmosphere .layer-glow{
    position:absolute; left:50%; top:40%; width:58%; height:58%; transform:translate(-50%,-50%);
    background:radial-gradient(circle, rgba(96,156,232,.14) 0%, rgba(96,156,232,.05) 45%, transparent 72%);
    filter:blur(6px);
  }

  .eco-head{position:relative; z-index:1; text-align:center; max-width:760px; margin:0 auto 20px;}
  .eco-head h2{font-size:clamp(30px,3.8vw,46px); margin:0 0 14px;}
  .eco-head .sub{font-size:clamp(17px,2vw,21px); font-weight:700; color:var(--text-hi); margin-bottom:16px;}
  .eco-head .gold-rule{margin:0 auto 22px;}
  .eco-head p{color:var(--text-mid); font-size:15px; line-height:1.85; max-width:620px; margin:0 auto;}

  .eco-wrap{position:relative; z-index:1; width:100%; max-width:1120px; aspect-ratio:1100/660; margin:34px auto 90px;}
  .eco-wrap svg.rings{position:absolute; inset:0; width:100%; height:100%; overflow:visible;}
  .rings-mobile{display:none;}
  .rings-mobile-v2{display:none;}

  /* animated glow along the connective paths */
  .eco-line{ stroke-dasharray:3 8; animation:ecoFlow 3.6s linear infinite; }
  @keyframes ecoFlow{ to{ stroke-dashoffset:-44; } }
  .eco-halo{ animation:ecoBreath 4.6s ease-in-out infinite; transform-origin:center; }
  @keyframes ecoBreath{ 0%,100%{opacity:.5;} 50%{opacity:.85;} }
  .eco-spin{ animation:ecoSpin 50s linear infinite; transform-origin:550px 320px; }
  .eco-spin-rev{ animation:ecoSpinRev 70s linear infinite; transform-origin:550px 320px; }
  @keyframes ecoSpin{ to{ transform:rotate(360deg); } }
  @keyframes ecoSpinRev{ to{ transform:rotate(-360deg); } }
  .eco-twinkle{ animation:ecoTwinkle 2.6s ease-in-out infinite; }
  .eco-twinkle:nth-child(2n){ animation-delay:.6s; }
  .eco-twinkle:nth-child(3n){ animation-delay:1.2s; }
  @keyframes ecoTwinkle{ 0%,100%{opacity:.4;} 50%{opacity:1;} }

  .eco-hub{
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
    width:23%; height:38.4%; border-radius:50%;
    background:radial-gradient(circle at 38% 28%, #17304f, #060d1a 74%);
    border:1.5px solid var(--gold-500);
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px;
    box-shadow:0 0 4px rgb(255 194 0), 0 0 13px rgb(255 255 255), 0 0 52px rgb(255 175 0), inset 0 0 26px rgb(0 0 0 / 60%);
    z-index:3;
  }
  .eco-hub::after{
    content:''; position:absolute; inset:-4px; border-radius:50%;
    border:1px solid rgba(249,237,199,.45); pointer-events:none;
  }
  .eco-hub img{width:66%;}
  .eco-hub .en{font-size:clamp(22px,2.6vw,34px); font-weight:700; letter-spacing:.04em;}
  .eco-hub small{font-size:9px; letter-spacing:.2em; color:var(--gold-300); font-family:'Space Grotesk',sans-serif;}

  .eco-node{
    position:absolute; width:14.4%; height:24%; border-radius:50%;
    transform:translate(-50%,-50%);
    background:radial-gradient(circle at 30% 24%, #17304f 0%, #0c1a2e 46%, #060d18 78%, #04080f 100%);
    border:1.5px solid rgba(217,169,64,.85);
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px;
    text-align:center;
    box-shadow:0 0 18px rgb(255 187 36), 0 0 40px rgb(255 212 117 / 38%), inset 0 0 22px rgba(0, 0, 0, .6), inset 0 1px 0 rgba(255, 255, 255, .06);
    transition:transform .35s var(--ease), box-shadow .35s var(--ease);
    z-index:2;
  }
  .eco-node::before{
    content:''; position:absolute; inset:-5px; border-radius:50%;
    border:1px solid rgba(217,169,64,.36); pointer-events:none;
  }
  .eco-node::after{
    content:''; position:absolute; inset:-11px; border-radius:50%;
    border:1px dashed rgba(217,169,64,.08); pointer-events:none;
  }
  .eco-node:hover{transform:translate(-50%,-52%); box-shadow:0 0 26px rgba(217,169,64,.4), 0 0 52px rgba(217,169,64,.18), inset 0 0 22px rgba(0,0,0,.6);}
  .eco-node .icon{width:33%; aspect-ratio:1; display:flex; align-items:center; justify-content:center;}
  .eco-node .icon img{width:100%; height:100%; object-fit:contain; filter:drop-shadow(0 0 8px rgba(217,169,64,.35));}
  .eco-node .icon svg{width:100%; height:100%; stroke:var(--gold-300);}
  .eco-node h4{font-size:clamp(14px,1.7vw,17px); color:var(--text-hi); font-weight:500; margin:0px 0 -10px;}
  .eco-node p{font-size:clamp(10.5px,1.15vw,11.5px); font-weight:400; color:var(--text-mid); line-height:1.65;}
  .eco-node .site{color:var(--gold-300); font-size:clamp(10.5px,1.15vw,13.5px); direction:ltr; display:inline-block;}

  .pos-tl{left:19%; top:27%;}
  .pos-tr{left:81%; top:27%;}
  .pos-ml{left:13%; top:50%;}
  .pos-mr{left:87%; top:50%;}
  .pos-bl{left:19%; top:73%;}
  .pos-br{left:81%; top:73%;}

  .eco-ready{
    position:relative; z-index:1;
    display:flex; align-items:center; justify-content:center; gap:16px;
    color:var(--text-mid); font-size:14px; margin-bottom:30px;
  }
  .eco-ready .ln{width:56px; height:1px; background:linear-gradient(90deg, var(--gold-600), transparent);}
  .eco-cta{position:relative; z-index:1; display:flex; gap:16px; justify-content:center; flex-wrap:wrap;}

  /* ---- ECOSYSTEM: dedicated mobile layout (not a scaled-down desktop) ---- */
  .eco-node h4 br{display:none;}

  @media (max-width:768px){
    #ecosystem{
      padding:18px 0 24px;
      min-height:0;
      background-position:center;
      background-size:cover;
    }

    .eco-head,
    .eco-ready,
    .eco-cta{display:none;}

    #ecosystem > .container{padding-inline:6px;}

    .eco-wrap{
      aspect-ratio:626/531;
      height:auto;
      min-height:0;
      width:100%;
      max-width:626px;
      margin:0 auto;
    }

    .rings-desktop{display:none;}
    .rings-mobile{display:none;}
    .rings-mobile-v2{display:block;}

    .eco-hub{
      width:27%;
      height:31.85%;
      min-height:0;
      border-width:2px;
      box-shadow:
        0 0 4px rgba(249,237,199,.7),
        0 0 16px rgba(217,169,64,.62),
        0 0 38px rgba(217,169,64,.25),
        inset 0 0 20px rgba(0,0,0,.68);
    }
    .eco-hub::after{inset:-6px; border-color:rgba(249,237,199,.55);}
    .eco-hub img{width:82%;}

    .eco-node{
      width:24%;
      height:28.3%;
      min-height:0;
      padding:7px 6px;
      border-width:1.5px;
      box-shadow:
        0 0 7px rgba(249,237,199,.35),
        0 0 14px rgba(217,169,64,.32),
        inset 0 0 16px rgba(0,0,0,.68);
    }
    .eco-node::before{inset:-4px;}
    .eco-node::after{inset:-8px;}
    .eco-node .icon{
      width:clamp(24px,7vw,33px);
      height:clamp(24px,7vw,33px);
      margin-bottom:3px;
    }
    .eco-node h4{
      font-size:clamp(10px,2vw,14px); line-height:1.12; margin:0 0 -2px;
      word-break:normal; overflow-wrap:normal; white-space:normal;
    }
    .eco-node h4 br{display:inline;}
    .eco-node p{font-size:clamp(7px,2.05vw,10px); line-height:1.38;}
    .eco-node .site{font-size:clamp(7px,2vw,9px);}

    .pos-tl{left:17%; top:15%;}
    .pos-tr{left:83%; top:15%;}
    .pos-ml{left:13%; top:50%;}
    .pos-mr{left:87%; top:50%;}
    .pos-bl{left:17%; top:85%;}
    .pos-br{left:83%; top:85%;}
  }
  @media (max-width:359px){
    .eco-cta .btn{flex:1 1 100%;}
  }

  /* ================= ACADEMY BANNER ================= */
  .academy-banner{
    position:relative;
    overflow:hidden;
    padding:70px 0;
    background:
      radial-gradient(circle at 72% 45%, rgba(17,45,72,.22), transparent 38%),
      #030912;
  }
  .academy-banner-shell{
    position:relative;
    width:calc(100% - 64px);
    max-width:1600px;
    min-height:500px;
    margin:0 auto;
    display:grid;
    grid-template-columns:minmax(0,4fr) minmax(360px,5fr);
    overflow:hidden;
    background:#040b16;
  }
  .academy-banner-media{
    position:relative;
    min-width:0;
    background-size:cover;
    background-repeat:no-repeat;
    background-position:center;
  }
  .academy-banner-fade{
    position:absolute;
    inset:0;
    pointer-events:none;
    background:linear-gradient(90deg, transparent 0%, transparent 72%, rgba(4,11,22,.38) 88%, #040b16 100%);
  }
  .academy-banner-dots{
    position:absolute; right:0; bottom:0; width:260px; height:190px; z-index:1; opacity:.3;
    background-image:radial-gradient(rgba(217,169,64,.65) 1px, transparent 1.5px);
    background-size:14px 14px;
    -webkit-mask-image:linear-gradient(135deg, transparent 25%, #000 65%);
    mask-image:linear-gradient(135deg, transparent 25%, #000 65%);
    pointer-events:none;
  }
  .academy-banner-content{
    position:relative;
    z-index:2;
    min-width:0;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:56px 48px;
  }
  .academy-banner-text{width:100%; max-width:410px; text-align:right;}
  .academy-banner-text h2{font-size:clamp(30px,3.6vw,44px); margin:0 0 16px;}
  .academy-banner-text .sub{font-size:clamp(18px,2vw,22px); font-weight:700; color:var(--text-hi); margin-bottom:10px;}
  .academy-banner-text .desc{color:var(--text-mid); font-size:15.5px; line-height:1.85; margin-bottom:30px;}
  @media (max-width:860px){
    .academy-banner{
      padding:12px 0 40px;
      background:#202020;
    }
    .academy-banner-shell{
      width:calc(100% - 24px);
      min-height:0;
      grid-template-columns:1fr;
      overflow:hidden;
      background:#031022;
    }
    .academy-banner-media{
      z-index:1;
      min-height:clamp(410px,118vw,520px);
      background-position:44% center;
    }
    .academy-banner-fade{
      background:linear-gradient(180deg, transparent 72%, rgba(3,16,34,.15) 90%, #031022 100%);
    }
    .academy-banner-content{
      z-index:2;
      min-height:380px;
      margin-top:-48px;
      padding:92px 22px 46px;
      border-top:3px solid #e3ad3f;
      border-radius:50% 50% 0 0 / 58px 58px 0 0;
      background:
        radial-gradient(circle at 50% 0%, rgba(217,169,64,.09), transparent 31%),
        linear-gradient(180deg,#031022 0%,#020c1c 100%);
      box-shadow:0 -5px 18px rgba(217,169,64,.12);
    }
    .academy-banner-content::before,
    .academy-banner-content::after{display:none;}
    .academy-banner-dots{
      right:0;
      bottom:0;
      width:100%;
      height:58%;
      opacity:.2;
    }
    .academy-banner-text{
      position:relative;
      z-index:2;
      margin:0 auto;
      max-width:none;
      text-align:center;
    }
    .academy-banner-text h2{
      font-size:clamp(38px,11vw,50px);
      line-height:1.25;
      margin-bottom:26px;
    }
    .academy-banner-text .sub{
      font-size:clamp(18px,5.2vw,22px);
      margin-bottom:12px;
    }
    .academy-banner-text .desc{
      font-size:clamp(14px,4.2vw,18px);
      font-weight:600;
      color:var(--text-hi);
      margin-bottom:32px;
    }
    .academy-banner-text .btn{
      min-width:210px;
      min-height:58px;
      justify-content:center;
      font-size:16px;
      font-weight:700;
      border-radius:10px;
      box-shadow:0 8px 20px rgba(0,0,0,.3), 0 0 18px rgba(217,169,64,.12);
    }
  }

  /* ================= ACADEMY DASHBOARD ================= */
  .academy-grid{display:grid; grid-template-columns:1.1fr .9fr; gap:64px; align-items:center;}
  .academy-visual{
    position:relative; border-radius:var(--radius-lg); border:1px solid var(--navy-700);
    background:linear-gradient(160deg,#0f2038,#081222); padding:26px; overflow:hidden;
  }
  .academy-visual::before{
    content:''; position:absolute; inset:0; opacity:.5;
    background:radial-gradient(circle at 15% 15%, rgba(217,169,64,.14), transparent 55%);
  }
  .dash-top{display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; position:relative;}
  .dash-top .en{font-size:13px; letter-spacing:.1em; color:var(--gold-300);}
  .dash-grid{display:grid; grid-template-columns:1fr 1fr; gap:14px; position:relative;}
  .dash-card{background:rgba(255,255,255,.02); border:1px solid var(--navy-700); border-radius:12px; padding:16px;}
  .dash-card h5{font-size:12px; color:var(--text-lo); font-weight:500; margin-bottom:10px;}
  .donut{
    width:96px; height:96px; border-radius:50%; margin:0 auto;
    background:conic-gradient(var(--gold-500) 0 75%, var(--navy-700) 75% 100%);
    display:flex; align-items:center; justify-content:center;
  }
  .donut span{
    width:70px; height:70px; border-radius:50%; background:#0d1c30;
    display:flex; align-items:center; justify-content:center;
    font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:16px; color:var(--gold-300);
  }
  .bar-row{display:flex; align-items:center; gap:8px; margin-top:10px; font-size:11px; color:var(--text-lo);}
  .bar-track{flex:1; height:5px; background:var(--navy-700); border-radius:4px; overflow:hidden;}
  .bar-fill{height:100%; background:linear-gradient(90deg, var(--gold-600), var(--gold-300));}
  .academy-copy h2{font-size:clamp(26px,3vw,36px); margin:16px 0 16px;}
  .academy-copy .sub{color:var(--gold-300); font-size:15px; margin-bottom:14px;}
  .academy-copy p{color:var(--text-mid); font-size:15px; margin-bottom:30px; max-width:44ch;}
  @media (max-width:900px){ .academy-grid{grid-template-columns:1fr;} .dash-grid{grid-template-columns:1fr 1fr;} }

  /* ================= APPS ================= */
  #apps{position:relative; overflow:hidden;}
  .apps-bg{position:absolute; inset:0; z-index:0; pointer-events:none;}
  .apps-bg .layer-base{
    position:absolute; inset:0;
    background:
      radial-gradient(ellipse 52% 38% at 50% 16%, rgba(217,169,64,.09), transparent 72%),
      linear-gradient(180deg, #030609 0%, #060d18 32%, #0a1526 58%, #04070c 100%);
  }
  .apps-bg .layer-grid{
    position:absolute; inset:0; opacity:.12;
    background-image:
      linear-gradient(rgba(120,165,225,.14) 1px, transparent 1px),
      linear-gradient(90deg, rgba(120,165,225,.14) 1px, transparent 1px);
    background-size:52px 52px;
    -webkit-mask-image:radial-gradient(ellipse 55% 50% at 50% 22%, #000 0%, transparent 78%);
    mask-image:radial-gradient(ellipse 55% 50% at 50% 22%, #000 0%, transparent 78%);
  }
  .apps-bg .layer-glow{
    position:absolute; left:50%; top:6%; width:48%; height:38%; transform:translate(-50%,0);
    background:radial-gradient(circle, rgba(217,169,64,.16) 0%, transparent 70%);
    filter:blur(18px);
  }

  .apps-wide{position:relative; z-index:1; width:calc(100% - 70px); max-width:1680px; margin:0 auto;}

  .apps-head{text-align:center; max-width:820px; margin:0 auto 60px;}
  .apps-head h2{font-size:clamp(30px,4.4vw,56px); margin:16px 0 16px;}
  .apps-head p{color:var(--text-hi); font-size:clamp(17px,2vw,23px); font-weight:500;}

  .apps-grid{display:grid; grid-template-columns:repeat(3,1fr); gap:16px; direction:ltr;}

  .app-card{
    direction:ltr; position:relative; overflow:hidden; display:flex; align-items:stretch;
    height:580px; border-radius:22px;
    border:1px solid rgba(217,169,64,.45);
    background:
      radial-gradient(circle at bottom center, rgba(22,51,78,.22), transparent 45%),
      linear-gradient(145deg, #07111e, #030a13);
    box-shadow:0 20px 40px rgba(0,0,0,.4), 0 30px 60px -22px rgba(217,169,64,.14);
    transition:transform .4s var(--ease), border-color .4s var(--ease);
  }
  .app-card::before{
    content:''; position:absolute; inset:0; pointer-events:none;
    background:
      radial-gradient(circle at 10% 6%, rgba(217,169,64,.16), transparent 32%),
      radial-gradient(circle at 92% 6%, rgba(217,169,64,.1), transparent 28%);
  }
  .app-card:hover{transform:translateY(-6px); border-color:var(--gold-500);}

  .app-card-text{
    position:relative; z-index:2; flex:0 0 54%;
    direction:rtl; text-align:right;
    display:flex; flex-direction:column; justify-content:space-between;
    padding:32px 10px 28px 16px;
  }
  .app-card h3{direction:ltr; text-align:right;}
  .app-card h3 .en{font-size:clamp(24px,2.2vw,34px); font-weight:600;}
  .app-card h3 .en.gold-text{margin-inline-end:6px;}
  .app-card .tag{color:var(--text-hi); font-size:clamp(18px,1.7vw,24px); font-weight:500; margin-top:14px;}
  .app-card .desc{color:var(--text-mid); font-size:clamp(15px,1.35vw,20px); line-height:1.6; margin-top:8px;}

  .app-card-phone{
    position:relative; z-index:1; flex:0 0 46%;
    display:flex; align-items:flex-end; justify-content:flex-end;
  }
  .app-card-phone img{
    width:100%; height:520px; max-height:100%;
    object-fit:contain; object-position:bottom right;
    filter:drop-shadow(0 14px 22px rgba(0,0,0,.32));
    display:block;
  }

  .store-col{display:flex; flex-direction:column; gap:clamp(6px,.8vw,9px); align-items:flex-start; flex:none; min-width:0;}
  .store-btn{
    direction:ltr; text-align:left;
    display:flex; align-items:center; gap:clamp(6px,.8vw,9px);
    padding:clamp(5px,.6vw,8px) clamp(8px,1vw,14px); border-radius:10px;
    border:1px solid var(--navy-600); background:rgba(255,255,255,.02);
  }
  .store-btn img{width:clamp(17px,1.7vw,22px); height:clamp(17px,1.7vw,22px); object-fit:contain; flex:none;}
  .store-btn .st-text{display:flex; flex-direction:column; line-height:1.3; white-space:nowrap;}
  .store-btn .st-text small{font-size:clamp(7.5px,.75vw,9px); color:var(--text-lo);}
  .store-btn .st-text strong{font-size:clamp(11px,1.15vw,14px); font-weight:600; color:var(--text-hi);}
  .qr-block{display:flex; align-items:center; gap:clamp(8px,1.1vw,14px); direction:ltr;}
  .qr{width:clamp(78px,8vw,112px); height:clamp(78px,8vw,112px); border-radius:8px; object-fit:cover; flex:none;}
  .get-btn{
    justify-content:center; width:clamp(158px,15vw,190px); height:clamp(48px,4.4vw,55px); max-width:100%;
    border-radius:11px; background:transparent; border:1.5px solid var(--gold-600); color:var(--gold-300);
  }
  .get-btn:hover{background:var(--gold-500); color:var(--navy-950);}

  @media (max-width:1240px){
    .apps-wide{width:calc(100% - 40px);}
    .apps-grid{grid-template-columns:repeat(2,1fr);}
  }
  @media (max-width:720px){
    #apps{padding-block:82px;}
    .apps-wide{width:calc(100% - 24px);}
    .apps-head{margin-bottom:34px; padding-inline:10px;}
    .apps-head h2{font-size:clamp(30px,9vw,40px); margin:12px 0 8px;}
    .apps-head p{font-size:15px;}

    .apps-grid{
      grid-template-columns:1fr;
      gap:16px;
    }

    .app-card{
      display:grid;
      grid-template-columns:minmax(0,57%) minmax(0,43%);
      direction:ltr;
      align-items:stretch;
      height:430px;
      min-width:0;
      border-radius:24px;
      border-color:rgba(217,169,64,.72);
      background:
        radial-gradient(circle at 92% 78%, rgba(28,67,104,.38), transparent 46%),
        radial-gradient(circle at 12% 4%, rgba(217,169,64,.14), transparent 34%),
        linear-gradient(145deg,#0b192a 0%,#06111f 52%,#030912 100%);
      box-shadow:
        inset 0 1px 0 rgba(255,255,255,.025),
        0 18px 38px rgba(0,0,0,.38),
        0 0 30px rgba(217,169,64,.05);
    }

    .app-card:hover{transform:none;}

    .app-card-text{
      grid-column:1;
      min-width:0;
      padding:24px 0 20px 18px;
      direction:rtl;
      text-align:right;
      justify-content:space-between;
    }

    .app-card h3{
      direction:ltr;
      text-align:left;
      white-space:nowrap;
      line-height:1.15;
    }
    .app-card h3 .en{font-size:clamp(19px,6vw,25px);}
    .app-card h3 .en.gold-text{margin-inline-end:5px;}
    .app-card .tag{
      margin-top:12px;
      font-size:clamp(15px,4.5vw,18px);
      line-height:1.45;
    }
    .app-card .desc{
      margin-top:5px;
      font-size:clamp(11px,3.35vw,14px);
      line-height:1.65;
      white-space:nowrap;
    }

    .qr-block{
      width:100%;
      gap:7px;
      align-items:center;
    }
    .qr{width:64px; height:64px; border-radius:7px;}
    .store-col{gap:5px; flex:1;}
    .store-btn{
      width:100%;
      max-width:104px;
      min-height:29px;
      gap:5px;
      padding:4px 6px;
      border-radius:7px;
      background:rgba(255,255,255,.025);
    }
    .store-btn img{width:15px; height:15px;}
    .store-btn .st-text small{font-size:6px;}
    .store-btn .st-text strong{font-size:9px;}

    .get-btn{
      width:142px;
      height:44px;
      padding:9px 16px;
      border-radius:10px;
      font-size:13px;
      align-self:flex-start;
    }

    .app-card-phone{
      grid-column:2;
      min-width:0;
      align-items:flex-end;
      justify-content:flex-end;
      padding:0 5px 10px 0;
      overflow:visible;
    }
    .app-card-phone img{
      width:auto;
      max-width:none;
      height:338px;
      max-height:calc(100% - 34px);
      object-fit:contain;
      object-position:bottom right;
      filter:drop-shadow(-10px 15px 18px rgba(0,0,0,.5));
    }
  }

  @media (max-width:380px){
    .apps-wide{width:calc(100% - 20px);}
    .app-card{
      grid-template-columns:minmax(0,59%) minmax(0,41%);
      height:418px;
    }
    .app-card-text{padding:22px 0 18px 14px;}
    .app-card h3 .en{font-size:19px;}
    .app-card .tag{font-size:15px;}
    .app-card .desc{font-size:11px;}
    .qr{width:60px; height:60px;}
    .store-btn{max-width:96px;}
    .get-btn{width:132px; height:42px;}
    .app-card-phone img{height:315px;}
  }


</style>

<script>
  // scroll reveal
  const revealEls = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target);} });
  }, {threshold:.15});
  revealEls.forEach(el=>io.observe(el));

  // active side-nav dot
  const sections = ['hero','about','ecosystem','academy','apps'].map(id=>document.getElementById(id));
  const dots = document.querySelectorAll('.side-nav a');
  const navIO = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){
        const idx = sections.indexOf(e.target);
        dots.forEach(d=>d.classList.remove('active'));
        if(dots[idx]) dots[idx].classList.add('active');
      }
    });
  }, {threshold:.5});
  sections.forEach(s=>{ if(s) navIO.observe(s); });

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.pulse-ring, .flow-line').forEach(el=>{ el.style.animation='none'; });
  }
</script>

@endsection