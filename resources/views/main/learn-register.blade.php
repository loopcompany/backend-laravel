@extends('layout.main.header')
@section('content')
    <picture class="learn-hero">
        <source
            media="(max-width: 767px)"
            srcset="{{ asset('assets/new-style/mobile/learn.png') }}"
            width="1024"
            height="1536"
        >
        <img
            src="{{ asset('assets/new-style/learn.png') }}"
            alt="آکادمی لوپ؛ از یادگیری تا ورود به بازار کار"
            width="1536"
            height="1024"
            fetchpriority="high"
        >
    </picture>
    <div class="container pt-90">
        @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="section_title text_center mb-55">

                    <div class="section_main_title">
                        <p style="font-size: x-large;line-height: 35px;text-align: justify;text-align-last: center;">لوپ با هدف توسعه علم فناوری رایانه در کشور و ایجاد فرصت‌های شغلی مدرن برای جوانان، مسیر تازه‌ای را پیش روی علاقه‌مندان قرار داده است.

اگر به دنیای رایانه علاقه دارید، حتی اگر هم‌اکنون در شغل یا کسب‌وکار دیگری فعالیت می‌کنید، می‌توانید با آموزش‌های تخصصی لوپ مهارت‌های لازم را فرا بگیرید و در صورت تمایل، به‌عنوان تکنسین رایانه وارد بازار کار شوید.

