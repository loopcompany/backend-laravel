@extends('layout.main.header')

@php
    // فیلدها، گزینه‌ها و برچسب‌ها همانند فرم ثبت‌نام اپ تکنسین (screens/auth/SignIn.js) است
    $maritalStatuses = ['متأهل', 'مجرد'];
    $militaryStatuses = ['مشمول خدمت', 'درانتظار اعزام', 'فاقد سابقه خدمت', 'اتمام خدمت', 'معافیت', 'در حال تحصیل'];
    $educationStatuses = ['دیپلم', 'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری'];
    $vehicleTypes = ['موتور سیکلت', 'خودرو', 'دوچرخه', 'پیاده'];

    $computerFields = ['idea', 'software_skill', 'hardware_skill', 'software_weakness', 'hardware_weakness', 'expertise_ids', 'resume', 'accept_terms'];
    $startOnComputerStep = $errors->hasAny($computerFields)
        && !$errors->hasAny(array_diff(array_keys($errors->getMessages()), $computerFields, ['message']));

    $selectedExpertises = array_map('intval', (array) old('expertise_ids', []));
    $licenceMaxDate = \Morilog\Jalali\Jalalian::now()->addYears(10)->format('Y/m/d');
@endphp

@section('content')
    <!-- Jalali DatePicker CSS -->
    <link rel="stylesheet" href="{{ asset('assets/jalali/jalalidatepicker.min.css') }}">

    <style>
        /* Fix RTL direction for select elements */
        select.form-select {
            direction: rtl;
            text-align: right;
            unicode-bidi: embed;
        }

        select.form-select option {
            direction: rtl;
            text-align: right;
            unicode-bidi: embed;
        }

        .technician-register-img {
            max-height: 70vh;
            width: auto;
            object-fit: cover;
        }

        .register-step-title {
            background: var(--bs-primary);
            color: #fff;
            border-radius: .5rem;
            padding: .75rem 1rem;
            text-align: center;
            font-size: 1.05rem;
            margin: 1.5rem 0 1rem;
        }

        .register-step[hidden] {
            display: none !important;
        }

        input[data-jdp][readonly] {
            background-color: var(--bs-body-bg);
            cursor: pointer;
        }

        .expertise-option .btn {
            width: 100%;
        }
    </style>

    <main>
        <section class="p-0 d-flex align-items-center position-relative overflow-hidden">

            <div class="container-fluid">
                <div class="row">
                    <!-- left -->
                    <div
                        class="col-12 col-lg-6 d-md-flex align-items-center justify-content-center bg-primary bg-opacity-10 vh-lg-100">
                        <div class="p-3 p-lg-5">
                            <!-- Title -->
                            <div class="text-center">
                                <h2 class="fw-bold fs-3">به جمع تکنسین‌ها ما بپیوندید</h2>
                                <p class="mb-0 h6 fw-light">فرصت‌های کاری جدید در انتظار شماست!</p>
                            </div>
                            <!-- Image -->
                            <picture>
                                <source srcset="{{ asset('assets/images/technician/register.webp') }}" type="image/webp">
                                <img src="{{ asset('assets/images/technician/register.png') }}"
                                    class="mt-5 d-block mx-auto img-fluid rounded-4 shadow technician-register-img"
                                    alt="تکنسین‌های لوپ در حال کار" width="1024" height="1536">
                            </picture>
                        </div>
                    </div>

                    <!-- Right -->
                    <div class="col-12 col-lg-6 m-auto">
                        <div class="row my-5">
                            <div class="col-sm-10 col-xl-10 m-auto">
                                <!-- Title -->
                                <h1 class="fs-4">ثبت نام تکنسین</h1>
                                <p class="mb-4">لطفا اطلاعات خود را با دقت وارد نمایید.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.technician.register') }}" enctype="multipart/form-data"
                                    id="technician-register-form" novalidate>
                                    @csrf

                                    @if($errors->has('message'))
                                        <div class="alert alert-danger">{{ $errors->first('message') }}</div>
                                    @elseif($errors->any())
                                        <div class="alert alert-danger">لطفاً فیلدهای مشخص شده را تکمیل کنید</div>
                                    @endif

                                    @if(session('success'))
                                        <div class="alert alert-success">
                                            {{ session('success') }}
                                        </div>
                                    @endif

                                    <div class="alert alert-danger d-none" id="client-error-alert"></div>

                                    <!-- ========== مرحله ۱: اطلاعات تکمیلی ========== -->
                                    <div class="register-step" data-step="personal" @if($startOnComputerStep) hidden @endif>
                                        <h5 class="register-step-title">اطلاعات تکمیلی</h5>

                                        <div class="row g-3">
                                            <!-- نام و نام خانوادگی -->
                                            <div class="col-md-6">
                                                <label for="name" class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                                    value="{{ old('name') }}" placeholder="مثال: علی احمدی">
                                                <div class="invalid-feedback" data-error-for="name">@error('name'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- شماره ملی -->
                                            <div class="col-md-6">
                                                <label for="melicode" class="form-label">شماره ملی <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('melicode') is-invalid @enderror" id="melicode" name="melicode"
                                                    value="{{ old('melicode') }}" placeholder="0123456789" maxlength="10" inputmode="numeric" dir="ltr" data-digits>
                                                <div class="invalid-feedback" data-error-for="melicode">@error('melicode'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- متولد -->
                                            <div class="col-md-6">
                                                <label for="birth_date" class="form-label">متولد <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date"
                                                    value="{{ old('birth_date') }}" placeholder="انتخاب تاریخ تولد" readonly
                                                    data-jdp data-jdp-min-date="1364/01/01" data-jdp-max-date="{{ $maxBirthDate }}">
                                                <div class="invalid-feedback" data-error-for="birth_date">@error('birth_date'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- نام پدر -->
                                            <div class="col-md-6">
                                                <label for="father_name" class="form-label">نام پدر <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('father_name') is-invalid @enderror" id="father_name" name="father_name"
                                                    value="{{ old('father_name') }}">
                                                <div class="invalid-feedback" data-error-for="father_name">@error('father_name'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- صادره از -->
                                            <div class="col-md-6">
                                                <label for="issued_from" class="form-label">صادره از <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('issued_from') is-invalid @enderror" id="issued_from" name="issued_from"
                                                    value="{{ old('issued_from') }}">
                                                <div class="invalid-feedback" data-error-for="issued_from">@error('issued_from'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- شماره شناسنامه -->
                                            <div class="col-md-6">
                                                <label for="serial_number" class="form-label">شماره شناسنامه <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('serial_number') is-invalid @enderror" id="serial_number" name="serial_number"
                                                    value="{{ old('serial_number') }}">
                                                <div class="invalid-feedback" data-error-for="serial_number">@error('serial_number'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- وضعیت تأهل -->
                                            <div class="col-md-6">
                                                <label for="marital_status" class="form-label">وضعیت تأهل <span class="text-danger">*</span></label>
                                                <select class="form-select @error('marital_status') is-invalid @enderror" id="marital_status" name="marital_status" dir="rtl">
                                                    @foreach ($maritalStatuses as $status)
                                                        <option value="{{ $status }}" @selected(old('marital_status', 'متأهل') === $status)>&#x202B;{{ $status }}&#x202C;</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" data-error-for="marital_status">@error('marital_status'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- وضعیت خدمت -->
                                            <div class="col-md-6">
                                                <label for="military_status" class="form-label">وضعیت خدمت <span class="text-danger">*</span></label>
                                                <select class="form-select @error('military_status') is-invalid @enderror" id="military_status" name="military_status" dir="rtl">
                                                    <option value="">&#x202B;انتخاب کنید...&#x202C;</option>
                                                    @foreach ($militaryStatuses as $status)
                                                        <option value="{{ $status }}" @selected(old('military_status') === $status)>&#x202B;{{ $status }}&#x202C;</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" data-error-for="military_status">@error('military_status'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- وضعیت تحصیلات -->
                                            <div class="col-md-6">
                                                <label for="education_status" class="form-label">وضعیت تحصیلات <span class="text-danger">*</span></label>
                                                <select class="form-select @error('education_status') is-invalid @enderror" id="education_status" name="education_status" dir="rtl">
                                                    @foreach ($educationStatuses as $status)
                                                        <option value="{{ $status }}" @selected(old('education_status', 'دیپلم') === $status)>&#x202B;{{ $status }}&#x202C;</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" data-error-for="education_status">@error('education_status'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- رشته تحصیلی -->
                                            <div class="col-md-6">
                                                <label for="education_field" class="form-label">رشته تحصیلی <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('education_field') is-invalid @enderror" id="education_field" name="education_field"
                                                    value="{{ old('education_field') }}">
                                                <div class="invalid-feedback" data-error-for="education_field">@error('education_field'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- شماره تلفن ثابت (۸ رقم، پیش‌شماره ۰۲۱) -->
                                            <div class="col-md-6">
                                                <label for="telephone" class="form-label">شماره تلفن ثابت <span class="text-danger">*</span></label>
                                                <div class="input-group has-validation" dir="ltr">
                                                    <span class="input-group-text">021</span>
                                                    <input type="text" class="form-control @error('telephone') is-invalid @enderror" id="telephone" name="telephone"
                                                        value="{{ old('telephone') }}" maxlength="8" inputmode="numeric" data-digits>
                                                    <div class="invalid-feedback text-end" dir="rtl" data-error-for="telephone">@error('telephone'){{ $message }}@enderror</div>
                                                </div>
                                            </div>

                                            <!-- شماره تلفن موبایل -->
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">شماره تلفن موبایل ۱۱ رقمی <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                                                    value="{{ old('phone') }}" placeholder="09123456789" maxlength="11" inputmode="numeric" dir="ltr" data-digits>
                                                <div class="invalid-feedback" data-error-for="phone">@error('phone'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- شماره موبایل دوم -->
                                            <div class="col-md-6">
                                                <label for="mobile" class="form-label">شماره موبایل دوم (اختیاری)</label>
                                                <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile"
                                                    value="{{ old('mobile') }}" placeholder="09123456789" maxlength="11" inputmode="numeric" dir="ltr" data-digits>
                                                <div class="invalid-feedback" data-error-for="mobile">@error('mobile'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- آدرس ایمیل -->
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">آدرس ایمیل <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                                    value="{{ old('email') }}" placeholder="example@email.com" dir="ltr">
                                                <div class="invalid-feedback" data-error-for="email">@error('email'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- شماره گواهینامه -->
                                            <div class="col-md-6">
                                                <label for="certificate_number" class="form-label">شماره گواهینامه</label>
                                                <input type="text" class="form-control @error('certificate_number') is-invalid @enderror" id="certificate_number" name="certificate_number"
                                                    value="{{ old('certificate_number') }}" placeholder="123456789" inputmode="numeric" dir="ltr" data-digits>
                                                <div class="invalid-feedback" data-error-for="certificate_number">@error('certificate_number'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- تاریخ اعتبار گواهینامه: از امروز تا ۱۰ سال بعد -->
                                            <div class="col-md-6">
                                                <label for="certificate_expiry_date" class="form-label">تاریخ اعتبار گواهینامه</label>
                                                <input type="text" class="form-control @error('certificate_expiry_date') is-invalid @enderror" id="certificate_expiry_date" name="certificate_expiry_date"
                                                    value="{{ old('certificate_expiry_date') }}" placeholder="انتخاب تاریخ اعتبار" readonly
                                                    data-jdp data-jdp-min-date="today" data-jdp-max-date="{{ $licenceMaxDate }}">
                                                <div class="invalid-feedback" data-error-for="certificate_expiry_date">@error('certificate_expiry_date'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- تاریخ صدور گواهینامه: تا امروز -->
                                            <div class="col-md-6">
                                                <label for="certificate_issue_date" class="form-label">تاریخ صدور گواهینامه</label>
                                                <input type="text" class="form-control @error('certificate_issue_date') is-invalid @enderror" id="certificate_issue_date" name="certificate_issue_date"
                                                    value="{{ old('certificate_issue_date') }}" placeholder="تاریخ صدور گواهینامه: 1400/05/15" readonly
                                                    data-jdp data-jdp-max-date="today">
                                                <div class="invalid-feedback" data-error-for="certificate_issue_date">@error('certificate_issue_date'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- نوع وسیله نقلیه -->
                                            <div class="col-md-6">
                                                <label for="vehicle_type" class="form-label">نوع وسیله نقلیه</label>
                                                <select class="form-select @error('vehicle_type') is-invalid @enderror" id="vehicle_type" name="vehicle_type" dir="rtl">
                                                    <option value="">&#x202B;انتخاب کنید...&#x202C;</option>
                                                    @foreach ($vehicleTypes as $vehicleType)
                                                        <option value="{{ $vehicleType }}" @selected(old('vehicle_type') === $vehicleType)>&#x202B;{{ $vehicleType }}&#x202C;</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" data-error-for="vehicle_type">@error('vehicle_type'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- کد پستی منزل -->
                                            <div class="col-md-6">
                                                <label for="home_postal_code" class="form-label">کد پستی منزل <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('home_postal_code') is-invalid @enderror" id="home_postal_code" name="home_postal_code"
                                                    value="{{ old('home_postal_code') }}" maxlength="10" inputmode="numeric" dir="ltr" data-digits>
                                                <div class="invalid-feedback" data-error-for="home_postal_code">@error('home_postal_code'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- منطقه -->
                                            <div class="col-4 col-md-3">
                                                <label for="region" class="form-label">منطقه <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('region') is-invalid @enderror" id="region" name="region"
                                                    value="{{ old('region') }}" maxlength="2" inputmode="numeric" dir="ltr" data-digits>
                                                <div class="invalid-feedback" data-error-for="region">@error('region'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- شهر (فعلاً فقط تهران) -->
                                            <div class="col-8 col-md-3">
                                                <label for="city" class="form-label">شهر <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="city" name="city" value="تهران" readonly>
                                            </div>

                                            <!-- آدرس منزل -->
                                            <div class="col-12">
                                                <label for="home_address" class="form-label">آدرس منزل <span class="text-danger">*</span></label>
                                                <textarea class="form-control @error('home_address') is-invalid @enderror" id="home_address" name="home_address"
                                                    rows="3">{{ old('home_address') }}</textarea>
                                                <div class="invalid-feedback" data-error-for="home_address">@error('home_address'){{ $message }}@enderror</div>
                                            </div>

                                            <!-- کد پرسنلی معرف -->
                                            <div class="col-12">
                                                <label for="other_referral_code" class="form-label">کد پرسنلی معرف</label>
                                                <input type="text" class="form-control @error('other_referral_code') is-invalid @enderror" id="other_referral_code" name="other_referral_code"
                                                    value="{{ old('other_referral_code') }}" dir="ltr">
                                                <div class="invalid-feedback" data-error-for="other_referral_code">@error('other_referral_code'){{ $message }}@enderror</div>
                                            </div>
                                        </div>

                                        <div class="d-grid mt-4">
                                            <button class="btn btn-primary mb-0" type="button" id="go-to-computer-step">بعدی - دانش کامپیوتر</button>
                                        </div>
                                    </div>

                                    <!-- ========== مرحله ۲: دانش کامپیوتر ========== -->
                                    <div class="register-step" data-step="computer" @unless($startOnComputerStep) hidden @endunless>
                                        <h5 class="register-step-title">دانش کامپیوتر</h5>

                                        <div class="row g-3">
                                            @foreach ([
                                                'idea' => 'ایده / خلاقیت',
                                                'software_skill' => 'تسلط / توانایی ها (نرم افزار)',
                                                'hardware_skill' => 'تسلط / توانایی ها (سخت افزار)',
                                                'software_weakness' => 'ناآگاهی / نقطه ضعف (نرم افزار)',
                                                'hardware_weakness' => 'ناآگاهی / نقاط ضعف (سخت افزار)',
                                            ] as $field => $label)
                                                <div class="col-12">
                                                    <label for="{{ $field }}" class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                                                    <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}"
                                                        rows="3">{{ old($field) }}</textarea>
                                                    <div class="invalid-feedback" data-error-for="{{ $field }}">@error($field){{ $message }}@enderror</div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <h5 class="register-step-title">گرایش فعالیت / تخصص</h5>

                                        <div class="row g-2" id="expertise-options">
                                            @forelse ($expertises as $expertise)
                                                <div class="col-sm-6 expertise-option">
                                                    <input type="checkbox" class="btn-check" name="expertise_ids[]" value="{{ $expertise->id }}"
                                                        id="expertise-{{ $expertise->id }}" autocomplete="off" @checked(in_array($expertise->id, $selectedExpertises, true))>
                                                    <label class="btn btn-outline-primary" for="expertise-{{ $expertise->id }}">{{ $expertise->title }}</label>
                                                </div>
                                            @empty
                                                <div class="col-12 text-muted">لیست تخصص‌ها در دسترس نیست.</div>
                                            @endforelse
                                        </div>
                                        <div class="invalid-feedback @error('expertise_ids') d-block @enderror" data-error-for="expertise_ids">
                                            @error('expertise_ids'){{ $message }}@enderror
                                            @error('expertise_ids.*'){{ $message }}@enderror
                                        </div>

                                        <!-- بارگذاری رزومه -->
                                        <div class="card card-body bg-light mt-4">
                                            <label for="resume" class="form-label fw-bold">بارگذاری رزومه <span class="text-danger">*</span></label>
                                            <p class="small mb-2">می‌توانید رزومه / اطلاعات تکمیلی خود را امضا شده با موضوع (همکاری / فعالیت در لوپ) بارگذاری نمایید.</p>
                                            <input type="file" class="form-control @error('resume') is-invalid @enderror" id="resume" name="resume"
                                                accept=".pdf,.doc,.docx,.jpg,.jpeg">
                                            <div class="form-text">فرمت‌های مجاز: PDF، DOC، DOCX، JPG — حداکثر ۵ مگابایت</div>
                                            <div class="invalid-feedback" data-error-for="resume">@error('resume'){{ $message }}@enderror</div>
                                        </div>

                                        <!-- پذیرش قوانین -->
                                        <div class="form-check mt-4">
                                            <input class="form-check-input @error('accept_terms') is-invalid @enderror" type="checkbox" value="1"
                                                id="accept_terms" name="accept_terms" @checked(old('accept_terms'))>
                                            <label class="form-check-label" for="accept_terms">
                                                با ادامه، <a href="{{ route('web.terms') }}" target="_blank">شرایط استفاده</a> و
                                                <a href="{{ route('web.privacy') }}" target="_blank">سیاست حفظ حریم خصوصی</a> Loop را می‌پذیرم.
                                            </label>
                                            <div class="invalid-feedback" data-error-for="accept_terms">@error('accept_terms'){{ $message }}@enderror</div>
                                        </div>

                                        <div class="d-grid gap-2 mt-4">
                                            <button class="btn btn-primary mb-0" type="submit" id="register-submit">ثبت نام</button>
                                            <button class="btn btn-secondary mb-0" type="button" id="back-to-personal-step">بازگشت به اطلاعات تکمیلی</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                                <!-- Login link -->
                                <div class="mt-4 text-center">
                                    <span>قبلاً ثبت نام کرده‌اید؟ <a href="{{ route('web.technician.login-form') }}">ورود</a></span>
                                </div>
                            </div>
                        </div> <!-- Row END -->
                    </div>
                </div> <!-- Row END -->
            </div>
        </section>
    </main>

    <!-- Jalali DatePicker JS -->
    <script src="{{ asset('assets/jalali/jalalidatepicker.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            jalaliDatepicker.startWatch({
                minDate: "attr",
                maxDate: "attr",
                separatorChar: "/",
                hasMaxYear: true,
                hasMinYear: true
            });

            const form = document.getElementById('technician-register-form');
            const personalStep = form.querySelector('[data-step="personal"]');
            const computerStep = form.querySelector('[data-step="computer"]');
            const clientAlert = document.getElementById('client-error-alert');
            const maxBirthYear = {{ (int) substr($maxBirthDate, 0, 4) }};
            const referralUrl = @json(route('api.technician.validate-referral'));

            const personalRequired = {
                name: 'نام و نام خانوادگی', melicode: 'کد ملی', birth_date: 'تاریخ تولد', father_name: 'نام پدر',
                issued_from: 'محل صدور', serial_number: 'شماره شناسنامه', marital_status: 'وضعیت تأهل',
                military_status: 'وضعیت نظام وظیفه', education_status: 'وضعیت تحصیلات', education_field: 'رشته تحصیلی',
                telephone: 'تلفن ثابت', phone: 'شماره تلفن موبایل', email: 'آدرس ایمیل', home_postal_code: 'کد پستی',
                region: 'منطقه', home_address: 'آدرس منزل'
            };
            const computerRequired = {
                idea: 'ایده / خلاقیت', software_skill: 'تسلط نرم‌افزار', hardware_skill: 'تسلط سخت‌افزار',
                software_weakness: 'نقطه ضعف نرم‌افزار', hardware_weakness: 'نقطه ضعف سخت‌افزار'
            };

            // ارقام فارسی/عربی را در فیلدهای عددی به لاتین تبدیل می‌کند
            const toLatinDigits = (text) => text.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
            form.querySelectorAll('[data-digits]').forEach(input => {
                input.addEventListener('input', () => {
                    const latin = toLatinDigits(input.value).replace(/\D/g, '');
                    if (latin !== input.value) input.value = latin;
                });
            });

            const value = (name) => (form.elements[name]?.value || '').trim();

            const isValidMeliCode = (code) => {
                if (!/^\d{10}$/.test(code) || /^(\d)\1{9}$/.test(code)) return false;
                let sum = 0;
                for (let i = 0; i < 9; i++) sum += Number(code[i]) * (10 - i);
                const remainder = sum % 11;
                return Number(code[9]) === (remainder < 2 ? remainder : 11 - remainder);
            };

            function setError(name, message) {
                const feedback = form.querySelector(`[data-error-for="${name}"]`);
                const input = form.elements[name] && !(form.elements[name] instanceof RadioNodeList) ? form.elements[name] : null;
                if (input) input.classList.toggle('is-invalid', !!message);
                if (feedback) {
                    feedback.textContent = message || '';
                    if (!input) feedback.classList.toggle('d-block', !!message);
                }
            }

            function validateStep(step) {
                const errors = {};
                Object.entries(personalRequired).forEach(([key, label]) => {
                    if (!value(key)) errors[key] = `${label} الزامی است`;
                });

                if (value('melicode') && !isValidMeliCode(value('melicode'))) {
                    errors.melicode = value('melicode').length !== 10 ? 'کد ملی باید 10 رقم باشد' : 'کد ملی نامعتبر است';
                }
                if (value('phone') && !/^09\d{9}$/.test(value('phone'))) errors.phone = 'فرمت شماره تلفن صحیح نیست (09xxxxxxxxx)';
                if (value('mobile') && !/^09\d{9}$/.test(value('mobile'))) errors.mobile = 'فرمت شماره تلفن صحیح نیست (09xxxxxxxxx)';
                if (value('telephone') && !/^\d{8,9}$/.test(value('telephone'))) errors.telephone = 'فرمت شماره تلفن ثابت صحیح نیست';
                if (value('email') && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value('email'))) errors.email = 'فرمت ایمیل صحیح نیست';
                if (value('home_postal_code') && !/^\d{10}$/.test(value('home_postal_code'))) errors.home_postal_code = 'کد پستی باید 10 رقم باشد';
                if (value('birth_date') && Number(value('birth_date').slice(0, 4)) > maxBirthYear) {
                    errors.birth_date = `ثبت‌نام برای متولدین سال ${maxBirthYear} و قبل از آن امکان‌پذیر است`;
                }

                if (step === 'computer') {
                    Object.entries(computerRequired).forEach(([key, label]) => {
                        if (!value(key)) errors[key] = `${label} الزامی است`;
                    });
                    if (!form.querySelector('input[name="expertise_ids[]"]:checked')) errors.expertise_ids = 'لطفاً حداقل یک تخصص انتخاب کنید';

                    const resume = form.elements.resume.files[0];
                    if (!resume) errors.resume = 'بارگذاری رزومه الزامی است.';
                    else if (resume.size > 5 * 1024 * 1024) errors.resume = 'حجم فایل نباید بیشتر از 5 مگابایت باشد.';

                    if (!form.elements.accept_terms.checked) errors.accept_terms = 'پذیرش قوانین و مقررات الزامی می باشد';
                }

                const fields = step === 'computer'
                    ? [...Object.keys(personalRequired), 'mobile', ...Object.keys(computerRequired), 'expertise_ids', 'resume', 'accept_terms']
                    : [...Object.keys(personalRequired), 'mobile'];
                fields.forEach(name => setError(name, errors[name]));

                return errors;
            }

            function showClientAlert(message) {
                clientAlert.textContent = message || '';
                clientAlert.classList.toggle('d-none', !message);
            }

            function showStep(step) {
                personalStep.hidden = step !== 'personal';
                computerStep.hidden = step !== 'computer';
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function focusFirstError() {
                const first = form.querySelector('.register-step:not([hidden]) .is-invalid');
                if (first) first.focus({ preventScroll: false });
            }

            // پاک کردن خطای فیلد هنگام تغییر
            form.addEventListener('input', (e) => e.target.name && setError(e.target.name.replace('[]', ''), ''));
            form.addEventListener('change', (e) => e.target.name && setError(e.target.name.replace('[]', ''), ''));

            async function referralCodeIsValid() {
                const code = value('other_referral_code');
                if (!code) return true;
                try {
                    const response = await fetch(referralUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ referral_code: code })
                    });
                    const result = await response.json();
                    if (result?.data?.is_valid) return true;
                    setError('other_referral_code', result?.data?.message || 'کد معرف نامعتبر است');
                } catch (e) {
                    setError('other_referral_code', 'خطا در بررسی کد معرف');
                }
                return false;
            }

            document.getElementById('go-to-computer-step').addEventListener('click', async function () {
                this.disabled = true;
                const referralOk = await referralCodeIsValid();
                this.disabled = false;
                if (!referralOk) {
                    showClientAlert('لطفاً فیلدهای مشخص شده را تکمیل کنید');
                    form.elements.other_referral_code.focus();
                    return;
                }

                const errors = validateStep('personal');
                if (Object.keys(errors).length) {
                    showClientAlert('لطفاً فیلدهای مشخص شده را تکمیل کنید');
                    focusFirstError();
                    return;
                }
                showClientAlert('');
                showStep('computer');
            });

            document.getElementById('back-to-personal-step').addEventListener('click', () => {
                showClientAlert('');
                showStep('personal');
            });

            form.addEventListener('submit', function (e) {
                const errors = validateStep('computer');
                if (Object.keys(errors).length) {
                    e.preventDefault();
                    showClientAlert('لطفاً فیلدهای مشخص شده را تکمیل کنید');
                    const personalHasErrors = Object.keys(errors).some(key => key in personalRequired || key === 'mobile');
                    if (personalHasErrors) showStep('personal');
                    focusFirstError();
                    return;
                }
                const submit = document.getElementById('register-submit');
                submit.disabled = true;
                submit.textContent = 'در حال ثبت نام...';
            });
        });
    </script>
@endsection
