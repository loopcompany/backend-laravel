@extends('layout.main.header')
@section('content')
    <div class="slider_area d-flex align-items-center slider10" id="home">
        <div class="container">
            <div class="row">
                <!--Start Single Portfolio -->
                <div class="col-lg-12">
                    <div class="single_slider">
                        <div class="slider_content">
                            <div class="slider_text">
                                <div class="slider_text_inner">
                                    <h5>خدمات تعمیر تخصصی و مطمئن</h5>
                                    <h1>تعمیرات تخصصی دستگاه‌های کامپیوتری با لوپ</h1>
                                </div>
                                <div class="slider_text_desc pt-4">
                                    <p>
                                        از کیس و مانیتور گرفته تا لپ‌تاپ، پرینتر، هارد دیسک
                                        در لوپ، همه‌چیز توسط تعمیرکاران تأییدشده، سریع، دقیق و با گارانتی انجام می‌شود.
                                    </p>
                                </div>
                                <div class="slider_button pt-5 d-flex">
                                    <style>
                                        @keyframes bgChangeAndBlink {
                                            0% {
                                                background-color: red;
                                                opacity: 1;
                                            }

                                            25% {
                                                background-color: blue;
                                                opacity: 0.7;
                                            }

                                            50% {
                                                background-color: #f0c400;
                                                opacity: 1;
                                            }

                                            75% {
                                                background-color: #ff03d5;
                                                opacity: 0.7;
                                            }

                                            100% {
                                                background-color: red;
                                                opacity: 1;
                                            }
                                        }

                                        .blinking-bg-link {
                                            animation: bgChangeAndBlink 1.5s infinite;
                                            color: #ffffff !important;
                                            /* رنگ متن کاملا ثابت است */
                                            font-weight: bold;
                                            text-decoration: none;
                                            display: block;
                                            text-align: center;
                                            margin-top: 15px;
                                            padding: 10px;
                                            /* اضافه شدن پدینگ برای دیده شدن بهتر پس‌زمینه */
                                            border-radius: 8px;
                                            /* کمی انحنا برای زیبایی */
                                            font-size: 16px;
                                        }
                                    </style>

                                    <div style="position: relative;z-index: 44;">
                                        <div
                                            style="background: #ffd700;display: flex;align-items: center;justify-content: center;border-radius: 10px;padding-inline: 15px;padding-top: 10px;padding-bottom: 10px;">
                                            <p style="font-weight: 900;margin: 0px;color: #000;">همین حالا
                                                اپلیکیشن لوپ را نصب کن</p>
                                        </div>
                                        <a href="{{ route('loop.learn') }}" class="blinking-bg-link">
                                            کلاس‌های آموزش رایگان رایانه
                                        </a>

                                    </div>
                                    <div class="circle-icon">
                                        <i class="fa fa-arrow-left arrow-left"></i>
                                        <i class="fa fa-arrow-down arrow-down"></i>
                                    </div>
                                    <div class="button"
                                        style="display: flex;flex-direction: column;align-items: flex-start;gap: 15px;">
                                        <div class="d-flex align-items-center justify-content-center" style="gap: 15px">
                                            <img src="{{ asset('assets/images/appicon/usericon.png') }}"
                                                style="height: 60px; width: 60px; border-radius: 100px; object-fit: cover;" />
                                            <a href="#">دانلود اپلیکیشن کاربر<i class="fa fa-chevron-left"></i></a>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center" style="gap: 15px">
                                            <img src="{{ asset('assets/images/appicon/techicon.png') }}"
                                                style="height: 60px; width: 60px; border-radius: 100px; object-fit: cover;" />
                                            <a href="#">دانلود اپلیکیشن تکنسین<i class="fa fa-chevron-left"></i></a>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lines">
            <div class="line"></div>
            <div class="line"></div>
            <div class="line"></div>
        </div>
    </div>
    <!--==================================================-->
    <!----- End Techno Slider Area ----->
    <!--==================================================-->

    <!--==================================================-->
    <!----- Start Techno Feature Area ----->
    <!--==================================================-->
    <div class="feature_area pb-2">
        <div class="container">
            <div class="row nagative_margin4">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="single_feature_six">
                        <div class="single_feature_six_icon ml-3">
                            <i class="fa fa-thumbs-o-up" aria-hidden="true"></i>
                        </div>
                        <div class="single_feature_six_content white">
                            <h5>حرفه‌ای و مجرب</h5>
                            <p>تعمیرکاران لوپ همگی دارای تجربه‌ی تخصصی در حوزه‌ی سخت‌افزار و الکترونیک هستند.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="single_feature_six">
                        <div class="single_feature_six_icon ml-3">
                            <i class="fa fa-clock-o" aria-hidden="true"></i>
                        </div>
                        <div class="single_feature_six_content white">
                            <h5>پشتیبانی 24/7</h5>
                            <p>تیم پشتیبانی لوپ همراه شماست تا تجربه‌ای مطمئن و بی‌دغدغه داشته باشید.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="single_feature_six">
                        <div class="single_feature_six_icon ml-3">
                            <i class="fa fa-user-o" aria-hidden="true"></i>
                        </div>
                        <div class="single_feature_six_content white">
                            <h5>خدمات قابل اعتماد</h5>
                            <p>هر تعمیر در لوپ با قطعات اصلی و ضمانت انجام می‌شود. اعتماد شما بزرگ‌ترین سرمایه‌ی ماست.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!--==================================================-->
    <!----- End Techno Feature Area ----->
    <!--==================================================-->


    <!--==================================================-->
    <!----- Start Techno About Area ----->
    <!--==================================================-->
    <div class="about_area pt-100 pb-100">
        <div class="container">
            <div class="row align-items-center">

                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-6">
                    <div class="section_title text_left mb-40 mt-3">
                        <div class="section_sub_title uppercase mb-3">
                            <h6>درباره لوپ</h6>
                        </div>
                        <div class="section_main_title">
                            <h1>راه‌حلی هوشمند، مطمئن و سریع</h1>
                            <h1>برای تعمیر دستگاه‌های <span> کامپیوتری </span></h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                        <div class="section_content_text pt-4">
                            <p>
                                لوپ، پلی است میان کاربران و تعمیرکاران حرفه‌ای در حوزه‌ی لپ‌تاپ، کیس، چاپگر، مانیتور، هارد
                                دیسک.
                            </p>
                            <p>
                                ما با هدف ایجاد اعتماد، شفافیت و سرعت در خدمات تعمیرات، بستری طراحی کرده‌ایم که کاربران
                                بتوانند بدون اتلاف وقت، تعمیرکار مورد اعتماد خود را انتخاب کرده و از کیفیت خدمات مطمئن
                                باشند.
                                <br>
                                تیم لوپ متشکل از تکنسین‌ها فنی، پشتیبانان همیشه‌فعال و تکنسین‌های تأییدشده است تا تجربه‌ی
                                شما
                                از تعمیر، ساده‌تر و ایمن‌تر از همیشه باشد.
                            </p>
                        </div>
                    </div>

                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-6">
                    <div class="single_about_thumb mb-3">
                        <div class="single_about_thumb_inner">
                            <img src="assets/images/about-4.png" alt="" />
                        </div>
                        <div class="border_ift"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!--==================================================-->
    <!----- End Techno About Area ----->
    <!--==================================================-->

    <!--==================================================-->
    <!----- Start Techno Service Area ----->
    <!--==================================================-->

    <div class="service_area pt-85 pb-130" style="background-image:url(assets/images/slider/bg2.jpg)";>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title text_center white mb-55">
                        <div class="section_sub_title uppercase mb-3">
                            <h6>شروع کار با لوپ</h6>
                        </div>
                        <div class="section_main_title">
                            <h1>با ۴ روش زیر می‌توانید</h1>
                            <h1>از خدمات لوپ بهره مند شوید</h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="service_style_eight">
                        <div class="service_style_eight_icon">
                            <div class="icon">
                                <i class="flaticon-mobile-app"></i>
                            </div>
                        </div>
                        <div class="service_style_eight_content white pt-4">
                            <h4>دانلود و نصب اپلیکیشن کاربر</h4>

                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="service_style_eight">
                        <div class="service_style_eight_icon">
                            <div class="icon">
                                <i class="flaticon-global"></i>
                            </div>
                        </div>
                        <div class="service_style_eight_content white pt-4">
                            <h4>ثبت سفارش از طریق سایت</h4>

                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="service_style_eight">
                        <div class="service_style_eight_icon">
                            <div class="icon">
                                <i class="flaticon-call"></i>
                            </div>
                        </div>
                        <div class="service_style_eight_content white pt-4">
                            <h4>از طریق تماس تلفنی و ثبت سفارش با کارشناسان لوپ</h4>

                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="service_style_eight">
                        <div class="service_style_eight_icon">
                            <div class="icon">
                                <i class="flaticon-content"></i>
                            </div>
                        </div>
                        <div class="service_style_eight_content white pt-4">
                            <h4>بصورت حضوری و مراجعه به دفاتر لوپ</h4>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!--==================================================-->
    <!----- End Techno Services Area ----->
    <!--==================================================-->






    <!--==================================================-->
    <!----- Start Techno Counter Area ----->
    <!--==================================================-->
    <div class="counter_area">
        <div class="container">
            <div class="row cntr_bg_up nagative_margin pt-50 pb-45 pr-4">
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="single_counter mb-4">
                        <div class="single_counter_icon_two">
                            <div class="icon">
                                <i class="flaticon-developer"></i>
                            </div>
                        </div>
                        <div class="single_counter_content">
                            <div class="countr_text">
                                <h1><span class="counter">992 </span><span>+</span> </h1>
                            </div>
                            <div class="counter_desc">
                                <h6>تماس دریافتی</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="single_counter mb-4">
                        <div class="single_counter_icon_two">
                            <div class="icon">
                                <i class="flaticon-developer"></i>
                            </div>
                        </div>
                        <div class="single_counter_content">
                            <div class="countr_text">
                                <h1><span class="counter">10 </span><span>K</span> </h1>
                            </div>
                            <div class="counter_desc">
                                <h6>پروژه</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="single_counter mb-4">
                        <div class="single_counter_icon_two">
                            <div class="icon">
                                <i class="flaticon-developer"></i>
                            </div>
                        </div>
                        <div class="single_counter_content">
                            <div class="countr_text">
                                <h1><span class="counter">128 </span><span>K</span> </h1>
                            </div>
                            <div class="counter_desc">
                                <h6>حساب</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="single_counter mb-4">
                        <div class="single_counter_icon_two">
                            <div class="icon">
                                <i class="flaticon-developer"></i>
                            </div>
                        </div>
                        <div class="single_counter_content">
                            <div class="countr_text">
                                <h1><span class="counter">147 </span><span>+</span> </h1>
                            </div>
                            <div class="counter_desc">
                                <h6>مشتری راضی</h6>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!--==================================================-->
    <!----- End Techno Counter Area ----->
    <!--==================================================-->


    <!--==================================================-->
    <!----- Start Techno Testimonial One Area ----->
    <!--==================================================-->
    @if (count($testimonials) > 0)
        <div class="testimonial_area pt-70 pb-70">
            <div class="container">
                <div class="row">
                    <!-- Start Section Tile -->
                    <div class="col-lg-12">
                        <div class="section_title text_center mb-50 mt-3">
                            <div class="section_sub_title uppercase mb-3">
                                <h6>نظرات واقعی</h6>
                            </div>
                            <div class="section_main_title">
                                <h1>نظرات</h1>
                                <h1>مشتریان خرسند ما</h1>
                            </div>
                            <div class="em_bar">
                                <div class="em_bar_bg"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="row">
                            <!--testimonial owl curousel -->
                            <div class="testimonial_list owl-carousel curosel-style">
                                @foreach ($testimonials as $testimonial)
                                    <!-- Start Single Testimonial -->
                                    <div class="col-lg-12">
                                        <div class="single_testimonial_two mt-3 mb-5">
                                            <div class="single_testimonial_content_two">
                                                <div class="single_testimonial_thumb_two mb-4">
                                                    <img src="{{ $testimonial['user']['avatar'] ?? 'assets/default.jpg' }}"
                                                        alt="{{ $testimonial['user']['name'] ?? 'کاربر' }}"
                                                        style="width: 70px; height: 70px;" />
                                                </div>
                                                <div class="single_testimonial_content_text_two mb-4">
                                                    <p>{{ $testimonial['description'] ?? 'نظری ثبت نشده است.' }}</p>
                                                </div>
                                                <div class="single_testimonial_content_title_two mt-4">
                                                    <h4>{{ $testimonial['user']['first_name'] ?? 'کاربر' }}
                                                        {{ $testimonial['user']['last_name'] ?? 'عزیز' }}</h4>
                                                    <span>
                                                        @if (($testimonial['app_rate'] ?? '') === 'خوب')
                                                            راضی از اپلیکیشن
                                                        @elseif(($testimonial['tech_rate'] ?? '') === 'خوب')
                                                            راضی از تکنسین
                                                        @elseif(($testimonial['support_rate'] ?? '') === 'خوب')
                                                            راضی از پشتیبانی
                                                        @else
                                                            مشتری
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!--==================================================-->
    <!----- End Techno Testimonial One Area ----->
    <!--==================================================-->

    <!--==================================================-->
    <!----- Start Techno Call Do Action Area ----->
    <!--==================================================-->

    <div class="experience_area pt-85 pb-75">
        <div class="container">
            <div class="row d-flex align-items-center">
                <div class="col-lg-6">
                    <div class="experience_thumb">
                        <img src="{{ asset('assets/images/loop-mockup.webp') }}" alt="" />
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section_title text_left mb-5">
                        <div class="section_sub_title uppercase mb-3">
                            <h6>اپلیکیشن لوپ</h6>
                        </div>
                        <div class="section_main_title">
                            <h1>اپلیکیشن کاربران لوپ</h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                        <div class="section_content_text pt-4">
                            <p>سفارش تعمیرات کامپیوتر، هارد و سیستم رو در چند دقیقه ثبت کن. از ثبت تا تحویل، وضعیت کار رو
                                مرحله‌به‌مرحله توی لوپ پیگیری کن.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        .service_style_eight {
            min-height: -webkit-fill-available;
        }

        .btnapp {
            height: 50px;
            background: black;
            max-width: 160px;
            flex: 1;
            color: #fff !important;
            padding-inline: 15px;
            font-size: 13px;
            border-radius: 5px;
            line-height: 22px;
            display: flex;
            align-items: center;
            flex-direction: row-reverse;
            justify-content: space-between;
        }

        .circle-icon {
            background: #ffd700;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            width: 40px;
            border-radius: 100px;
        }

        .circle-icon i {
            color: #000;
        }

        .arrow-down {
            display: none;
        }

        @media (max-width:355px) {
            .arrow-left {
                display: none;
            }

            .arrow-down {
                display: block;
            }
        }

        .slider_button {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }

        @media (max-width:355px) {
            .slider_button {
                justify-content: center;
            }
        }
    </style>

    <div class="pt-35 pb-15">
        <div class="container">
            <div class="d-flex align-items-center" style="justify-content: space-evenly;flex-wrap: wrap;gap: 20px;">
                <a class="btnapp">
                    <i class="fa fa-android" style="color: #fff;font-size: 30px;"></i>
                    دریافت مستقیم نسخه اندروید
                </a>
                <a class="btnapp">
                    <img src="{{ asset('assets/images/icon.png') }}" style="height:30px; width: 30px;" />
                    <strong>مایکت</strong>دریافت از
                </a>
                <a class="btnapp">
                    <img src="{{ asset('assets/images/Apple-Logo.png') }}" style="height:30px; width: 30px;" />
                    <div style="text-align: center;">
                        <span style="font-size: 10px;">
                            Download on the
                        </span> <br>
                        <span>App Store</span>
                    </div>
                </a>
                <a href="https://user-panel.clpiran.com/" class="btnapp" style="background: #04257c">
                    نسخه وب اپلیکیشن لوپ
                </a>
                <a class="btnapp">
                    <img src="{{ asset('assets/images/bazar2.png') }}"
                        style="height:30px;width: 60px;object-fit: contain;" />
                    دریافت از
                </a>
            </div>
        </div>
    </div>


    <div class="experience_area pt-85 pb-75">
        <div class="container">
            <div class="row d-flex align-items-center">
                <div class="col-lg-6">
                    <div class="section_title text_left mb-5">
                        <div class="section_sub_title uppercase mb-3">
                            <h6>اپلیکیشن لوپ</h6>
                        </div>
                        <div class="section_main_title">
                            <h1>اپلیکیشن تکنسین‌های لوپ</h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                        <div class="section_content_text pt-4">
                            <p>سفارش تعمیرات سیستم و هارد خود را با چند کلیک ثبت کنید. با لوپ، درخواست شما مستقیماً توسط
                                متخصصان ما پیگیری و اجرا می‌شود.
                            </p>
                        </div>
                    </div>

                </div>
                <div class="col-lg-6">
                    <div class="experience_thumb">
                        <img src="{{ asset('assets/images/looptech.webp') }}" alt="" />
                    </div>
                </div>

            </div>
        </div>
    </div>


    <div class="call_do_action pt-85 pb-200 bg_color"
        style="background-image:url(assets/images/slider/slider13.jpg); background-position-y: center;">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title white text_center mb-60 mt-3">
                        <div class="section_sub_title uppercase mb-3">
                            <h6>شروع کار با لوپ.</h6>
                        </div>
                        <div class="section_main_title">
                            <h1>با ما تماس بگیرید و تعمیرات خود را آغاز کنید</h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                        <div class="section_content_text pt-4">
                            <p>
                                در هر زمان که نیاز به مشاوره یا خدمات تعمیر داشتید، تیم ما آماده است تا کمک کند. با استفاده
                                از روش‌های ارتباطی زیر، در کمترین زمان ممکن پاسخ خواهید گرفت.
                            </p>
                        </div>
                        <div class="border_bottom_lin"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--==================================================-->
    <!----- End Techno Call Do Action Area ----->
    <!--==================================================-->

    <!--==================================================-->
    <!----- Start Techno Contact Address Area ----->
    <!--==================================================-->
    @if (count($contacts) > 0)
        <div class="contact_address_area">
            <div class="container">
                <div class="row nagative_margin5">
                    @php
                        // گروه‌بندی کردن اطلاعات تماس بر اساس نوع
                        $contactsByType = $contacts->keyBy('type');

                        // اولویت نمایش انواع مختلف تماس
                        $displayOrder = ['office', 'email', 'phone'];

                        // آیکون‌های پیش‌فرض برای هر نوع
                        $defaultIcons = [
                            'office' => 'fa fa-map-marker',
                            'email' => 'fa fa-envelope',
                            'phone' => 'fa fa-phone',
                        ];
                    @endphp

                    @foreach ($displayOrder as $type)
                        @php
                            $contact = $contactsByType->get($type);
                        @endphp
                        @if ($contact)
                            <div class="col-lg-4 col-md-6">
                                <div class="single_contact_address_two">
                                    <div class="single_contact_address_two_icon">
                                        <div class="icon">
                                            <i class="{{ $defaultIcons[$type] ?? 'fa fa-info' }}"></i>
                                        </div>
                                    </div>
                                    <div class="single_contact_address_two_content">
                                        <h4>{{ $contact->name }}</h4>
                                        <span>{{ $contact->title }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- نمایش سایر انواع تماس که در لیست اولویت نیستند --}}
                    @foreach ($contacts as $contact)
                        @if (!in_array($contact->type, $displayOrder))
                            <div class="col-lg-4 col-md-6">
                                <div class="single_contact_address_two">
                                    <div class="single_contact_address_two_icon">
                                        <div class="icon">
                                            <i class="fa fa-info"></i>
                                        </div>
                                    </div>
                                    <div class="single_contact_address_two_content">
                                        <h4>{{ $contact->name }}</h4>
                                        <span>{{ $contact->title }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    <!--==================================================-->
    <!----- End Techno Contact Address Area ----->
    <!--==================================================-->



    <!--==================================================-->
    <!----- Start Techno Blog Area ----->
    <!--==================================================-->
    <div class="blog_area pt-85 pb-65">
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <div class="section_title text_left mb-60 mt-3">
                        <div class="section_sub_title uppercase mb-3">
                            <h6>آخرین مقالات</h6>
                        </div>
                        <div class="section_main_title">
                            <h1>مشاهده آخرین</h1>
                            <h1>پست های بلاگ</h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>

                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="section_button mt-50">
                        <div class="button two">
                            <a href="{{ route('web.blogs') }}">نمایش همه بلاگ ها</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                @forelse($recentBlogs as $blog)
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="single_blog mb-4">
                            <div class="single_blog_thumb pb-4">
                                <a href="{{ route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) }}">
                                    <img src="{{ $blog->image_path ? asset('storage/' . $blog->image_path) : 'assets/default.jpg' }}"
                                        alt="{{ $blog->title }}" />
                                </a>
                            </div>
                            <div class="single_blog_content pl-4 pr-4">
                                <div class="techno_blog_meta">
                                    <a href="#">{{ $blog->category->name ?? 'لوپ' }}</a>
                                    <span
                                        class="meta-date pr-3">{{ Morilog\Jalali\Jalalian::fromDateTime($blog->created_at)->format('Y/m/d') }}</span>
                                </div>
                                <div class="blog_page_title pb-1">
                                    <h3><a
                                            href="{{ route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) }}">{{ $blog->title }}</a>
                                    </h3>
                                </div>
                                <div class="blog_description">
                                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($blog->short_des ?? ($blog->des ?? 'خلاصه مقاله در دسترس نیست')), 100) }}
                                    </p>
                                </div>
                                <div class="blog_page_button pb-4">
                                    <a href="{{ route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) }}">ادامه
                                        مطلب <i class="fa fa-long-arrow-left"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p>در حال حاضر مقاله‌ای برای نمایش وجود ندارد.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    <!--==================================================-->
    <!----- End Techno Blog Area ----->
    <!--==================================================-->
@endsection
