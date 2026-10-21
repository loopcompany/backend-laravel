{{-- سوابق همکاری: بلافاصله بعد از بخش دانلود نسخه ویندوز در صفحه اصلی --}}
<section id="partnership">
  <div class="partner-wide">
    <div class="partner-head reveal">
      <span class="eyebrow rule-center">همکاری‌ها</span>
      <h2>سوابق <span class="gold-text">همکاری</span></h2>
    </div>

    <div class="partner-banner reveal">
      <img src="{{ asset('assets/new-style/partnership.jpg') }}" width="1672" height="941" loading="lazy"
           alt="سوابق همکاری لوپ — دست دادن دو مدیر در دفتر لوپ با چشم‌انداز تهران">
    </div>

    {{-- فقط نام و لوگو؛ لینک سایت پشت لوگو است و نمایش داده نمی‌شود (همکاران از پنل › مدیریت محتوا › همکاران) --}}
    @if (!empty($partners) && $partners->isNotEmpty())
      <ul class="partner-grid reveal">
        @foreach ($partners as $partner)
          <li class="partner-item">
            <a href="{{ $partner->website_url }}" target="_blank" rel="noopener" class="partner-logo" aria-label="سایت {{ $partner->name }}">
              <img src="{{ asset('storage/' . $partner->logo) }}" alt="لوگوی {{ $partner->name }}" loading="lazy">
            </a>
            <span class="partner-name">{{ $partner->name }}</span>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</section>

<style>
  #partnership{position:relative; padding:0 0 120px; background:#04070c;}
  .partner-wide{width:calc(100% - 70px); max-width:1280px; margin:0 auto;}
  .partner-head{text-align:center; margin:0 auto 36px;}
  .partner-head h2{font-size:clamp(30px,4.4vw,56px); margin:16px 0 0;}

  .partner-banner{
    position:relative; border-radius:22px; overflow:hidden;
    border:1px solid rgba(217,169,64,.45);
    box-shadow:0 20px 40px rgba(0,0,0,.4), 0 30px 60px -22px rgba(217,169,64,.18);
  }
  .partner-banner img{width:100%; height:auto; display:block;}

  .partner-grid{
    list-style:none; margin:40px 0 0; padding:0; direction:rtl;
    display:flex; flex-wrap:wrap; justify-content:center; gap:28px 22px;
  }
  .partner-item{width:180px; display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center;}
  .partner-logo{
    display:grid; place-items:center; width:100%; aspect-ratio:1/1; padding:18px; border-radius:18px;
    background:#fff; border:1px solid rgba(217,169,64,.45);
    box-shadow:0 14px 30px -14px rgba(0,0,0,.6);
    transition:transform .35s var(--ease), box-shadow .35s var(--ease), border-color .35s var(--ease);
  }
  .partner-logo img{max-width:100%; max-height:100%; object-fit:contain;}
  .partner-logo:hover{transform:translateY(-4px); border-color:var(--gold-500); box-shadow:0 22px 40px -16px rgba(217,169,64,.45);}
  .partner-logo:focus-visible{outline:2px solid var(--gold-300); outline-offset:4px;}
  .partner-name{font-size:15px; font-weight:600; color:var(--text-hi); line-height:1.6;}

  @media (max-width:1100px){
    .partner-wide{width:calc(100% - 40px);}
  }
  @media (max-width:600px){
    #partnership{padding:0 0 80px;}
    .partner-wide{width:calc(100% - 32px);}
    .partner-head{margin-bottom:24px;}
    .partner-head h2{font-size:clamp(28px,9vw,38px); margin-top:12px;}
    .partner-banner{border-radius:14px;}
    .partner-grid{margin-top:28px; gap:20px 14px;}
    .partner-item{width:calc(50% - 7px); max-width:170px;}
    .partner-logo{padding:14px; border-radius:14px;}
    .partner-name{font-size:13.5px;}
  }
</style>
