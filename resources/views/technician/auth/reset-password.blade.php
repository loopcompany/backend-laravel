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
                                <h2 class="fw-bold fs-3">تنظیم رمز عبور جدید</h2>
                                <p class="mb-0 h6 fw-light">یک رمز قوی انتخاب کنید</p>
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
                                <h1 class="fs-4">تنظیم رمز عبور جدید</h1>
                                <p class="mb-4">رمز عبور جدید خود را وارد کنید.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.technician.reset-password') }}">
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

                                    <!-- Phone (hidden) -->
                                    <input type="hidden" name="phone" value="{{ session('phone') ?? old('phone') }}">

                                    <!-- New Password -->
                                    <div class="mb-4">
                                        <label for="new_password" class="form-label">رمز عبور جدید *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control border-0 bg-light ps-1"
                                                placeholder="حداقل ۶ کاراکتر" id="new_password" name="new_password" required>
                                            <span class="input-group-text bg-light rounded-end border-0 text-secondary px-3" 
                                                  style="cursor: pointer;" 
                                                  onclick="togglePasswordVisibility('new_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="mb-4">
                                        <label for="new_password_confirmation" class="form-label">تأیید رمز عبور *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control border-0 bg-light ps-1"
                                                placeholder="تکرار رمز عبور" id="new_password_confirmation" name="new_password_confirmation" required>
                                            <span class="input-group-text bg-light rounded-end border-0 text-secondary px-3" 
                                                  style="cursor: pointer;" 
                                                  onclick="togglePasswordVisibility('new_password_confirmation', this)">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                        <div class="form-text">
                                            رمز عبور باید حداقل ۶ کاراکتر باشد
                                        </div>
                                    </div>
                                    
                                    <!-- Button -->
                                    <div class="align-items-center mt-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">تنظیم رمز عبور</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                                <!-- Back to login link -->
                                <div class="mt-4 text-center">
                                    <span>بازگشت به <a href="{{ route('web.technician.login-form') }}">ورود</a></span>
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
