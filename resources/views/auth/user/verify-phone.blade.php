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
                                <h2 class="fw-bold fs-3">تایید شماره موبایل</h2>
                                <p class="mb-0 h6 fw-light">کد تایید ارسال شده را وارد کنید</p>
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
                                <span class="mb-0 fs-1">📱</span>
                                <h1 class="fs-4">تایید شماره موبایل</h1>
                                <p class="mb-4">
                                    کد تایید به شماره <strong>{{ session('phone') }}</strong> ارسال شد. 
                                    کد ۶ رقمی را در زیر وارد کنید تا ثبت‌نام تکمیل شود.
                                </p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.verify-phone') }}">
                                    @csrf
                                    
                                    <!-- Hidden fields -->
                                    <input type="hidden" name="phone" value="{{ session('phone') }}">
                                    <input type="hidden" name="user_id" value="{{ session('user_id') }}">
                                    
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

                                    @if(session('error'))
                                        <div class="alert alert-warning">
                                            {{ session('error') }}
                                        </div>
                                    @endif

                                    <!-- Verification Code -->
                                    <div class="mb-4">
                                        <label for="verification_code" class="form-label">کد تایید *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-shield-lock-fill"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1 text-center"
                                                placeholder="123456" id="verification_code" name="verification_code" 
                                                value="{{ old('verification_code') }}" required maxlength="6" 
                                                style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                                        </div>
                                        <div class="form-text">کد ۶ رقمی ارسال شده به شماره موبایل</div>
                                    </div>

                                    <!-- Button -->
                                    <div class="align-items-center mt-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">تایید و تکمیل ثبت‌نام</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Form END -->

                                <!-- Resend Code -->
                                <div class="mt-4 text-center">
                                    <form method="POST" action="{{ route('web.resend-verification-code') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="phone" value="{{ session('phone') }}">
                                        <button type="submit" class="btn btn-link p-0 text-decoration-none">
                                            کد را دریافت نکردید؟ ارسال مجدد
                                        </button>
                                    </form>
                                </div>

                                <!-- Divider -->
                                <div class="position-relative my-4">
                                    <hr>
                                    <p class="small position-absolute top-50 start-50 translate-middle bg-body px-5">یـا</p>
                                </div>

                                <!-- Back to register -->
                                <div class="mt-4 text-center">
                                    <span>اطلاعات اشتباه وارد کردید؟ <a href="{{ route('web.register') }}">ثبت‌نام مجدد</a></span>
                                </div>
                            </div>
                        </div> <!-- Row END -->
                    </div>
                </div> <!-- Row END -->
            </div>
        </section>
    </main>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto focus on verification code input
    const codeInput = document.getElementById('verification_code');
    if (codeInput) {
        codeInput.focus();
        
        // Only allow numbers
        codeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
        });
    }
});
</script>