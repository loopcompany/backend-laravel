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
                                <h2 class="fw-bold fs-3">تأیید کد بازیابی</h2>
                                <p class="mb-0 h6 fw-light">کد بازیابی به شماره شما ارسال شد</p>
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
                                <h1 class="fs-4">تأیید کد بازیابی</h1>
                                <p class="mb-4">کد ۶ رقمی ارسال شده به شماره {{ session('phone') ?? 'موبایل شما' }} را وارد کنید.</p>

                                <!-- Form START -->
                                <form method="POST" action="{{ route('web.technician.verify-reset-code') }}">
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

                                    <!-- Reset Code -->
                                    <div class="mb-4">
                                        <label for="code" class="form-label">کد بازیابی *</label>
                                        <div class="input-group input-group-lg">
                                            <span
                                                class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i
                                                    class="bi bi-shield-lock-fill"></i></span>
                                            <input type="text" class="form-control border-0 bg-light rounded-end ps-1"
                                                placeholder="۶ رقم" id="code" name="code" 
                                                value="{{ old('code') }}" maxlength="6" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Button -->
                                    <div class="align-items-center mt-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">تأیید و ادامه</button>
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
@endsection
