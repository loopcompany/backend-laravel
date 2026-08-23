@extends('layout.main.header')
@section('content')
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
                                <h2 class="fw-bold fs-3">سلام؛ به جوهر آینده خوش آمدید.</h2>
                                <p class="mb-0 h6 fw-light">ثبت‌نام کنید و از خدمات بی‌نظیر ما بهره‌مند شوید!</p>
                            </div>
                            <!-- SVG Image -->
                            <img src="assets/images/element/02.svg" class="mt-5" alt="">
                     
                        </div>
                    </div>

                    <!-- Right -->
                    <div class="col-12 col-lg-6 m-auto">
                        <div class="row my-5">
                            <div class="col-sm-10 col-xl-8 m-auto">
                                <!-- Title -->
                                <span class="mb-0 fs-1">🚀</span>
                                <h1 class="fs-4">ثبت نام در سیستم</h1>
                                <p class="mb-4">برای شروع، اطلاعات زیر را وارد کنید و به خانواده ما بپیوندید.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.register') }}">
                                    @csrf

                                    <!-- نمایش پیام‌های خطا -->
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            @foreach ($errors->all() as $error)
                                                <div>{{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (session('success'))
                                        <div class="alert alert-success">
                                            {{ session('success') }}
                                        </div>
                                    @endif

                                    <!-- National Code -->
                                    <div class="mb-4">
                                        <label for="melicode" class="form-label">کد ملی *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-person-vcard-fill"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="1234567890" id="melicode" name="melicode"
                                                value="{{ old('melicode') }}" required maxlength="10">
                                        </div>
                                        <div class="form-text">کد ملی ۱۰ رقمی خود را وارد کنید</div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="mb-4">
                                        <label for="phone" class="form-label">شماره موبایل *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-phone-fill"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="09123456789" id="phone" name="phone"
                                                value="{{ old('phone') }}" required maxlength="11">
                                        </div>
                                        <div class="form-text">شماره موبایل خود را با ۰۹ شروع کنید</div>
                                    </div>

                                    <!-- Email -->
                                    <div class="mb-4">
                                        <label for="email" class="form-label">آدرس ایمیل *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-envelope-fill"></i></span>
                                            <input type="email" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="example@gmail.com" id="email" name="email"
                                                value="{{ old('email') }}" required>
                                        </div>
                                    </div>

                                    <!-- Referral Code (Optional) -->
                                    <div class="mb-4">
                                        <label for="other_referral_code" class="form-label">کد معرف (اختیاری)</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-gift-fill"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="کد معرف خود را وارد کنید" id="other_referral_code"
                                                name="other_referral_code" value="{{ old('other_referral_code') }}">
                                        </div>
                                        <div class="form-text">اگر کد معرف دارید، اینجا وارد کنید</div>
                                    </div>

                                    <!-- Terms and Conditions -->
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="terms" name="terms"
                                                required>
                                            <label class="form-check-label" for="terms">
                                                <a href="{{ route('web.terms') }}" target="_blank">قوانین و شرایط</a> و
                                                <a href="{{ route('web.privacy') }}" target="_blank">حریم خصوصی</a> را
                                                مطالعه کرده و می‌پذیرم
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- reCAPTCHA -->
                                    <div class="mb-4">
                                        {!! NoCaptcha::renderJs('fa') !!}
                                        {!! NoCaptcha::display() !!}
                                        @if($errors->has('g-recaptcha-response'))
                                            <div class="text-danger small mt-1">{{ $errors->first('g-recaptcha-response') }}</div>
                                        @endif
                                    </div>

                                    <!-- Button -->
                                    <div class="align-items-center mt-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">ثبت نام</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                                <!-- Divider -->
                                <div class="position-relative my-4">
                                    <hr>
                                    <p class="small position-absolute top-50 start-50 translate-middle bg-body px-5">یـا
                                    </p>
                                </div>

                                <!-- Login link -->
                                <div class="mt-4 text-center">
                                    <span>قبلاً ثبت نام کرده‌اید؟ <a href="{{ route('web.login') }}">ورود</a></span>
                                </div>
                            </div>
                        </div> <!-- Row END -->
                    </div>
                </div> <!-- Row END -->
            </div>
        </section>
    </main>
@endsection
