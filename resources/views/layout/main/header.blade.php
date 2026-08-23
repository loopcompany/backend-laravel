<!DOCTYPE html>
<html lang="fa-IR" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>@yield('meta_title', 'لوپ | پلتفرم خدمات تعمیرات لپ تاپ و کامپیوتر')</title>
    <meta name="description" content="@yield('meta_description', 'لوپ ارائه‌دهنده خدمات تخصصی تعمیرات لپ‌تاپ، کامپیوتر با تیمی مجرب و قیمت‌های مناسب')">
    @yield('meta')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="56x56" href="{{ asset('assets/images/fav-icon/icon.png') }}">
    <!-- bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" type="text/css" media="all">
    <!-- carousel CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}" type="text/css" media="all">
    <!-- responsive CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}" type="text/css" media="all">
    <!-- nivo-slider CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/nivo-slider.css') }}" type="text/css" media="all">
    <!-- animate CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/animate.css') }}" type="text/css" media="all">
    <!-- animated-text CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/animated-text.css') }}" type="text/css" media="all">
    <!-- font-awesome CSS -->
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/fonts/font-awesome/css/font-awesome.min.css') }}">
    <!-- font-flaticon CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/flaticon.css') }}" type="text/css" media="all">
    <!-- theme-default CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/theme-default.css') }}" type="text/css" media="all">
    <!-- meanmenu CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/meanmenu.min.css') }}" type="text/css" media="all">
    <!-- Main Style CSS -->
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}" type="text/css" media="all">
    <!-- transitions CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/owl.transitions.css') }}" type="text/css" media="all">
    <!-- venobox CSS -->
    <link rel="stylesheet" href="{{ asset('venobox/venobox.css') }}" type="text/css" media="all">
    <!-- widget CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/widget.css') }}" type="text/css" media="all">
    <!-- RTL FONT Vazir matn with nine weights -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/vazir/Farsi-Digits/Vazirmatn-FD-font-face.css') }}">
    <!-- Custom RTL Code -->
    <link rel="stylesheet" href="{{ asset('assets/css/custom_rtl.css') }}">
    <!-- modernizr js -->
    <script type="text/javascript" src="{{ asset('assets/js/vendor/modernizr-3.5.0.min.js') }}"></script>
    {{-- <script src="{{ asset('assets/js/jquery-3.4.1.slim.min.js') }}"></script>
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script> --}}
    <link rel="stylesheet" href="{{ asset('assets/jalali/jalalidatepicker.min.css') }}">

</head>

