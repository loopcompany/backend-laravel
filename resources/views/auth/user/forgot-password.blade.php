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
                                <p class="mb-0 h6 fw-light">نگران نباشید، ما کمکتان می‌کنیم!</p>
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
                                <span class="mb-0 fs-1">🔑</span>
                                <h1 class="fs-4">فراموشی رمز عبور</h1>
                                <p class="mb-4">برای بازیابی رمز عبور، اطلاعات ثبت شده خود را وارد کنید تا کد تایید برایتان ارسال شود.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.forgot-password') }}">
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
                                        <div class="form-text">کد ملی ۱۰ رقمی ثبت شده</div>
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
                                        <div class="form-text">شماره موبایل ثبت شده در سیستم</div>
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
                                        <div class="form-text">ایمیل ثبت شده در سیستم</div>
                                    </div>

                                    <!-- Button -->
                                    <div class="align-items-center mt-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">ارسال کد تایید</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                                <!-- Divider -->
                                <div class="position-relative my-4">
                                    <hr>
                                    <p class="small position-absolute top-50 start-50 translate-middle bg-body px-5">یـا</p>
                                </div>

                                <!-- Back to login link -->
                                <div class="mt-4 text-center">
                                    <span>رمز عبور خود را به یاد آوردید؟ <a href="{{ route('web.login') }}">ورود</a></span>
                                </div>
                            </div>
                        </div> <!-- Row END -->
                    </div>
                </div> <!-- Row END -->
            </div>
        </section>
    </main>
@endsection