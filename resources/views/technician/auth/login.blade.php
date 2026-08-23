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
                                <h2 class="fw-bold fs-3">سلام؛ به پنل تکنسین خوش آمدید.</h2>
                                <p class="mb-0 h6 fw-light">برای مدیریت سفارشات خود وارد شوید</p>
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
                                <h1 class="fs-4">ورود تکنسین</h1>
                                <p class="mb-4">از دیدن شما خوشحالم! لطفا با شماره موبایل و رمز عبور خود وارد شوید.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.technician.login') }}">
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
                                        <label for="referral_code" class="form-label">شماره موبایل *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-phone-fill"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="شماره موبایل خود را وارد کنید" id="referral_code" name="referral_code" 
                                                value="{{ old('referral_code') }}" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Password -->
                                    <div class="mb-4">
                                        <label for="password" class="form-label">رمز عبور *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control border-0 bg-light ps-1"
                                                placeholder="********" id="password" name="password" required>
                                            <span class="input-group-text bg-light rounded-end border-0 text-secondary px-3" 
                                                  style="cursor: pointer;" 
                                                  onclick="togglePasswordVisibility('password', this)">
                                                <i class="fas fa-eye" id="password-eye"></i>
                                            </span>
                                        </div>
                                        <div id="passwordHelpBlock" class="form-text">
                                            رمز عبور شما باید حداقل ۶ کاراکتر باشد
                                        </div>
                                    </div>
                                    
                                    <!-- Forgot password link -->
                                    <div class="mb-4 text-end">
                                        <a href="{{ route('web.technician.forgot-password-form') }}" class="text-secondary">
                                            <u>رمز خود را فراموش کرده اید؟</u>
                                        </a>
                                    </div>
                                    
                                    <!-- Button -->
                                    <div class="align-items-center mt-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">ورود</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                                <!-- Sign up link -->
                                <div class="mt-4 text-center">
                                    <span>هنوز عضو نشده‌اید؟ <a href="{{ route('web.technician.register-form') }}">ثبت نام تکنسین</a></span>
                                </div>
                            </div>
                        </div> <!-- Row END -->
                    </div>
                </div> <!-- Row END -->
            </div>
        </section>
    </main>

    <script>
        function togglePasswordVisibility(inputId, eyeElement) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = eyeElement.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
@endsection
