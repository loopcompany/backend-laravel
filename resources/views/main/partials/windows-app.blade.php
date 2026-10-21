@php
  // فایل‌های نصب ویندوز روی سرور در public/downloads/windows قرار می‌گیرند (در گیت و ایمیج داکر نیستند)
  $windowsVersion = '1.1.3';
  $faDigits = fn ($v) => strtr((string) $v, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
  $windowsBuilds = collect([
    ['key' => 'win10-64', 'os' => 'ویندوز ۱۰ و ۱۱', 'arch' => '۶۴ بیتی', 'file' => "Loop-{$windowsVersion}-Windows-10-11-64bit-Setup.exe"],
    ['key' => 'win10-32', 'os' => 'ویندوز ۱۰ و ۱۱', 'arch' => '۳۲ بیتی', 'file' => "Loop-{$windowsVersion}-Windows-10-11-32bit-Setup.exe"],
    ['key' => 'win7-64',  'os' => 'ویندوز ۷ و ۸',   'arch' => '۶۴ بیتی', 'file' => "Loop-{$windowsVersion}-Windows-7-8-64bit-Setup.exe"],
    ['key' => 'win7-32',  'os' => 'ویندوز ۷ و ۸',   'arch' => '۳۲ بیتی', 'file' => "Loop-{$windowsVersion}-Windows-7-8-32bit-Setup.exe"],
  ])->map(function ($build) use ($faDigits) {
    $path = public_path('downloads/windows/'.$build['file']);
    $build['url'] = asset('downloads/windows/'.$build['file']);
    $build['size'] = is_file($path) ? $faDigits(round(filesize($path) / 1048576)).' مگابایت' : null;
    return $build;
  });
  // حدس اولیه‌ی سمت سرور؛ اسکریپت پایین صفحه با navigator.userAgentData دقیق‌ترش می‌کند
  $winDetected = \App\Support\WindowsBuildDetector::detect(request());
  $suggestedKey = $winDetected['key'];
  $suggestedBuild = $windowsBuilds->firstWhere('key', $suggestedKey) ?? $windowsBuilds->first();
  $winIcon = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5.1 10.4 4v7.1H3V5.1Zm0 13.8 7.4 1.1v-7H3v5.9ZM11.3 3.9 21 2.5v8.6h-9.7V3.9Zm0 16.2 9.7 1.4v-8.5h-9.7v7.1Z"/></svg>';
@endphp
<section id="windows-app">
  <div class="win-wide">
    <div class="win-banner reveal">
      <img src="{{ asset('assets/new-style/windows-banner.jpg') }}" width="1774" height="887" loading="lazy"
           alt="نسخه نرم‌افزار ویندوز لوپ — تجربه‌ی یکپارچه از خدمات و امکانات لوپ، روی رایانه شما">
      {{-- ناحیه‌ی کلیک روی دکمه‌ی «دانلود نسخه ویندوز» داخل تصویر: روی ویندوز مستقیم نسخه‌ی پیشنهادی را دانلود می‌کند --}}
      @if ($winDetected['is_windows'])
        <a href="{{ $suggestedBuild['url'] }}" download class="win-banner-hotspot" aria-label="دانلود نسخه ویندوز (پیشنهادی)"></a>
      @else
        <a href="#windows-downloads" class="win-banner-hotspot" aria-label="دانلود نسخه ویندوز"></a>
      @endif
    </div>

    <div id="windows-downloads" class="win-downloads reveal">
      @foreach ($windowsBuilds as $build)
        <a href="{{ $build['url'] }}" download class="win-dl {{ $build['key'] === $suggestedBuild['key'] ? 'is-suggested' : '' }}" data-build="{{ $build['key'] }}">
          <span class="win-dl-icon">{!! $winIcon !!}</span>
          <span class="win-dl-text">
            <strong>{{ $build['os'] }}</strong>
            <small>{{ $build['arch'] }}@if ($build['size']) · {{ $build['size'] }}@endif</small>
          </span>
          <span class="win-dl-pill">پیشنهادی</span>
          <svg class="win-dl-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v13m0 0-4-4m4 4 4-4M5 21h14"/></svg>
        </a>
      @endforeach
    </div>

    <p class="win-detected" id="win-detected" @unless ($winDetected['is_windows']) hidden @endunless>
      سیستم شما: <b id="win-detected-os">{{ $winDetected['family'] === 'win7' ? 'ویندوز ۷ یا ۸' : 'ویندوز ۱۰ یا ۱۱' }} ({{ $winDetected['bits'] === '64' ? '۶۴' : '۳۲' }} بیتی)</b>
      — نسخه‌ی <span class="win-detected-pill">پیشنهادی</span> مناسب رایانه‌ی شما مشخص شده است.
    </p>
    <p class="win-detected win-detected--other" id="win-not-windows" @if ($winDetected['is_windows']) hidden @endif>
برای نصب نرم‌افزار، لطفاً این صفحه را در رایانه‌ای با سیستم‌عامل ویندوز باز کنید
    </p>

    <p class="win-hint">نسخه نرم‌افزار: <b>{{ $windowsVersion }}</b> · نمی‌دانید ویندوز شما ۳۲ یا ۶۴ بیتی است؟ در ویندوز به مسیر <b>Settings › System › About</b> بروید و بخش <b>System type</b> را ببینید.</p>
  </div>
</section>

<style>
  #windows-app{position:relative; padding:60px 0 120px; background:linear-gradient(180deg,#04070c,#071226 50%,#04070c);}
  .win-wide{width:calc(100% - 70px); max-width:1280px; margin:0 auto;}

  .win-banner{
    position:relative; border-radius:22px; overflow:hidden;
    border:1px solid rgba(217,169,64,.45);
    box-shadow:0 20px 40px rgba(0,0,0,.4), 0 30px 60px -22px rgba(217,169,64,.18);
  }
  .win-banner img{width:100%; height:auto; display:block;}
  /* مختصات دکمه‌ی طلایی در تصویر ۱۷۷۴×۸۸۷ (x: 128–493 ، y: 586–659) */
  .win-banner-hotspot{position:absolute; left:7.2%; top:66.1%; width:20.6%; height:8.3%; border-radius:999px;}
  .win-banner-hotspot:focus-visible{outline:2px solid var(--gold-300); outline-offset:3px;}

  .win-downloads{display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-top:26px; direction:rtl; scroll-margin-top:120px;}
  .win-dl{
    position:relative; display:flex; align-items:center; gap:12px;
    padding:14px 16px; border-radius:14px;
    border:1px solid rgba(217,169,64,.4); background:rgba(255,255,255,.02);
    color:var(--text-hi); transition:all .3s var(--ease);
  }
  .win-dl:hover{border-color:var(--gold-500); background:rgba(217,169,64,.08); transform:translateY(-2px);}
  .win-dl-icon svg{width:26px; height:26px; color:var(--gold-300); display:block;}
  .win-dl-text{display:flex; flex-direction:column; line-height:1.5; flex:1; min-width:0; text-align:right;}
  .win-dl-text strong{font-size:15px; font-weight:700;}
  .win-dl-text small{font-size:12.5px; color:var(--text-mid);}
  .win-dl-arrow{width:20px; height:20px; color:var(--gold-300); flex:none;}
  .win-dl-pill{
    display:none; position:absolute; top:-10px; left:14px;
    font-size:11px; font-weight:700; padding:1px 10px; border-radius:999px;
    background:var(--navy-950); color:var(--gold-300); border:1px solid var(--gold-500);
  }
  .win-dl.is-suggested{
    background:linear-gradient(135deg, var(--gold-300), var(--gold-600)); border-color:transparent;
    color:var(--navy-950); box-shadow:0 10px 30px -10px rgba(217,169,64,.6);
  }
  .win-dl.is-suggested .win-dl-text small,
  .win-dl.is-suggested .win-dl-icon svg,
  .win-dl.is-suggested .win-dl-arrow{color:var(--navy-950);}
  .win-dl.is-suggested .win-dl-pill{display:block;}
  .win-dl.is-suggested:hover{filter:brightness(1.06);}

  .win-detected{margin-top:22px; color:var(--text-mid); font-size:14px; text-align:center; direction:rtl;}
  .win-detected[hidden]{display:none;}
  .win-detected b{color:var(--gold-300); font-weight:700;}
  .win-detected-pill{
    display:inline-block; font-size:11.5px; font-weight:700; padding:0 10px; border-radius:999px;
    background:var(--navy-950); color:var(--gold-300); border:1px solid var(--gold-500);
  }
  .win-detected + .win-hint, .win-detected--other + .win-hint{margin-top:8px;}

  .win-hint{margin-top:16px; color:var(--text-lo); font-size:13px; text-align:center; direction:rtl;}
  .win-hint b{color:var(--text-mid); font-weight:500; direction:ltr; unicode-bidi:isolate; white-space:nowrap;}

  @media (max-width:1100px){
    .win-wide{width:calc(100% - 40px);}
    .win-downloads{grid-template-columns:repeat(2,minmax(0,1fr));}
  }
  @media (max-width:600px){
    #windows-app{padding:40px 0 80px;}
    .win-wide{width:calc(100% - 32px);}
    .win-banner{border-radius:14px;}
    .win-downloads{grid-template-columns:1fr;}
  }
</style>

<script>
  // پیشنهاد نسخه‌ی مناسب بر اساس ویندوز بازدیدکننده.
  // کروم/اج User-Agent را روی «Windows NT 10.0; Win64; x64» ثابت کرده‌اند، پس اول از Client Hints
  // (navigator.userAgentData) نسخه‌ی واقعی ویندوز و ۳۲/۶۴ بیتی بودن خوانده می‌شود و User-Agent فقط پشتیبان است.
  (function () {
    const ua = navigator.userAgent;
    const faDigits = s => String(s).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    const labels = { win7: 'ویندوز ۷ یا ۸', win10: 'ویندوز ۱۰', win11: 'ویندوز ۱۱' };

    function apply(isWindows, os, bits) {
      document.getElementById('win-detected').hidden = !isWindows;
      document.getElementById('win-not-windows').hidden = isWindows;
      if (!isWindows) return;

      const key = (os === 'win7' ? 'win7-' : 'win10-') + bits;
      const target = document.querySelector('.win-dl[data-build="' + key + '"]');
      if (!target) return;
      document.querySelectorAll('.win-dl.is-suggested').forEach(el => el.classList.remove('is-suggested'));
      target.classList.add('is-suggested');
      document.getElementById('win-detected-os').textContent = labels[os] + ' (' + faDigits(bits) + ' بیتی)';

      const hotspot = document.querySelector('.win-banner-hotspot');
      hotspot.href = target.href;
      hotspot.setAttribute('download', '');
      hotspot.setAttribute('aria-label', 'دانلود نسخه ویندوز (پیشنهادی)');
    }

    function fromUserAgent() {
      const isWindows = /Windows NT/.test(ua);
      const os = /Windows NT 6\.[0-3]/.test(ua) ? 'win7' : 'win10';
      const bits = /Win64|WOW64|x64|amd64|arm64|aarch64/i.test(ua) ? '64' : '32';
      apply(isWindows, os, bits);
    }

    const uaData = navigator.userAgentData;
    if (!uaData || !uaData.getHighEntropyValues) return fromUserAgent();

    uaData.getHighEntropyValues(['platformVersion', 'bitness', 'architecture']).then(function (v) {
      if (v.platform !== 'Windows') return apply(false);
      // platformVersion: «0.x» = ویندوز ۷/۸/۸.۱ ، 1 تا 12 = ویندوز ۱۰ ، 13 به بعد = ویندوز ۱۱
      const major = parseInt(String(v.platformVersion || '').split('.')[0], 10);
      const os = isNaN(major) ? (/Windows NT 6\.[0-3]/.test(ua) ? 'win7' : 'win10')
               : major === 0 ? 'win7' : major >= 13 ? 'win11' : 'win10';
      const bits = v.bitness === '32' ? '32'
                 : v.bitness === '64' || v.architecture === 'arm' ? '64'
                 : (/Win64|WOW64|x64/i.test(ua) ? '64' : '32');
      apply(true, os, bits);
    }).catch(fromUserAgent);
  })();
</script>
