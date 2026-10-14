@php
  // فایل‌های نصب ویندوز روی سرور در public/downloads/windows قرار می‌گیرند (در گیت و ایمیج داکر نیستند)
  $windowsVersion = '1.1.1';
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
  $winIcon = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5.1 10.4 4v7.1H3V5.1Zm0 13.8 7.4 1.1v-7H3v5.9ZM11.3 3.9 21 2.5v8.6h-9.7V3.9Zm0 16.2 9.7 1.4v-8.5h-9.7v7.1Z"/></svg>';
@endphp
<section id="windows-app">
  <div class="win-wide">
    <div class="win-banner reveal">
      <img src="{{ asset('assets/new-style/windows-banner.jpg') }}" width="1280" height="640"
           alt="نسخه نرم‌افزار ویندوز لوپ — تجربه‌ی یکپارچه از خدمات و امکانات لوپ، روی رایانه شما">
      {{-- ناحیه‌ی کلیک روی دکمه‌ی «دانلود نسخه ویندوز» داخل تصویر --}}
      <a href="#windows-downloads" class="win-banner-hotspot" aria-label="دانلود نسخه ویندوز"></a>
    </div>

    <div id="windows-downloads" class="win-downloads reveal">
      @foreach ($windowsBuilds as $build)
        <a href="{{ $build['url'] }}" download class="win-dl {{ $loop->first ? 'is-suggested' : '' }}" data-build="{{ $build['key'] }}">
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
  /* مختصات دکمه‌ی طلایی در تصویر ۱۲۸۰×۶۴۰ */
  .win-banner-hotspot{position:absolute; left:7.2%; top:65%; width:20.5%; height:9%; border-radius:999px;}
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
  // پیشنهاد نسخه‌ی مناسب بر اساس ویندوز بازدیدکننده
  (function () {
    const ua = navigator.userAgent;
    if (!/Windows NT/.test(ua)) return;
    const legacy = /Windows NT (6\.[0-3])/.test(ua);
    const is64 = /Win64|WOW64|x64|amd64/i.test(ua);
    const key = (legacy ? 'win7-' : 'win10-') + (is64 ? '64' : '32');
    const target = document.querySelector('.win-dl[data-build="' + key + '"]');
    if (!target) return;
    document.querySelectorAll('.win-dl.is-suggested').forEach(el => el.classList.remove('is-suggested'));
    target.classList.add('is-suggested');
  })();
</script>
