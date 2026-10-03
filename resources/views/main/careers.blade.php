@extends('layout.main.header')
@section('meta_title', 'همکاری با لوپ | فرصت‌های شغلی')
@section('meta_description', 'فرم درخواست همکاری با لوپ برای تکنسین میدانی، تکنسین داخلی، کارمند اداری، فروش، انبار و حسابداری. آقایان و بانوان.')
@section('content')
@php
    use App\Support\Careers\CareerOptions as O;

    $submitted = session('cooperation_submitted');
    $tracked = session('cooperation_tracked');
    $trackErrors = $errors->getBag('track');

    $old = fn (string $key, $default = null) => old($key, $default);
    $checked = fn (string $key, string $value) => (string) old($key) === $value ? 'checked' : '';
@endphp

<div class="careers-page">

  <!-- ================= HERO ================= -->
  <section class="cr-hero" aria-label="همکاری با لوپ">
    <picture>
      <source media="(max-width: 767px)" srcset="{{ asset('assets/new-style/careers/hero-mobile.jpg') }}" width="941" height="1672">
      <img src="{{ asset('assets/new-style/careers/hero-desktop.jpg') }}" alt="همکاری با لوپ — آینده را با هم می‌سازیم" width="2094" height="751" fetchpriority="high">
    </picture>
  </section>

  <!-- ================= INTRO ================= -->
  <section class="cr-intro">
    <div class="cr-container">
      <div class="cr-head">
        <span class="cr-eyebrow">همکاری با لوپ</span>
        <h1>آینده را با هم می‌سازیم</h1>
        <div class="cr-rule"></div>
      </div>
      <div class="cr-lead">
        <p>در <span class="en">LOOP</span>، باور داریم که آینده فناوری با دانش، تخصص، خلاقیت و همکاری افراد توانمند شکل می‌گیرد. هدف ما ایجاد مجموعه‌ای پویا، حرفه‌ای و رو به رشد است؛ محیطی که در آن افراد بتوانند استعدادهای خود را شکوفا کنند، مهارت‌هایشان را توسعه دهند و در مسیر پیشرفت، نقشی مؤثر داشته باشند.</p>
        <p>ما به ارزش توانمندی‌های انسانی و فرصت‌های برابر در مسیر رشد حرفه‌ای باور داریم و از همکاری با آقایان و بانوان در بخش‌های مختلف مجموعه، از جمله واحدهای فنی و تکنسینی، اداری، پشتیبانی، فروش و سایر حوزه‌های تخصصی استقبال می‌کنیم.</p>
      </div>
    </div>
  </section>

  <!-- ================= OPPORTUNITIES ================= -->
  <section class="cr-opportunity">
    <div class="cr-container cr-split">
      <div class="cr-split-text">
        <span class="cr-eyebrow">فرصت‌های تازه</span>
        <h2>فرصت‌های تازه در دنیای فناوری</h2>
        <p>در راستای توسعه فعالیت‌های <span class="en">LOOP</span>، زمینه همکاری بانوان در بخش تکنسینی رایانه نیز فراهم شده است؛ فرصتی برای حضور در حوزه‌های تخصصی فناوری، کسب تجربه، توسعه مهارت‌های فنی و مشارکت در ارائه خدمات حرفه‌ای.</p>
        <p>در <span class="en">LOOP</span>، معیار اصلی همکاری، شایستگی، دانش، مهارت، مسئولیت‌پذیری و تناسب توانمندی‌های افراد با الزامات هر موقعیت شغلی است.</p>
      </div>
      <picture class="cr-split-media">
        <source media="(max-width: 767px)" srcset="{{ asset('assets/new-style/careers/workshop-mobile.jpg') }}" width="900" height="1599">
        <img src="{{ asset('assets/new-style/careers/workshop-desktop.jpg') }}" alt="تکنسین لوپ در حال تعمیر برد لپ‌تاپ" width="1280" height="853" loading="lazy">
      </picture>
    </div>
  </section>

  <!-- ================= PATH ================= -->
  <section class="cr-path">
    <div class="cr-container">
      <div class="cr-head">
        <span class="cr-eyebrow">مسیر همکاری</span>
        <h2>مسیر همکاری شما با <span class="en">LOOP</span></h2>
        <div class="cr-rule"></div>
      </div>
      <div class="cr-path-grid">
        <div class="cr-path-card">
          <span class="cr-step">01</span>
          <p>اگر علاقه‌مند به فعالیت در محیطی حرفه‌ای، یادگیری مستمر و مشارکت در مسیر توسعه <span class="en">LOOP</span> هستید، از شما دعوت می‌کنیم فرم درخواست همکاری را تکمیل کنید.</p>
        </div>
        <div class="cr-path-card">
          <span class="cr-step">02</span>
          <p>این فرم با هدف آشنایی اولیه با مشخصات، سوابق، تخصص‌ها، مهارت‌ها و زمینه‌های موردعلاقه شما طراحی شده و بخشی از فرایند گزینش اولیه مجموعه است.</p>
        </div>
        <div class="cr-path-card">
          <span class="cr-step">03</span>
          <p>پس از ثبت درخواست، اطلاعات شما توسط واحد مربوطه بررسی خواهد شد و در صورت تناسب با فرصت‌های شغلی موجود، برای ادامه مراحل گزینش، مصاحبه یا ارزیابی تخصصی با شما تماس گرفته می‌شود.</p>
        </div>
      </div>
      <p class="cr-note">تکمیل فرم درخواست همکاری به‌منزله استخدام قطعی یا ایجاد تعهد استخدامی برای طرفین نیست و صرفاً به‌عنوان ثبت درخواست و آغاز فرایند بررسی و گزینش اولیه در نظر گرفته می‌شود.</p>

      <div class="cr-cta">
        <h3>یک گام برای شروع مسیری تازه</h3>
        <p>توانمندی‌های خود را با ما به اشتراک بگذارید و نخستین گام را برای آشنایی و همکاری با <span class="en">LOOP</span> بردارید.</p>
        <div class="cr-cta-actions">
          <a href="#apply" class="cr-btn solid">همکاری با لوپ</a>
          <a href="#track" class="cr-btn">استعلام کد پیگیری</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= FORM ================= -->
  <section class="cr-form-section" id="apply">
    <div class="cr-container">
      <div class="cr-head dark">
        <span class="cr-eyebrow">فرم درخواست</span>
        <h2>فرم درخواست همکاری با لوپ</h2>
        <div class="cr-rule"></div>
      </div>

      @if ($submitted)
        <div class="cr-success" role="status">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
          <div>
            <h3>درخواست همکاری شما با موفقیت ثبت شد.</h3>
            <p>اطلاعات شما بررسی و در صورت تطابق با فرصت‌های همکاری، کارشناسان لوپ با شما تماس خواهند گرفت.</p>
            <p class="cr-code">کد پیگیری: <strong class="en">{{ $submitted['tracking_code'] }}</strong></p>
            <p class="cr-muted">
              @if ($submitted['sms_sent'])
                کد پیگیری به شماره موبایل شما پیامک شد.
              @else
                لطفاً این کد را یادداشت کنید؛ با آن می‌توانید وضعیت درخواست را استعلام کنید.
              @endif
            </p>
          </div>
        </div>
      @else
        @if ($errors->hasAny(['form']) || ($errors->any() && $trackErrors->isEmpty()))
          <div class="cr-alert" role="alert">
            <strong>لطفاً موارد زیر را بررسی کنید:</strong>
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form class="cr-form" action="{{ route('web.careers.store') }}" method="POST" enctype="multipart/form-data" novalidate>
          @csrf
          <div class="cr-hp" aria-hidden="true">
            <label>وب‌سایت <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          {{-- ۱. اطلاعات فردی --}}
          <fieldset class="cr-card">
            <legend><span>۱</span> اطلاعات فردی</legend>
            <div class="cr-grid">
              <label class="cr-field">نام و نام خانوادگی <b>*</b>
                <input type="text" name="full_name" value="{{ $old('full_name') }}" required maxlength="191" autocomplete="name">
                @error('full_name')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field">شماره موبایل <b>*</b>
                <input type="tel" name="mobile" value="{{ $old('mobile') }}" required inputmode="numeric" maxlength="11" placeholder="09xxxxxxxxx" class="ltr" autocomplete="tel">
                @error('mobile')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field">کد ملی <b>*</b>
                <input type="text" name="national_code" value="{{ $old('national_code') }}" required inputmode="numeric" maxlength="10" class="ltr">
                <small class="cr-hint">کد ملی با شماره موبایل استعلام می‌شود؛ شماره‌ای را وارد کنید که به نام خودتان است.</small>
                @error('national_code')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field">سن <b>*</b>
                <input type="number" name="age" value="{{ $old('age') }}" required min="16" max="70" inputmode="numeric">
                @error('age')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field">شهر محل سکونت <b>*</b>
                <input type="text" name="city" value="{{ $old('city') }}" required maxlength="100">
                @error('city')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field">منطقه محل سکونت
                <input type="text" name="district" value="{{ $old('district') }}" maxlength="100" placeholder="مثلاً منطقه ۵">
                @error('district')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
            </div>

            <div class="cr-q">
              <span class="cr-q-title">جنسیت <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::GENDERS as $value => $label)
                  <label class="cr-choice"><input type="radio" name="gender" value="{{ $value }}" {{ $checked('gender', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('gender')<small class="cr-err">{{ $message }}</small>@enderror
            </div>

            <div class="cr-q">
              <span class="cr-q-title">وضعیت تأهل <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::MARITAL as $value => $label)
                  <label class="cr-choice"><input type="radio" name="marital_status" value="{{ $value }}" {{ $checked('marital_status', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('marital_status')<small class="cr-err">{{ $message }}</small>@enderror
            </div>

            <div class="cr-q" data-show-when="gender=male">
              <span class="cr-q-title">وضعیت نظام وظیفه <b>*</b> <small>(برای آقایان)</small></span>
              <div class="cr-choices">
                @foreach (O::MILITARY as $value => $label)
                  <label class="cr-choice"><input type="radio" name="military_status" value="{{ $value }}" {{ $checked('military_status', $value) }}><span>{{ $label }}</span></label>
                @endforeach
              </div>
              <label class="cr-field cr-inline" data-show-when="military_status=other">توضیح
                <input type="text" name="military_status_other" value="{{ $old('military_status_other') }}" maxlength="191">
              </label>
              @error('military_status')<small class="cr-err">{{ $message }}</small>@enderror
              @error('military_status_other')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
          </fieldset>

          {{-- ۲. موقعیت شغلی --}}
          <fieldset class="cr-card">
            <legend><span>۲</span> موقعیت شغلی مورد درخواست</legend>
            <div class="cr-q">
              <span class="cr-q-title">عنوان شغلی <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::JOB_TITLES as $value => $label)
                  <label class="cr-choice"><input type="radio" name="job_title" value="{{ $value }}" {{ $checked('job_title', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              <label class="cr-field cr-inline" data-show-when="job_title=other">عنوان شغلی موردنظر
                <input type="text" name="job_title_other" value="{{ $old('job_title_other') }}" maxlength="191">
              </label>
              @error('job_title')<small class="cr-err">{{ $message }}</small>@enderror
              @error('job_title_other')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
            <div class="cr-q">
              <span class="cr-q-title">نوع همکاری <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::COOPERATION_TYPES as $value => $label)
                  <label class="cr-choice"><input type="radio" name="cooperation_type" value="{{ $value }}" {{ $checked('cooperation_type', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('cooperation_type')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
          </fieldset>

          {{-- ۳. تحصیلات و سابقه کاری --}}
          <fieldset class="cr-card">
            <legend><span>۳</span> تحصیلات و سابقه کاری</legend>
            <div class="cr-q">
              <span class="cr-q-title">آخرین مدرک تحصیلی <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::EDUCATION as $value => $label)
                  <label class="cr-choice"><input type="radio" name="education_level" value="{{ $value }}" {{ $checked('education_level', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              <label class="cr-field cr-inline" data-show-when="education_level=other">مدرک تحصیلی
                <input type="text" name="education_level_other" value="{{ $old('education_level_other') }}" maxlength="191">
              </label>
              @error('education_level')<small class="cr-err">{{ $message }}</small>@enderror
              @error('education_level_other')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
            <label class="cr-field">رشته تحصیلی
              <input type="text" name="field_of_study" value="{{ $old('field_of_study') }}" maxlength="191">
            </label>
            <div class="cr-q">
              <span class="cr-q-title">سابقه کاری <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::WORK_EXPERIENCE as $value => $label)
                  <label class="cr-choice"><input type="radio" name="work_experience" value="{{ $value }}" {{ $checked('work_experience', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('work_experience')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
            <div class="cr-q">
              <span class="cr-q-title">سابقه کار مرتبط با موقعیت شغلی <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::RELATED_EXPERIENCE as $value => $label)
                  <label class="cr-choice"><input type="radio" name="related_experience" value="{{ $value }}" {{ $checked('related_experience', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('related_experience')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
          </fieldset>

          {{-- ۴. مهارت‌های کامپیوتری و فنی --}}
          <fieldset class="cr-card">
            <legend><span>۴</span> مهارت‌های کامپیوتری و فنی</legend>
            <p class="cr-desc">برای هر مهارت، سطح توانایی خود را انتخاب کنید:</p>
            <ul class="cr-levels">
              @foreach (O::SKILL_LEVEL_HINTS as $level => $hint)
                <li><strong>{{ O::SKILL_LEVELS[$level] }}:</strong> {{ $hint }}</li>
              @endforeach
            </ul>

            @foreach (O::SKILL_GROUPS as $groupKey => $group)
              <div class="cr-skill-group">
                <h4>{{ ['hardware' => 'الف', 'software' => 'ب', 'office' => 'ج'][$groupKey] }}) {{ $group['title'] }}</h4>
                <div class="cr-skill-table" role="table">
                  <div class="cr-skill-row cr-skill-headrow" role="row">
                    <span role="columnheader">مهارت</span>
                    @foreach (O::SKILL_LEVELS as $label)
                      <span role="columnheader">{{ $label }}</span>
                    @endforeach
                  </div>
                  @foreach ($group['skills'] as $skillKey => $skillLabel)
                    @php $current = old("skills.$skillKey", 'none'); @endphp
                    <div class="cr-skill-row" role="row">
                      <span class="cr-skill-name" role="rowheader">{{ $skillLabel }}</span>
                      @foreach (O::SKILL_LEVELS as $level => $label)
                        <label class="cr-skill-cell" role="cell">
                          <input type="radio" name="skills[{{ $skillKey }}]" value="{{ $level }}" {{ $current === $level ? 'checked' : '' }} aria-label="{{ $skillLabel }}: {{ $label }}">
                          <span>{{ $label }}</span>
                        </label>
                      @endforeach
                    </div>
                  @endforeach
                </div>
              </div>
            @endforeach
          </fieldset>

          {{-- ۵. تخصص‌های تکمیلی --}}
          <fieldset class="cr-card">
            <legend><span>۵</span> تخصص‌های تکمیلی</legend>
            <div class="cr-q">
              <span class="cr-q-title">حوزه‌های تخصصی موردعلاقه</span>
              <div class="cr-choices">
                @foreach (O::INTEREST_AREAS as $value => $label)
                  <label class="cr-choice"><input type="checkbox" name="interest_areas[]" value="{{ $value }}" {{ in_array($value, old('interest_areas', []), true) ? 'checked' : '' }}><span>{{ $label }}</span></label>
                @endforeach
              </div>
              <label class="cr-field cr-inline" data-show-when="interest_areas[]=other">سایر حوزه‌ها
                <input type="text" name="interest_other" value="{{ $old('interest_other') }}" maxlength="191">
              </label>
            </div>
            <div class="cr-q">
              <span class="cr-q-title">مدارک فنی و گواهینامه‌ها <b>*</b></span>
              <div class="cr-choices">
                <label class="cr-choice"><input type="radio" name="has_certificates" value="yes" {{ $checked('has_certificates', 'yes') }} required><span>دارم</span></label>
                <label class="cr-choice"><input type="radio" name="has_certificates" value="no" {{ $checked('has_certificates', 'no') }}><span>ندارم</span></label>
              </div>
              @error('has_certificates')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
            <label class="cr-field">توضیحات یا تخصص‌های دیگر
              <textarea name="extra_skills" rows="3" maxlength="2000">{{ $old('extra_skills') }}</textarea>
            </label>
          </fieldset>

          {{-- ۶. اطلاعات ویژه تکنسین‌های میدانی --}}
          <fieldset class="cr-card" data-show-when="job_title=field_technician">
            <legend><span>۶</span> اطلاعات ویژه تکنسین‌های میدانی</legend>
            @php
              $fieldQuestions = [
                ['has_vehicle', 'آیا وسیله نقلیه شخصی دارید؟', O::YES_NO, null],
                ['vehicle_type', 'نوع وسیله نقلیه', O::VEHICLE_TYPES, null],
                ['has_license', 'آیا گواهینامه رانندگی دارید؟', O::YES_NO, null],
                ['license_type', 'نوع گواهینامه', O::LICENSE_TYPES, 'field_info[has_license]=yes'],
                ['mission_range', 'امکان مراجعه به شرکت‌ها و سازمان‌ها', O::MISSION_RANGE, null],
                ['carry_equipment', 'آیا امکان حمل تجهیزات و قطعات دارید؟', O::CARRY_EQUIPMENT, null],
                ['onsite_experience', 'آیا سابقه مراجعه و ارائه خدمات در محل مشتری را دارید؟', O::YES_NO, null],
              ];
            @endphp
            @foreach ($fieldQuestions as [$key, $title, $options, $showWhen])
              <div class="cr-q" @if ($showWhen) data-show-when="{{ $showWhen }}" @endif>
                <span class="cr-q-title">{{ $title }} <b>*</b></span>
                <div class="cr-choices">
                  @foreach ($options as $value => $label)
                    <label class="cr-choice"><input type="radio" name="field_info[{{ $key }}]" value="{{ $value }}" {{ (string) old("field_info.$key") === (string) $value ? 'checked' : '' }}><span>{{ $label }}</span></label>
                  @endforeach
                </div>
                @error("field_info.$key")<small class="cr-err">{{ $message }}</small>@enderror
              </div>
            @endforeach
          </fieldset>

          {{-- ۷. شرایط همکاری --}}
          <fieldset class="cr-card">
            <legend><span>۷</span> شرایط همکاری</legend>
            <div class="cr-q">
              <span class="cr-q-title">زمان آمادگی برای شروع <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::START_AVAILABILITY as $value => $label)
                  <label class="cr-choice"><input type="radio" name="start_availability" value="{{ $value }}" {{ $checked('start_availability', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('start_availability')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
            <div class="cr-q">
              <span class="cr-q-title">حقوق درخواستی <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::SALARY_TYPES as $value => $label)
                  <label class="cr-choice"><input type="radio" name="salary_type" value="{{ $value }}" {{ $checked('salary_type', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              <label class="cr-field cr-inline" data-show-when="salary_type=fixed">مبلغ حقوق درخواستی (تومان، اختیاری)
                <input type="text" name="salary_amount" value="{{ $old('salary_amount') }}" inputmode="numeric" class="ltr">
              </label>
              @error('salary_type')<small class="cr-err">{{ $message }}</small>@enderror
              @error('salary_amount')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
            <div class="cr-q">
              <span class="cr-q-title">امکان کار اضافه‌کاری <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::YES_NO_COORDINATION as $value => $label)
                  <label class="cr-choice"><input type="radio" name="overtime" value="{{ $value }}" {{ $checked('overtime', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('overtime')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
            <div class="cr-q">
              <span class="cr-q-title">امکان کار در شیفت <b>*</b></span>
              <div class="cr-choices">
                @foreach (O::YES_NO_COORDINATION as $value => $label)
                  <label class="cr-choice"><input type="radio" name="shift_work" value="{{ $value }}" {{ $checked('shift_work', $value) }} required><span>{{ $label }}</span></label>
                @endforeach
              </div>
              @error('shift_work')<small class="cr-err">{{ $message }}</small>@enderror
            </div>
          </fieldset>

          {{-- ۸. رزومه و مدارک --}}
          <fieldset class="cr-card">
            <legend><span>۸</span> رزومه و مدارک</legend>
            <div class="cr-grid">
              <label class="cr-field cr-file">بارگذاری رزومه
                <input type="file" name="resume" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <small class="cr-hint">PDF، Word یا تصویر — حداکثر ۵ مگابایت</small>
                @error('resume')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field cr-file">بارگذاری مدارک فنی
                <input type="file" name="certificates" accept=".pdf,.jpg,.jpeg,.png,.zip">
                <small class="cr-hint">PDF، تصویر یا ZIP — حداکثر ۱۰ مگابایت</small>
                @error('certificates')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field cr-file">بارگذاری نمونه‌کار
                <input type="file" name="portfolio" accept=".pdf,.jpg,.jpeg,.png,.zip">
                <small class="cr-hint">PDF، تصویر یا ZIP — حداکثر ۱۰ مگابایت</small>
                @error('portfolio')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
              <label class="cr-field">لینک رزومه یا نمونه‌کار (اختیاری)
                <input type="url" name="portfolio_link" value="{{ $old('portfolio_link') }}" class="ltr" placeholder="https://">
                @error('portfolio_link')<small class="cr-err">{{ $message }}</small>@enderror
              </label>
            </div>
          </fieldset>

          {{-- ۹. تأیید و ثبت درخواست --}}
          <fieldset class="cr-card">
            <legend><span>۹</span> تأیید و ثبت درخواست</legend>
            <label class="cr-check">
              <input type="checkbox" name="confirm_accuracy" value="1" {{ old('confirm_accuracy') ? 'checked' : '' }} required>
              <span>من تأیید می‌کنم اطلاعات ثبت‌شده صحیح است.</span>
            </label>
            @error('confirm_accuracy')<small class="cr-err">{{ $message }}</small>@enderror
            <label class="cr-check">
              <input type="checkbox" name="confirm_privacy" value="1" {{ old('confirm_privacy') ? 'checked' : '' }} required>
              <span>با ثبت و نگهداری اطلاعات و رزومه‌ام برای بررسی درخواست همکاری موافقم.</span>
            </label>
            @error('confirm_privacy')<small class="cr-err">{{ $message }}</small>@enderror

            <button type="submit" class="cr-btn solid cr-submit">همکاری با لوپ</button>
            <p class="cr-muted">پس از ثبت، کد پیگیری صادر و پیامک تأیید برای شماره موبایل ثبت‌شده ارسال می‌شود.</p>
          </fieldset>
        </form>
      @endif
    </div>
  </section>

  <!-- ================= TRACK ================= -->
  <section class="cr-track" id="track">
    <div class="cr-container">
      <div class="cr-track-box">
        <div>
          <span class="cr-eyebrow">پیگیری</span>
          <h2>استعلام کد پیگیری همکاری</h2>
          <p>کد پیگیری (مثل <span class="en">PCS-284580</span>) و شماره موبایل ثبت‌شده در درخواست را وارد کنید.</p>
        </div>
        <form action="{{ route('web.careers.track') }}" method="POST" class="cr-track-form">
          @csrf
          <input type="text" name="tracking_code" value="{{ old('tracking_code') }}" placeholder="کد پیگیری" class="ltr" required maxlength="20" aria-label="کد پیگیری">
          <input type="tel" name="track_mobile" value="{{ old('track_mobile') }}" placeholder="شماره موبایل" class="ltr" required maxlength="11" inputmode="numeric" aria-label="شماره موبایل">
          <button type="submit" class="cr-btn solid">استعلام</button>
        </form>
        @if ($trackErrors->any())
          <div class="cr-alert" role="alert">
            @foreach ($trackErrors->all() as $error)<div>{{ $error }}</div>@endforeach
          </div>
        @endif
        @if ($tracked)
          <div class="cr-track-result" role="status">
            <div><span>کد پیگیری</span><strong class="en">{{ $tracked['tracking_code'] }}</strong></div>
            <div><span>موقعیت شغلی</span><strong>{{ $tracked['job_title'] }}</strong></div>
            <div><span>تاریخ ثبت</span><strong>{{ $tracked['submitted_at'] }}</strong></div>
            <p>{{ $tracked['status_message'] }}</p>
          </div>
        @endif
      </div>
    </div>
  </section>
</div>

<style>
  .careers-page{
    --navy-950:#050b16; --navy-900:#0a1526; --navy-800:#0f2038; --navy-700:#16304f;
    --gold-100:#f9edc7; --gold-300:#ecc873; --gold-500:#d9a940; --gold-600:#d5aa65; --gold-700:#96701f;
    --text-hi:#f4f0e6; --text-mid:#c4cbda; --text-lo:#7c8aa3;
    --cream:#f7f4ef; --ink:#161a22; --ink-soft:#5b6472; --line:#e3ddd2; --danger:#b42318;
    --ease:cubic-bezier(.16,.84,.32,1);
    background:var(--navy-950); color:var(--text-hi); font-family:'Vazirmatn FD','Vazirmatn',sans-serif; line-height:1.9;
  }
  .careers-page *{box-sizing:border-box;}
  .careers-page .en{font-family:'Space Grotesk',sans-serif; direction:ltr; unicode-bidi:isolate;}
  .careers-page .ltr{direction:ltr; text-align:right;}
  .careers-page img{max-width:100%; height:auto; display:block;}
  .cr-container{max-width:1200px; margin:0 auto; padding:0 16px;}
  .careers-page section{padding:88px 0;}
  .careers-page section.cr-hero{padding:0;}
  .cr-hero img{width:100%;}

  .cr-head{text-align:center; margin-bottom:36px;}
  .cr-head h1,.cr-head h2{color:var(--text-hi); font-weight:800; font-size:clamp(24px,3vw,38px); margin:10px 0 0;}
  .cr-head.dark h2{color:var(--ink);}
  .cr-eyebrow{display:inline-block; color:var(--gold-500); font-size:13px; font-weight:700; letter-spacing:.08em;}
  .cr-rule{width:64px; height:2px; margin:16px auto 0; background:linear-gradient(90deg,transparent,var(--gold-500),transparent);}
  .cr-lead{max-width:880px; margin:0 auto; color:var(--text-mid); font-size:17px; text-align:justify;}
  .cr-lead p+p{margin-top:14px;}

  .cr-opportunity{background:var(--navy-900);}
  .cr-split{display:grid; grid-template-columns:1.05fr .95fr; gap:48px; align-items:center;}
  .cr-split-text h2{color:var(--text-hi); font-size:clamp(22px,2.6vw,32px); font-weight:800; margin:8px 0 16px;}
  .cr-split-text p{color:var(--text-mid); font-size:16px; text-align:justify;}
  .cr-split-text p+p{margin-top:12px;}
  .cr-split-media img{border-radius:22px; border:1px solid var(--navy-700); box-shadow:0 24px 60px rgba(0,0,0,.45);}

  .cr-path-grid{display:grid; grid-template-columns:repeat(3,1fr); gap:18px;}
  .cr-path-card{background:rgba(255,255,255,.03); border:1px solid var(--navy-700); border-radius:18px; padding:26px 24px;}
  .cr-path-card p{color:var(--text-mid); font-size:15px; margin:0;}
  .cr-step{display:inline-block; font-family:'Space Grotesk',sans-serif; color:var(--gold-500); border:1px solid var(--gold-600); border-radius:999px; padding:2px 12px; font-size:13px; margin-bottom:12px;}
  .cr-note{margin:26px auto 0; max-width:880px; color:var(--text-lo); font-size:14px; text-align:center; border-top:1px dashed var(--navy-700); padding-top:18px;}
  .cr-cta{margin-top:44px; text-align:center; background:linear-gradient(135deg,rgba(217,169,64,.12),rgba(217,169,64,.02)); border:1px solid rgba(217,169,64,.35); border-radius:22px; padding:36px 20px;}
  .cr-cta h3{color:var(--gold-300); font-weight:800; font-size:clamp(20px,2.2vw,26px); margin:0 0 8px;}
  .cr-cta p{color:var(--text-mid); margin:0 0 22px;}
  .cr-cta-actions{display:flex; gap:12px; justify-content:center; flex-wrap:wrap;}

  .cr-btn{display:inline-flex; align-items:center; justify-content:center; gap:10px; padding:13px 28px; border-radius:999px; border:1px solid var(--gold-600); color:var(--gold-300); background:rgba(217,169,64,.05); font-weight:700; font-size:15px; cursor:pointer; transition:all .3s var(--ease); text-decoration:none;}
  .cr-btn:hover{background:var(--gold-500); color:var(--navy-950); border-color:var(--gold-500); text-decoration:none;}
  .cr-btn.solid{background:linear-gradient(135deg,var(--gold-300),var(--gold-600)); color:var(--navy-950); border:none;}
  .cr-btn.solid:hover{filter:brightness(1.07); transform:translateY(-1px);}
  .cr-btn:focus-visible,.cr-choice input:focus-visible+span,.cr-skill-cell input:focus-visible+span{outline:2px solid var(--gold-500); outline-offset:2px;}

  /* ---------- form (light) ---------- */
  .cr-form-section{background:var(--cream); color:var(--ink);}
  .cr-form{max-width:980px; margin:0 auto;}
  .cr-card{background:#fff; border:1px solid var(--line); border-radius:18px; padding:26px 24px 22px; margin:0 0 18px; box-shadow:0 8px 22px rgba(20,24,31,.05); min-width:0;}
  .cr-card legend{float:right; width:100%; font-size:19px; font-weight:800; color:var(--ink); margin-bottom:16px; padding:0; display:flex; align-items:center; gap:10px;}
  .cr-card legend span{display:inline-flex; width:32px; height:32px; border-radius:50%; align-items:center; justify-content:center; background:var(--navy-900); color:var(--gold-300); font-size:15px;}
  .cr-card legend + *{clear:both;}
  .cr-grid{display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px 18px;}
  .cr-field{display:block; font-size:14px; font-weight:600; color:var(--ink); margin:0 0 14px;}
  .cr-field input,.cr-field textarea{display:block; margin-top:6px;}
  .cr-field .cr-hint,.cr-field .cr-err{margin-top:5px;}
  .cr-grid .cr-field{margin:0;}
  .cr-field b,.cr-q-title b{color:var(--danger); font-weight:700;}
  .cr-field input,.cr-field textarea{width:100%; border:1px solid #d6d0c4; border-radius:12px; padding:11px 14px; font:inherit; font-weight:400; color:var(--ink); background:#fdfcfa; transition:border-color .2s;}
  .cr-field input:focus,.cr-field textarea:focus{outline:none; border-color:var(--gold-500); box-shadow:0 0 0 3px rgba(217,169,64,.18);}
  .cr-field.cr-inline{margin-top:12px; max-width:420px;}
  .cr-file input{padding:9px 10px; background:#fff;}
  .cr-hint{color:var(--ink-soft); font-weight:400; font-size:12.5px;}
  .cr-err{color:var(--danger); font-weight:500; font-size:12.5px; display:block;}
  .cr-desc{color:var(--ink-soft); margin:0 0 6px;}
  .cr-levels{margin:0 0 18px; padding:0 18px 0 0; color:var(--ink-soft); font-size:14px;}

  .cr-q{margin:4px 0 20px;}
  .cr-q-title{display:block; font-weight:700; font-size:14.5px; margin-bottom:10px;}
  .cr-q-title small{color:var(--ink-soft); font-weight:400;}
  .cr-choices{display:flex; flex-wrap:wrap; gap:8px;}
  .cr-choice{position:relative; margin:0; cursor:pointer;}
  .cr-choice input{position:absolute; opacity:0; inset:0; cursor:pointer;}
  .cr-form-section .cr-choice span{display:inline-block; padding:8px 16px; border:1px solid #d6d0c4; border-radius:999px; font-size:14px; line-height:1.6; color:var(--ink); background:#fdfcfa; transition:all .2s; user-select:none;}
  .cr-choice:hover span{border-color:var(--gold-500);}
  .cr-form-section .cr-choice input:checked+span{background:var(--navy-900); border-color:var(--navy-900); color:var(--gold-300);}

  .cr-skill-group{margin-top:18px;}
  .cr-skill-group h4{font-size:16px; font-weight:800; color:var(--navy-800); margin:0 0 10px;}
  .cr-skill-table{border:1px solid var(--line); border-radius:14px; overflow:hidden;}
  .cr-skill-row{display:grid; grid-template-columns:minmax(0,2.2fr) repeat(4,minmax(0,1fr)); align-items:center; border-top:1px solid var(--line);}
  .cr-skill-row:nth-child(even){background:#fbf9f5;}
  .cr-skill-headrow{border-top:0; background:var(--navy-900) !important; color:var(--gold-100); font-size:13px; font-weight:700;}
  .cr-skill-headrow span{padding:10px 8px; text-align:center;}
  .cr-skill-headrow span:first-child{text-align:right; padding-right:14px;}
  .cr-skill-name{padding:10px 14px; font-size:14px; color:var(--ink);}
  .cr-skill-cell{position:relative; display:flex; justify-content:center; margin:0; padding:8px 4px; cursor:pointer;}
  .cr-skill-cell input{position:absolute; opacity:0; inset:0; cursor:pointer;}
  .cr-skill-cell span{width:22px; height:22px; border-radius:50%; border:2px solid #c9c2b4; font-size:0; transition:all .15s;}
  .cr-skill-cell input:checked+span{border-color:var(--gold-600); background:radial-gradient(circle,var(--gold-500) 0 45%,transparent 50%);}

  .cr-check{display:flex; gap:10px; align-items:flex-start; margin:0 0 10px; cursor:pointer; font-size:14.5px; color:var(--ink);}
  .cr-check input{margin-top:7px; width:18px; height:18px; accent-color:var(--gold-600); flex:none;}
  .cr-submit{margin-top:14px; min-width:220px; font-size:16px;}
  .cr-muted{color:var(--ink-soft); font-size:13px; margin:10px 0 0;}
  .cr-hp{position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap;}

  .cr-alert{max-width:980px; margin:0 auto 18px; background:#fef3f2; border:1px solid #fecdca; color:#7a271a; border-radius:14px; padding:14px 18px; font-size:14px;}
  .cr-alert ul{margin:6px 0 0; padding-right:18px;}
  .cr-success{max-width:980px; margin:0 auto; display:flex; gap:18px; align-items:flex-start; background:#fff; border:1px solid rgba(217,169,64,.5); border-radius:18px; padding:26px; box-shadow:0 10px 26px rgba(20,24,31,.07);}
  .cr-success>svg{width:42px; height:42px; color:#12805c; flex:none;}
  .cr-success h3{font-size:19px; font-weight:800; margin:0 0 6px; color:var(--ink);}
  .cr-success p{margin:0 0 6px; color:var(--ink-soft);}
  .cr-code{font-size:17px; color:var(--ink) !important;}
  .cr-code strong{color:var(--gold-700); font-size:22px; letter-spacing:.04em;}

  .cr-track{background:var(--navy-900);}
  .cr-track-box{max-width:980px; margin:0 auto; background:rgba(255,255,255,.03); border:1px solid var(--navy-700); border-radius:22px; padding:32px 28px;}
  .cr-track-box h2{color:var(--text-hi); font-weight:800; font-size:clamp(20px,2.4vw,28px); margin:6px 0 6px;}
  .cr-track-box p{color:var(--text-mid); margin:0 0 18px;}
  .cr-track-form{display:grid; grid-template-columns:1fr 1fr auto; gap:12px;}
  .cr-track-form input{border:1px solid var(--navy-700); background:rgba(255,255,255,.04); color:var(--text-hi); border-radius:12px; padding:12px 14px; font:inherit;}
  .cr-track-form input::placeholder{color:var(--text-lo);}
  .cr-track-form input:focus{outline:none; border-color:var(--gold-500);}
  .cr-track .cr-alert{margin:16px 0 0; max-width:none;}
  .cr-track-result{margin-top:18px; display:grid; grid-template-columns:repeat(3,1fr); gap:12px; background:rgba(217,169,64,.08); border:1px solid rgba(217,169,64,.35); border-radius:16px; padding:18px;}
  .cr-track-result div{display:flex; flex-direction:column;}
  .cr-track-result span{color:var(--text-lo); font-size:12.5px;}
  .cr-track-result strong{color:var(--gold-300); font-size:16px;}
  .cr-track-result p{grid-column:1/-1; margin:4px 0 0; color:var(--text-hi);}

  [data-show-when][hidden]{display:none !important;}

  @media (max-width:900px){
    .careers-page section{padding:64px 0;}
    .cr-split{grid-template-columns:1fr; gap:28px;}
    .cr-split-media{order:-1;}
    .cr-path-grid{grid-template-columns:1fr;}
  }
  @media (max-width:640px){
    .cr-grid{grid-template-columns:1fr;}
    .cr-card{padding:20px 16px 16px;}
    .cr-track-form{grid-template-columns:1fr;}
    .cr-track-result{grid-template-columns:1fr;}
    .cr-success{flex-direction:column;}
    /* جدول مهارت‌ها روی موبایل: هر مهارت یک کارت با چهار گزینه */
    .cr-skill-headrow{display:none;}
    .cr-skill-row{grid-template-columns:repeat(4,minmax(0,1fr)); padding:6px 4px 10px;}
    .cr-skill-name{grid-column:1/-1; padding:6px 8px 4px; font-weight:700;}
    .cr-skill-cell{flex-direction:column; align-items:center; gap:4px; padding:4px 2px;}
    .cr-skill-cell span{font-size:11px; width:auto; height:auto; border:1px solid #d6d0c4; border-radius:999px; padding:4px 6px; background:#fdfcfa; white-space:nowrap; color:var(--ink);}
    .cr-skill-cell input:checked+span{background:var(--navy-900); border-color:var(--navy-900); color:var(--gold-300);}
  }
</style>

<script>
  // نمایش شرطی بخش‌ها: data-show-when="name=value" (مثلاً بخش تکنسین میدانی فقط برای همان عنوان شغلی)
  (function () {
    var form = document.querySelector('.cr-form');
    if (!form) return;

    function values(name) {
      return Array.prototype.filter.call(form.elements, function (el) {
        return el.name === name && (el.type === 'radio' || el.type === 'checkbox' ? el.checked : true);
      }).map(function (el) { return el.value; });
    }

    function refresh() {
      form.querySelectorAll('[data-show-when]').forEach(function (block) {
        var rule = block.getAttribute('data-show-when').split('=');
        var visible = values(rule[0]).indexOf(rule[1]) !== -1;
        block.hidden = !visible;
        block.querySelectorAll('input, textarea, select').forEach(function (input) {
          input.disabled = !visible; // فیلدهای پنهان ارسال نمی‌شوند
        });
      });
    }

    form.addEventListener('change', refresh);
    refresh();
  })();
</script>
@endsection
