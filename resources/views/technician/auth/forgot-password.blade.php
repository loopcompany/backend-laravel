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
                                <h2 class="fw-bold fs-3">بازیابی رمز عبور</h2>
                                <p class="mb-0 h6 fw-light">نگران نباشید، ما کمک می‌کنیم!</p>
                            </div>
                            <!-- SVG Image -->
                            <img src="{{ asset('assets/images/element/02.svg') }}" class="mt-5" alt="">
                        </div>
                    </div>

                    <!-- Right -->
                    <div class="col-12 col-lg-6 m-auto">
                        <div class="row my-5">
                            <div class="col-sm-10 col-xl-8 m-auto">
                                <!-- Title -->
                                <h1 class="fs-4">فراموشی رمز عبور</h1>
                                <p class="mb-4">لطفا اطلاعات ثبت نام خود را وارد کنید تا کد بازیابی برای شما ارسال شود.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.technician.forgot-password') }}">
                                    @csrf
                                    
                                    <!-- نمایش پیام‌های خطا -->
                                    @if($errors->any())
                                        <div class="alert alert-danger">
                                            @foreach($errors->all() as $error)
                                                <div>{{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(session('success'))
                                        <div class="alert alert-success">
                                            {{ session('success') }}
                                        </div>
                                    @endif

                                    <!-- Referral Code -->
                                    <div class="mb-4">
                                        <label for="referral_code" class="form-label">کد پرسنلی *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-person-badge-fill"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="کد پرسنلی خود را وارد کنید" id="referral_code" name="referral_code" 
                                                value="{{ old('referral_code') }}" required>
                                        </div>
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
                                                value="{{ old('phone') }}" required>
                                        </div>
                                    </div>

                                    <!-- National Code -->
                                    <div class="mb-4">
                                        <label for="melicode" class="form-label">کد ملی *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-card-text"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="کد ملی ۱۰ رقمی" id="melicode" name="melicode" 
                                                value="{{ old('melicode') }}" maxlength="10" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Button -->
                                    <div class="align-items-center mt-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">ارسال کد بازیابی</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                                <!-- Back to login link -->
                                <div class="mt-4 text-center">
                                    <span>رمز عبور خود را به یاد آوردید؟ <a href="{{ route('web.technician.login-form') }}">ورود</a></span>
                                </div>
                            </div>
                        </div> <!-- Row END -->
                    </div>
                </div> <!-- Row END -->
            </div>
        </section>
    </main>
@endsection