اپلیکیشن درخواست تکنسین لوپ، تحولی نو در ارائه خدمات رایانه‌ای است؛ تحولی با محوریت امنیت اطلاعات کاربران، اعتمادسازی در صنعت خدمات رایانه، صرفه‌جویی در زمان، کاهش نیاز به جابه‌جایی دستگاه‌های تعمیری، شفافیت هزینه‌ها و ایجاد فرصت‌های کارآفرینی برای نسل جوان.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section_title text_center mb-55">
                    <div class="section_sub_title uppercase mb-3">
                        <h6 style="color:#dca82c">دپارتمان آموزشی لوپ</h6>
                    </div>
                    <div class="section_main_title">
                        <h1 >ثبت نام کلاس‌های آموزشی رایگان رایانه برای همه افراد و سنین ۱۸ تا ۴۵ سال جهت توسعه علم و فناوری رایانه در کشور</h1>
                        <h1 >و در صورت تمایل بعنوان تکنسین رایانه در لوپ استخدام شوید</h1>
                    </div>
                    <div class="em_bar">
                        <div class="em_bar_bg"></div>
                    </div>
                    <p style="font-size: x-large;line-height: 35px;text-align: justify;text-align-last: center;padding-top:10px">
                        کلاس‌های آموزشی نرم افزار رایانه شامل نصب سیستم عامل(ویندوز) و برنامه‌های رایانه کلاس‌های آموزشی سخت افزار رایانه شامل آشنایی کامل با قطعات و عیب یابی و رفع آن (بجز تعمیرات برد) لازم به ذکر است که کلاس های سخت افزار رایانه پس از گذراندن دوره کلاس های نرم افزار رایانه کامل و تجربه لازم در این زمینه، آغاز خواهد شد.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="contact-us-area pt-90 pb-90">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center pb-50">
                        <h5>ثبت نام دوره های آموزشی</h5>
                        <h2>فرم عضویت در خانواده لوپ</h2>
                        <div class="section-line"></div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12 offset-lg-1">
                    <div class="contact-form-box"
                        style="background: #1d2839;padding: 40px;border-radius: 15px;box-shadow: 0 10px 30px 3px rgba(99, 119, 238, 0.4);">
                        <form action="{{ route('loop.learn.submit') }}" method="POST" id="registration-form">
                            @csrf

                            <!-- بخش اول: انتخاب دوره -->
                            <div class="row">
                                <div class="col-lg-12 mb-4">
                                    <h4 style="color: #0c5adb; border-right: 4px solid #0c5adb; padding-right: 15px;">مشخصات
                                        دوره</h4>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>ثبت نام در کلاس های: <span class="text-danger">*</span></label>
                                        <select name="class" class="form-control" required>
                                            <option value="">انتخاب کنید...</option>
                                            <option value="آموزش نصب نرم افزار رایانه">آموزش نصب نرم افزار رایانه</option>
                                            <option value="آموزش سخت افزار رایانه">آموزش سخت افزار رایانه</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>ثبت نام بعنوان: <span class="text-danger">*</span></label>
                                        <select name="register_as" class="form-control" required>
                                            <option value="">انتخاب کنید...</option>
                                            <option value="تکنسین نرم افزار رایانه">تکنسین نرم افزار رایانه</option>
                                            <option value="تکنسین سخت افزار رایانه">تکنسین سخت افزار رایانه</option>
                                            <option value="فعلا قصد ندارم">فعلا قصد ندارم</option>
                                            <option value="هیچ کدام">هیچ کدام</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr class="mt-4 mb-4">

                            <!-- بخش دوم: میزان تسلط -->
                            <div class="row">
                                <div class="col-lg-12 mb-4">
                                    <h4 style="color: #0c5adb; border-right: 4px solid #0c5adb; padding-right: 15px;">تسلط
                                        به کارکرد رایانه <span class="text-danger">*</span></h4>
                                </div>

                                <div class="col-lg-6">
                                    <label class="d-block mb-2"><b>(نرم افزار) نصب ویندوزها و برنامه ها: <span class="text-danger">*</span></b></label>
                                    <div class="custom-radio-group">
                                        @foreach (['عالی', 'خوب', 'متوسط', 'ضعیف', 'هیچ اطلاعاتی از رایانه ندارم'] as $level)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="mastery_soft_level"
                                                    value="{{ $level }}" id="soft_{{ $loop->index }}" required>
                                                <label class="form-check-label mr-4"
                                                    for="soft_{{ $loop->index }}">{{ $level }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <label class="d-block mb-2"><b>سخت افزار (عیب یابی و رفع آن): <span class="text-danger">*</span></b></label>
                                    <div class="custom-radio-group">
                                        @foreach (['عالی', 'خوب', 'متوسط', 'ضعیف', 'هیچ اطلاعاتی از رایانه ندارم'] as $level)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="mastery_hard_level"
                                                    value="{{ $level }}" id="hard_{{ $loop->index }}" required>
                                                <label class="form-check-label mr-4"
                                                    for="hard_{{ $loop->index }}">{{ $level }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-lg-12 mt-4">
                                    <label class="d-block mb-2"><b>آیا پس از آموزش و تسط کامل موافق به استخدام بعنوان تکنسین
                                            میدانی هستید؟ <span class="text-danger">*</span></b></label>
                                    <div class="d-flex flex-wrap">
                                        @foreach (['بله، تکنسین نرم افزار', 'بله، تکنسین سخت افزار', 'بله، هردو مورد', 'خیر، فقط کلاس های آموزشی'] as $goal)
                                            <div class="form-check ml-4">
                                                <input class="form-check-input" type="radio" name="goal"
                                                    value="{{ $goal }}" id="goal_{{ $loop->index }}" required>
                                                <label class="form-check-label mr-4"
                                                    for="goal_{{ $loop->index }}">{{ $goal }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <hr class="mt-4 mb-4">

                            <!-- بخش سوم: مشخصات فردی -->
                            <div class="row">
                                <div class="col-lg-12 mb-4">
                                    <h4 style="color: #0c5adb; border-right: 4px solid #0c5adb; padding-right: 15px;">مشخصات
                                        فردی</h4>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label>نام <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="نام" required>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label>نام خانوادگی<span class="text-danger">*</span></label>
                                    <input type="text" name="lname" class="form-control" placeholder="نام خانوادگی"
                                        required>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label>تاریخ تولد<span class="text-danger">*</span></label>
                                    <input type="text" name="birth_date" class="form-control"
                                        placeholder="تاریخ تولد" data-jdp required>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label>وضعیت تاهل<span class="text-danger">*</span></label>
                                    <select name="marriage" class="form-control" required>
                                        <option value="">تاهل</option>
                                        <option value="مجرد">مجرد</option>
                                        <option value="متاهل">متاهل</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label>جنسیت <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">جنسیت</option>
                                        <option value="خانم">خانم</option>
                                        <option value="آقا">آقا</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label>ملیت <span class="text-danger">*</span></label>
                                    <select name="nationality" class="form-control" required>
                                        <option value="ایرانی">ایرانی</option>
                                        <option value="خارجی">خارجی</option>
                                    </select>
                                </div>
                                <div class="col-lg-6 col-md-6 mb-3">
                                    <label>تحصیلات <span class="text-danger">*</span></label>
                                    <input type="text" name="education" class="form-control" placeholder="تحصیلات"
                                        required>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <label>شماره همراه <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" placeholder="شماره همراه"
                                        required>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <label>شماره ثابت <span class="text-danger">*</span></label>
                                    <input type="text" name="telephone" class="form-control"
                                        placeholder="شماره ثابت">
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <label>آدرس محل سکونت <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control" placeholder="آدرس محل سکونت" rows="3" required></textarea>
                                </div>
                            </div>

                            <hr class="mt-4 mb-4">

                            <!-- بخش چهارم: امکانات -->
                            <div class="row">
                                <div class="col-lg-6">
                                    <label class="d-block mb-2"><b>دارای وسیله نقلیه: <span class="text-danger">*</span></b></label>
                                    <div class="d-flex">
                                        <div class="form-check ml-4"><input type="radio" name="vehicle"
                                                value="موتور سیکلت" class="form-check-input" id="v1"><label
                                                class="form-check-label mr-4" for="v1">موتور سیکلت</label></div>
                                        <div class="form-check ml-4"><input type="radio" name="vehicle" value="خودرو"
                                                class="form-check-input" id="v2"><label
                                                class="form-check-label mr-4" for="v2">خودرو</label></div>
                                        <div class="form-check ml-4"><input type="radio" name="vehicle" value="ندارم"
                                                class="form-check-input" id="v3"><label
                                                class="form-check-label mr-4" for="v3">ندارم</label></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <label class="d-block mb-2"><b>دارای گواهینامه: <span class="text-danger">*</span></b></label>
                                    <div class="d-flex">
                                        <div class="form-check ml-4"><input type="radio" name="certificate"
                                                value="موتورسیکلت" class="form-check-input" id="c1"><label
                                                class="form-check-label mr-4" for="c1">موتور سیکلت</label></div>
                                        <div class="form-check ml-4"><input type="radio" name="certificate"
                                                value="ماشین" class="form-check-input" id="c2"><label
                                                class="form-check-label mr-4" for="c2">خودرو</label></div>
                                        <div class="form-check ml-4"><input type="radio" name="certificate"
                                                value="ندارم" class="form-check-input" id="c3"><label
                                                class="form-check-label mr-4" for="c3">ندارم</label></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-5">
                                <div class="col-lg-12 text-center">
                                    <div class="contact-button">
                                        <button type="submit" class="btn btn-primary"
                                            style="background: #0c5adb; padding: 15px 50px; border-radius: 5px; border: none; font-weight: bold;">ثبت
                                            اطلاعات</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .learn-hero,
        .learn-hero img {
            display: block;
            width: 100%;
        }

        .learn-hero img {
            height: auto;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .form-control {
            height: 50px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            background: #fff;
        }

        .form-control:focus {
            border-color: #0c5adb;
            box-shadow: 0 0 10px rgba(12, 90, 219, 0.1);
        }

        textarea.form-control {
            height: auto;
        }

        .form-check-input {
            margin-top: 6px;
        }

        .form-check-label {
            cursor: pointer;
        }

        hr {
            border-top: 1px dashed #ccc;
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            jalaliDatepicker.startWatch({
                minDate: "attr",
                maxDate: "attr"
            });
        });
    </script>

@endsection