<body dir="rtl">


    <!--==================================================-->
    <!----- Start	Techno Header Top Menu Area Css ----->
    <!--==================================================-->
    
    <!--==================================================-->
    <!----- End	Techno Header Top Menu Area Css ----->
    <!--===================================================-->

    <!--==================================================-->
    <!----- Start Techno Main Menu Area ----->
    <!--==================================================-->


    @include('layout.main.nav')



    @yield('content')


    @include('layout.main.footer')
    {{--
    <div class="footer-middle pt-95" style="background-image:url(assets/images/call-bg.png)">
        <div class="container">
            <div class="row">


                <div class="col-lg-4 col-md-4 col-sm-12">
                    <div class="widget widgets-company-info">

                        <div class="follow-company-info pt-3" style="flex-direction: column;display: flex;">
                            <div class="follow-company-text ml-3">
                                <a href="#">
                                    <p>راه‌های ارتباطی</p>
                                </a>
                            </div>
                            <div class="follow-company-icon" style="display: flex;flex-direction: column;">
                                @if ($globalContacts && $globalContacts->count() > 0)
                                    @foreach ($globalContacts as $contact)
                                        <a href="{{ $contact->link }}" title="{{ $contact->title }}">
                                            @if ($contact->type == 'phone')
                                                <i class="fa fa-phone"></i>
                                            @elseif($contact->type == 'email')
                                                <i class="fa fa-envelope"></i>
                                            @elseif($contact->type == 'office')
                                                <i class="fa fa-location-arrow"></i>
                                            @endif
                                            {{ $contact->name }}
                                        </a>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <div class="widget widget-nav-menu">
                        <h4 class="widget-title pb-4">لوپ</h4>
                        <div class="menu-quick-link-container mr-4">
                            <ul id="menu-quick-link" class="menu">
                                <li><a href="{{ route('web.about') }}">درباره ما</a></li>
                                <li><a href="{{ route('web.contact') }}">تماس با ما</a></li>
                                <li><a href="{{ route('web.blogs') }}">مقالات و آموزش</a></li>


                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <div class="widget widget-nav-menu">
                        <h4 class="widget-title pb-4">خدمات مشتریان</h4>
                        <div class="menu-quick-link-container mr-4">
                            <ul id="menu-quick-link" class="menu">
                                <li><a href="{{ route('web.privacy') }}">حریم خصوصی</a></li>
                                <li><a href="{{ route('web.terms') }}">قوانین ومقررات</a></li>
                                <li><a href="{{ route('web.faqs') }}">سوالات متداول</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <div class="widget widget-nav-menu">
                        <h4 class="widget-title pb-4">فرصت‌های شغلی</h4>
                        <div class="menu-quick-link-container mr-4">
                            <ul id="menu-quick-link" class="menu">
                                <li><a href="https://tech-panel.clpiran.com/">ثبت نام تکنسین‌لوپ</a></li>
                                <li><a href="{{ route('loop.learn') }}">ثبت نام در کلاس های آموزش رایگان رایانه</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <div class="widget widget-nav-menu">
                        <h4 class="widget-title pb-4">راهنمای لوپ</h4>
                        <div class="menu-quick-link-container mr-4">
                            <ul id="menu-quick-link" class="menu">
                                <li><a href="{{ asset('assets/guid/tech.pdf') }}">راهنمای اپلیکیشن تکنسین</a></li>
                                <li><a href="{{ asset('assets/guid/user.pdf') }}">راهنمای اپلیکیشن کاربر</a></li>
                                <li><a href="{{ asset('assets/guid/organ.pdf') }}">راهنمای اپلیکیشن کاربری سازمانی
                                        شرکتی</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>


            </div>
            <style>
                .footer_middle_social_icon a {
                    height: 45px;
                    width: 45px;
                    display: inline-block;
                    background: #0c5adb;
                    border-radius: 50%;
                    font-size: 20px;
                    color: #fff;
                    text-align: center;
                    margin: 0 5px;
                    transition: .5s;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .footer_middle_social_icon a.color1 {
                    background: #1d2839;
                    color: #fff
                }

                .card {
                    background-color: #1d283a;
                }
            </style>
            <div class="d-flex align-items-center justify-content-center">
                <a referrerpolicy='origin' target='_blank'
                    href='https://trustseal.enamad.ir/?id=6860144&Code=JqYnk84jEHOMnmsQ6dG36pA2uEMBmdYa'><img
                        referrerpolicy='origin'
                        src='https://trustseal.enamad.ir/logo.aspx?id=6860144&Code=JqYnk84jEHOMnmsQ6dG36pA2uEMBmdYa'
                        alt='' style='cursor:pointer' code='JqYnk84jEHOMnmsQ6dG36pA2uEMBmdYa'></a>

                <img src="{{ asset('assets/images/samandehi.png') }}"
                    style="height: 70px; width: 70; object-fit: contain;" />
                <img src="{{ asset('assets/images/logonama.png') }}"
                    style="height: 70px; width: 70; object-fit: contain;" />
            </div>
            <div class="row footer-bottom mt-70 pt-3 pb-3">
                <div class="col-lg-6 col-md-6">

                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="footer_middle_social">
                        <div class="footer_middle_social_icon d-flex" style="justify-content: flex-end;">
                            @foreach ($globalSocials as $item)
                                <a class="color1" href="{{ $item->link }}"><img
                                        src="{{ asset('storage/' . $item->icon) }}"
                                        style="height: 40px; width: 40px;" /></a>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    --}}
    <!--==================================================-->
    <!----- End Techno Footer Middle Area ----->
    <!--==================================================-->

    <!-- jquery js -->
    <script type="text/javascript" src="{{ asset('assets/js/vendor/jquery-3.2.1.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/jquery.nav.js') }}"></script>
    <!-- bootstrap js -->
    <script type="text/javascript" src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <!-- carousel js -->
    <script type="text/javascript" src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
    <!-- counterup js -->
    <script type="text/javascript" src="{{ asset('assets/js/jquery.counterup.min.js') }}"></script>
    <!-- waypoints js -->
    <script type="text/javascript" src="{{ asset('assets/js/waypoints.min.js') }}"></script>
    <!-- wow js -->
    <script type="text/javascript" src="{{ asset('assets/js/wow.js') }}"></script>
    <!-- imagesloaded js -->
    <script type="text/javascript" src="{{ asset('assets/js/imagesloaded.pkgd.min.js') }}"></script>
    <!-- venobox js -->
    <script type="text/javascript" src="{{ asset('venobox/venobox.js') }}"></script>
    <!-- ajax mail js -->
    <script type="text/javascript" src="{{ asset('assets/js/ajax-mail.js') }}"></script>
    <!--  testimonial js -->
    <!--<script type="text/javascript" src="{{ asset('assets/js/testimonial.js') }}"></script>-->
    <!--  animated-text js -->
    <script type="text/javascript" src="{{ asset('assets/js/animated-text.js') }}"></script>
    <!-- venobox min js -->
    <script type="text/javascript" src="{{ asset('venobox/venobox.min.js') }}"></script>
    <!-- isotope js -->
    <script type="text/javascript" src="{{ asset('assets/js/isotope.pkgd.min.js') }}"></script>
    <!-- jquery nivo slider pack js -->
    <script type="text/javascript" src="{{ asset('assets/js/jquery.nivo.slider.pack.js') }}"></script>
    <!-- jquery meanmenu js -->
    <script type="text/javascript" src="{{ asset('assets/js/jquery.meanmenu.js') }}"></script>
    <!-- jquery scrollup js -->
    <script type="text/javascript" src="{{ asset('assets/js/jquery.scrollUp.js') }}"></script>
    <!-- theme js -->
    <script type="text/javascript" src="{{ asset('assets/js/theme.js') }}"></script>
    <!-- jquery js -->
    <script src="{{ asset('assets/jalali/jalalidatepicker.min.js') }}"></script>

    @yield('script')
</body>

</html>
