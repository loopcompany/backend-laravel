@extends('layout.main.header')

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
                            <!-- SVG Image -->
                            <img src="{{ asset('assets/images/element/02.svg') }}" class="mt-5" alt="">
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
                                <form method="POST" action="{{ route('web.technician.register') }}" enctype="multipart/form-data">
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

                                    <!-- اطلاعات شخصی -->
                                    <h5 class="mb-3 mt-4">اطلاعات شخصی</h5>
                                    
                                    <div class="row g-3">
                                        <!-- نام کامل -->
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">نام و نام خانوادگی *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                value="{{ old('name') }}" required>
                                        </div>
                                        
                                        <!-- کد ملی -->
                                        <div class="col-md-6">
                                            <label for="melicode" class="form-label">کد ملی</label>
                                            <input type="text" class="form-control" id="melicode" name="melicode" 
                                                value="{{ old('melicode') }}" maxlength="10" pattern="[0-9]{10}">
                                        </div>
                                        
                                        <!-- شماره موبایل -->
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">شماره موبایل *</label>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                value="{{ old('phone') }}" placeholder="09123456789" required>
                                        </div>
                                        
                                        <!-- ایمیل -->
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">ایمیل</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                value="{{ old('email') }}">
                                        </div>
                                        
                                        <!-- تاریخ تولد -->
                                        <div class="col-md-6">
                                            <label for="birth_date" class="form-label">تاریخ تولد</label>
                                            <input type="text" class="form-control" id="birth_date" name="birth_date" 
                                                data-jdp value="{{ old('birth_date') }}">
                                        </div>
                                        
                                        <!-- نام پدر -->
                                        <div class="col-md-6">
                                            <label for="father_name" class="form-label">نام پدر *</label>
                                            <input type="text" class="form-control" id="father_name" name="father_name" 
                                                value="{{ old('father_name') }}" required>
                                        </div>
                                        
                                        <!-- محل صدور -->
                                        <div class="col-md-6">
                                            <label for="issued_from" class="form-label">محل صدور شناسنامه *</label>
                                            <input type="text" class="form-control" id="issued_from" name="issued_from" 
                                                value="{{ old('issued_from') }}" required>
                                        </div>
                                        
                                        <!-- شماره شناسنامه -->
                                        <div class="col-md-6">
                                            <label for="serial_number" class="form-label">شماره شناسنامه *</label>
                                            <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                                value="{{ old('serial_number') }}" required>
                                        </div>
                                        
                                        <!-- شماره کارت ملی -->
                                        <div class="col-md-6">
                                            <label for="id_card_number" class="form-label">شماره کارت شناسایی *</label>
                                            <input type="text" class="form-control" id="id_card_number" name="id_card_number" 
                                                value="{{ old('id_card_number') }}" required>
                                        </div>
                                        
                                        <!-- وضعیت تأهل -->
                                        <div class="col-md-6">
                                            <label for="marital_status" class="form-label">وضعیت تأهل *</label>
                                            <select class="form-select" id="marital_status" name="marital_status" dir="rtl" required>
                                                <option value="">&#x202B;انتخاب کنید&#x202C;</option>
                                                <option value="مجرد" {{ old('marital_status') == 'مجرد' ? 'selected' : '' }}>&#x202B;مجرد&#x202C;</option>
                                                <option value="متأهل" {{ old('marital_status') == 'متأهل' ? 'selected' : '' }}>&#x202B;متأهل&#x202C;</option>
                                            </select>
                                        </div>
                                        
                                        <!-- وضعیت نظام وظیفه -->
                                        <div class="col-md-6">
                                            <label for="military_status" class="form-label">وضعیت نظام وظیفه *</label>
                                            <select class="form-select" id="military_status" name="military_status" dir="rtl" required>
                                                <option value="">&#x202B;انتخاب کنید&#x202C;</option>
                                                <option value="معاف" {{ old('military_status') == 'معاف' ? 'selected' : '' }}>&#x202B;معاف&#x202C;</option>
                                                <option value="در حال خدمت" {{ old('military_status') == 'در حال خدمت' ? 'selected' : '' }}>&#x202B;در حال خدمت&#x202C;</option>
                                                <option value="پایان خدمت" {{ old('military_status') == 'پایان خدمت' ? 'selected' : '' }}>&#x202B;پایان خدمت&#x202C;</option>
                                            </select>
                                        </div>
                                        
                                        <!-- وضعیت تحصیلات -->
                                        <div class="col-md-6">
                                            <label for="education_status" class="form-label">وضعیت تحصیلات *</label>
                                            <input type="text" class="form-control" id="education_status" name="education_status" 
                                                value="{{ old('education_status') }}" placeholder="مثال: کارشناسی کامپیوتر" required>
                                        </div>
                                    </div>

                                    <!-- اطلاعات تماس -->
                                    <h5 class="mb-3 mt-4">اطلاعات تماس</h5>
                                    
                                    <div class="row g-3">
                                        <!-- تلفن ثابت -->
                                        <div class="col-md-6">
                                            <label for="telephone" class="form-label">تلفن ثابت *</label>
                                            <input type="text" class="form-control" id="telephone" name="telephone" 
                                                value="{{ old('telephone') }}" placeholder="021xxxxxxxx" required>
                                        </div>
                                        
                                        <!-- شماره موبایل اضافی -->
                                        <div class="col-md-6">
                                            <label for="mobile" class="form-label">شماره موبایل (تکراری) *</label>
                                            <input type="text" class="form-control" id="mobile" name="mobile" 
                                                value="{{ old('mobile') }}" placeholder="09123456789" required>
                                        </div>
                                        
                                        <!-- کد پستی -->
                                        <div class="col-md-6">
                                            <label for="home_postal_code" class="form-label">کد پستی منزل *</label>
                                            <input type="text" class="form-control" id="home_postal_code" name="home_postal_code" 
                                                value="{{ old('home_postal_code') }}" maxlength="10" pattern="[0-9]{10}" required>
                                        </div>
                                        
                                        <!-- منطقه -->
                                        <div class="col-md-6">
                                            <label for="region" class="form-label">منطقه *</label>
                                            <input type="text" class="form-control" id="region" name="region" 
                                                value="{{ old('region') }}" required>
                                        </div>
                                        
                                        <!-- شهر -->
                                        <div class="col-md-6">
                                            <label for="city" class="form-label">شهر *</label>
                                            <input type="text" class="form-control" id="city" name="city" 
                                                value="{{ old('city') }}" required>
                                        </div>
                                        
                                        <!-- آدرس منزل -->
                                        <div class="col-12">
                                            <label for="home_address" class="form-label">آدرس کامل منزل *</label>
                                            <textarea class="form-control" id="home_address" name="home_address" 
                                                rows="3" required>{{ old('home_address') }}</textarea>
                                        </div>
                                    </div>

                                    <!-- اطلاعات گواهینامه و وسیله نقلیه -->
                                    <h5 class="mb-3 mt-4">اطلاعات گواهینامه و وسیله نقلیه</h5>
                                    
                                    <div class="row g-3">
                                        <!-- تاریخ اعتبار گواهینامه -->
                                        <div class="col-md-6">
                                            <label for="licence_date" class="form-label">تاریخ اعتبار گواهینامه *</label>
                                            <input type="text" class="form-control" id="licence_date" name="licence_date" 
                                                data-jdp value="{{ old('licence_date') }}" required>
                                        </div>
                                        
                                        <!-- نوع وسیله نقلیه -->
                                        <div class="col-md-6">
                                            <label for="vehicle_type" class="form-label">نوع وسیله نقلیه *</label>
                                            <input type="text" class="form-control" id="vehicle_type" name="vehicle_type" 
                                                value="{{ old('vehicle_type') }}" placeholder="مثال: پراید، پژو، موتور سیکلت" required>
                                        </div>
                                    </div>

                                    <!-- مهارت‌ها و توانایی‌ها -->
                                    <h5 class="mb-3 mt-4">مهارت‌ها و توانایی‌ها</h5>
                                    
                                    <div class="row g-3">
                                        <!-- ایده و خلاقیت -->
                                        <div class="col-12">
                                            <label for="idea" class="form-label">ایده و خلاقیت *</label>
                                            <textarea class="form-control" id="idea" name="idea" 
                                                rows="2" required>{{ old('idea') }}</textarea>
                                        </div>
                                        
                                        <!-- تسلط نرم‌افزاری -->
                                        <div class="col-md-6">
                                            <label for="software_skill" class="form-label">تسلط نرم‌افزاری *</label>
                                            <textarea class="form-control" id="software_skill" name="software_skill" 
                                                rows="3" required>{{ old('software_skill') }}</textarea>
                                        </div>
                                        
                                        <!-- تسلط سخت‌افزاری -->
                                        <div class="col-md-6">
                                            <label for="hardware_skill" class="form-label">تسلط سخت‌افزاری *</label>
                                            <textarea class="form-control" id="hardware_skill" name="hardware_skill" 
                                                rows="3" required>{{ old('hardware_skill') }}</textarea>
                                        </div>
                                        
                                        <!-- نقاط ضعف نرم‌افزاری -->
                                        <div class="col-md-6">
                                            <label for="software_weakness" class="form-label">نقاط ضعف نرم‌افزاری *</label>
                                            <textarea class="form-control" id="software_weakness" name="software_weakness" 
                                                rows="3" required>{{ old('software_weakness') }}</textarea>
                                        </div>
                                        
                                        <!-- نقاط ضعف سخت‌افزاری -->
                                        <div class="col-md-6">
                                            <label for="hardware_weakness" class="form-label">نقاط ضعف سخت‌افزاری *</label>
                                            <textarea class="form-control" id="hardware_weakness" name="hardware_weakness" 
                                                rows="3" required>{{ old('hardware_weakness') }}</textarea>
                                        </div>
                                        
                                        <!-- رزومه -->
                                        <div class="col-12">
                                            <label for="resume" class="form-label">رزومه (PDF, DOC, DOCX - حداکثر 10 مگابایت)</label>
                                            <input type="file" class="form-control" id="resume" name="resume" 
                                                accept=".pdf,.doc,.docx">
                                        </div>
                                        
                                        <!-- کد پرسنلی معرف -->
                                        <div class="col-12">
                                            <label for="other_referral_code" class="form-label">کد پرسنلی معرف (اختیاری)</label>
                                            <input type="text" class="form-control" id="other_referral_code" name="other_referral_code" 
                                                value="{{ old('other_referral_code') }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Button -->
                                    <div class="align-items-center mt-4">
                                        <div class="d-grid">
                                            <button class="btn btn-primary mb-0" type="submit">ثبت نام</button>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Jalali DatePicker for all fields with data-jdp attribute
            jalaliDatepicker.startWatch({
                minDate: "attr",
                maxDate: "attr",
                separatorChar: "/",
                separator: "/",
                hasMaxYear: true,
                hasMinYear: true
            });

            // Function to convert yyyy/mm/dd to yyyy-mm-dd
            function convertDateFormat(dateString) {
                if (!dateString) return '';
                return dateString.replace(/\//g, '-');
            }

            // Get the form
            const form = document.querySelector('form[action="{{ route('web.technician.register') }}"]');
            
            // Add submit event listener
            form.addEventListener('submit', function(e) {
                // Get all date input fields
                const birthDateInput = document.getElementById('birth_date');
                const licenceDateInput = document.getElementById('licence_date');
                
                // Convert format from yyyy/mm/dd to yyyy-mm-dd before submission
                if (birthDateInput && birthDateInput.value) {
                    birthDateInput.value = convertDateFormat(birthDateInput.value);
                }
                
                if (licenceDateInput && licenceDateInput.value) {
                    licenceDateInput.value = convertDateFormat(licenceDateInput.value);
                }
            });
        });
    </script>
@endsection
