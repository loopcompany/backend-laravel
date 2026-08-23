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
                                <p class="mb-0 h6 fw-light">برای ثبت سفارش سیستماتیک وارد شوید</p>
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
                                <span class="mb-0 fs-1">👋</span>
                                <h1 class="fs-4">ورود به حساب کاربری</h1>
                                <p class="mb-4">از دیدن شما خوشحالم! لطفا با حساب کاربری خود وارد شوید.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.login') }}">
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
                                    <!-- Check box -->
                                    <div class="mb-4 d-flex justify-content-between mb-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                            <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                                        </div>
                                        <div class="text-primary-hover">
                                            <a href="{{ route('web.forgot-password') }}" class="text-secondary">
                                                <u>رمز خود را فراموش کرده اید؟</u>
                                            </a>
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
                                            <button class="btn btn-primary mb-0" type="submit">ورود</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                              

                                <!-- Sign up link -->
                                <div class="mt-4 text-center">
                                    <span>حساب کاربری ندارید؟ <a href="{{ route('web.register') }}">ثبت نام</a></span>
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
