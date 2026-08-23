@extends('layout.main.header')
@section('content')
    <main>

        <!-- =======================
            Main Banner START -->
        <section class="pt-0 position-relative overflow-hidden h-700px h-sm-600px h-lg-700px rounded-top-4 mx-2 mx-md-4"
            style="background-image:url(assets/images/bg/03.jpg); background-position: center; background-size: cover;">
            <div class="bg-overlay opacity-5"></div>
            <!-- SVG decoration for curve -->
            <figure class="position-absolute bottom-0 left-0 w-100 d-md-block mb-n3 z-index-9">
                <svg class="fill-body" width="100%" height="150" viewBox="0 0 500 150" preserveAspectRatio="none">
                    <path d="M0,150 L0,40 Q250,150 500,40 L580,150 Z"></path>
                </svg>
            </figure>
            <!-- SVG decoration -->
            <figure class="position-absolute top-0 start-50 translate-middle-x z-index-9 mt-5">
                <svg width="29px" height="29px">
                    <path class="fill-orange"
                        d="M29.004,14.502 C29.004,22.512 22.511,29.004 14.502,29.004 C6.492,29.004 -0.001,22.512 -0.001,14.502 C-0.001,6.492 6.492,-0.001 14.502,-0.001 C22.511,-0.001 29.004,6.492 29.004,14.502 Z">
                    </path>
                </svg>
            </figure>

            <div class="container z-index-9 position-relative">
                <!-- SVG decoration -->
                <figure class="position-absolute bottom-0 end-0 z-index-9 ms-5 mb-5">
                    <svg width="23px" height="23px">
                        <path class="fill-primary"
                            d="M23.003,11.501 C23.003,17.854 17.853,23.003 11.501,23.003 C5.149,23.003 -0.001,17.854 -0.001,11.501 C-0.001,5.149 5.149,-0.000 11.501,-0.000 C17.853,-0.000 23.003,5.149 23.003,11.501 Z">
                        </path>
                    </svg>
                </figure>

                <div class="row py-0 py-md-5 align-items-center text-center text-sm-start">
                    <div class="col-sm-10 col-lg-8 col-xl-6 all-text-white my-5 mt-md-0">
                        <div class="py-0 py-md-5 my-5">

                            <!-- Badge with content -->
                            <div class="d-inline-block bg-white px-3 py-2 rounded-pill mb-3">
                                <p class="mb-0 text-dark"><span
                                        class="badge text-bg-success rounded-pill me-1">حرفه‌ای</span>
                                    تعمیر موبایل و لپ‌تاپت رو به لوپ بسپار!</p>
                            </div>

                            <!-- Title -->
                            <h1 class="text-white display-6">یک کلیک تا<br><span class="text-warning">
                                    تعمیر تخصصی موبایل، لپ‌تاپ و تبلت شما
                                </span></h1>
                            <p class="text-white">درخواست تعمیر ثبت کنید، قیمت را مقایسه کنید و با خیال راحت انتخاب کنید.
                                <br>
                                با اپلیکیشن لوپ همه‌چیز سریع، شفاف و مطمئن انجام میشه.
                            </p>

                            <div class="d-sm-flex align-items-center mt-4">
                                <!-- Button -->
                                <a href="#" class="btn btn-primary me-2 mb-4 mb-sm-0">دانلود اپ</a>
                                <!-- Video button -->
                                {{-- <div class="d-flex align-items-center justify-content-center py-2 ms-0 ms-sm-4">
                                    <a data-glightbox data-gallery="office-tour"
                                        href="https://www.aparat.com/video/video/embed/videohash/31hor/vt/frame"
                                        class="btn btn-round btn-white-shadow text-danger me-7 mb-0 overflow-visible">
                                        <i class="fas fa-play"></i>
                                        <h6
                                            class="mb-0 ms-3 text-white fw-normal position-absolute start-100 top-50 translate-middle-y">
                                            مشاهده ویدیو</h6>
                                    </a>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- =======================
            Main Banner END -->

        <!-- =======================
            Client START -->
        <section class="pb-0 pb-md-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <!-- Slider START -->
                        <div class="tiny-slider">
                            <div class="tiny-slider-inner" data-arrow="false" data-dots="false" data-gutter="80"
                                data-items-xl="6" data-items-lg="5" data-items-md="4" data-items-sm="3" data-items-xs="2"
                                data-autoplay="2000">
                                <!-- Slide item START -->
                                <div class="item"> <img class="grayscale" src="assets/images/client/acer.png"
                                        alt="client-logo"> </div>
                                <div class="item"> <img class="grayscale" src="assets/images/client/samsung.png"
                                        alt="client-logo"> </div>
                                <div class="item"> <img class="grayscale" src="assets/images/client/gigabyte.png"
                                        alt="client-logo"> </div>
                                <div class="item"> <img class="grayscale" src="assets/images/client/lenovo.png"
                                        alt="client-logo"> </div>
                                <div class="item"> <img class="grayscale" src="assets/images/client/xiaomi.png"
                                        alt="client-logo"> </div>
                                <div class="item"> <img class="grayscale" src="assets/images/client/apple.png"
                                        alt="client-logo"> </div>
                                <div class="item"> <img class="grayscale" src="assets/images/client/dell.png"
                                        alt="client-logo"> </div>
                                <!-- Slide item END -->
                            </div>
                        </div>
                        <!-- Slider END -->
                    </div>
                </div>
            </div>
        </section>
        <!-- =======================
            Client END -->

        <!-- =======================
            About START -->
        <section>
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-5">
                        <!-- Title -->
                        <h2>مشاوره و خدمات تخصصی <span class="text-warning"><br>تعمیرات موبایل، </span> لپ‌تاپ و تبلت</h2>
                        <!-- Image -->
                        <img src="assets/images/about/03.jpg" class="rounded-2" alt="">
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-4">
                            <!-- Item -->
                            <div class="col-sm-6">
                                <div class="icon-lg bg-orange bg-opacity-10 text-orange rounded-2"><i
                                        class="fas fa-user-tie fs-5"></i></div>
                                <h5 class="mt-2">تعمیرکاران تکنسین</h5>
                                <p class="mb-0">تعمیرکاران لوپ با سابقه‌ی حرفه‌ای و گواهی معتبر، آماده‌ی رفع هرگونه ایراد
                                    سخت‌افزاری و نرم‌افزاری هستند.</p>
                            </div>
                            <!-- Item -->
                            <div class="col-sm-6">
                                <div class="icon-lg bg-info bg-opacity-10 text-info rounded-2"><i
                                        class="fas fa-book fs-5"></i></div>
                                <h5 class="mt-2">درخواست آسان و سریع</h5>
                                <p class="mb-0">در چند ثانیه درخواست تعمیر ثبت کنید و نزدیک‌ترین تعمیرکار معتبر را انتخاب
                                    کنید. بدون تماس تلفنی و بدون اتلاف وقت.</p>
                            </div>
                            <!-- Item -->
                            <div class="col-sm-6">
                                <div class="icon-lg bg-success bg-opacity-10 text-success rounded-2"><i
                                        class="fas fa-dollar-sign fs-5"></i></div>
                                <h5 class="mt-2"> قیمت‌گذاری شفاف و رقابتی</h5>
                                <p class="mb-0">هزینه‌ها قبل از شروع کار به شما نمایش داده می‌شوند تا بهترین گزینه را
                                    براساس بودجه و امتیاز تعمیرکار انتخاب کنید.</p>
                            </div>
                            <!-- Item -->
                            <div class="col-sm-6">
                                <div class="icon-lg bg-purple bg-opacity-10 text-purple rounded-2"><i
                                        class="fas fa-headset fs-5"></i></div>
                                <h5 class="mt-2">پشتیبانی تا پایان تعمیر</h5>
                                <p class="mb-0">از لحظه‌ی ثبت درخواست تا تحویل نهایی دستگاه، تیم لوپ همراه شماست تا
                                    خیالتان از کیفیت و زمان تعمیر راحت باشد.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- =======================
            About END -->

        <!-- =======================
            Trending courses START -->
        <section class="pt-0 pt-md-5">
            <div class="container">
                <!-- Title -->
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <h2 class="mb-0">خدمات لوپ</h2>
                        <p class="mb-0">خدمات تعمیر و نگهداری موبایل، لپ‌تاپ و تبلت</p>
                    </div>
                </div>

                <div class="row g-4">
                    @if (isset($leafCategories) && count($leafCategories) > 0)
                        @foreach ($leafCategories->take(6) as $category)
                            <!-- Card Item START -->
                            <div class="col-md-6 col-xl-4">
                                <div class="card p-2 shadow h-100">
                                    <div class="rounded-top overflow-hidden">
                                        <div class="card-overlay-hover">
                                            <!-- Image -->
                                            @if ($category['image_path'])
                                                <img src="{{ asset('storage/'.$category['image_path']) }}" class="card-img-top"
                                                    alt="{{ $category['title'] }}">
                                            @else
                                                <img src="assets/images/courses/4by3/17.jpg" class="card-img-top"
                                                    alt="{{ $category['title'] }}">
                                            @endif
                                        </div>
                                        <!-- Hover element -->
                                        <div class="card-img-overlay">
                                            <div class="card-element-hover d-flex justify-content-end">
                                                {{-- <a href="#" class="icon-md bg-white rounded-circle text-center">
                                                    <i class="fas fa-tools text-danger"></i>
                                                </a> --}}
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Card body -->
                                    <div class="card-body">
                                        <!-- Title -->
                                        <h5 class="card-title fw-normal">
                                            <a href="#">{{ $category['title'] }}</a>
                                        </h5>

                                        <!-- Info -->
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="icon-md bg-primary bg-opacity-10 text-primary rounded-circle me-2">
                                                    <i class="fas fa-wrench"></i>
                                                </div>
                                                <span class="text-muted small">خدمات تخصصی</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="icon-md bg-success bg-opacity-10 text-success rounded-circle me-2">
                                                    <i class="fas fa-star"></i>
                                                </div>
                                                <span class="text-muted small">برتر</span>
                                            </div>
                                        </div>

                                        <!-- Action Button -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="#" class="badge bg-primary bg-opacity-10 text-primary">
                                                <i class="fas fa-circle small fw-bold"></i> خدمات لوپ
                                            </a>
                                            <a href="#" class="btn btn-sm btn-primary-soft">درخواست خدمت</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Card Item END -->
                        @endforeach
                    @else
                        <!-- Default content when no categories -->
                        <div class="col-12 text-center">
                            <p class="text-muted">در حال حاضر خدماتی موجود نیست</p>
                        </div>
                    @endif
                </div>

                <!-- Button -->
                <div class="text-center mt-5">
                    <a href="#" class="btn btn-primary-soft mb-0">مشاهده همه خدمات<i
                            class="fas fa-arrow-left ms-2"></i></a>
                </div>
            </div>
        </section>
        <!-- =======================
            Trending courses END -->

        <!-- =======================
            Video divider START -->
        <section class="bg-light position-relative">
            <!-- SVG decoration -->
            <figure class="position-absolute bottom-0 start-0 d-none d-lg-block">
                <svg width="822.2px" height="301.9px" viewBox="0 0 822.2 301.9">
                    <path class="fill-warning"
                        d="M752.5,51.9c-4.5,3.9-8.9,7.8-13.4,11.8c-51.5,45.3-104.8,92.2-171.7,101.4c-39.9,5.5-80.2-3.4-119.2-12.1 c-32.3-7.2-65.6-14.6-98.9-13.9c-66.5,1.3-128.9,35.2-175.7,64.6c-11.9,7.5-23.9,15.3-35.5,22.8c-40.5,26.4-82.5,53.8-128.4,70.7 c-2.1,0.8-4.2,1.5-6.2,2.2L0,301.9c3.3-1.1,6.7-2.3,10.2-3.5c46.1-17,88.1-44.4,128.7-70.9c11.6-7.6,23.6-15.4,35.4-22.8 c46.7-29.3,108.9-63.1,175.1-64.4c33.1-0.6,66.4,6.8,98.6,13.9c39.1,8.7,79.6,17.7,119.7,12.1C634.8,157,688.3,110,740,64.6 c4.5-3.9,9-7.9,13.4-11.8C773.8,35,797,16.4,822.2,1l-0.7-1C796.2,15.4,773,34,752.5,51.9z" />
                </svg>
            </figure>

            <!-- SVG decoration -->
            <figure class="position-absolute top-0 end-0">
                <svg width="822.2px" height="301.9px" viewBox="0 0 822.2 301.9">
                    <path class="fill-primary"
                        d="M752.5,51.9c-4.5,3.9-8.9,7.8-13.4,11.8c-51.5,45.3-104.8,92.2-171.7,101.4c-39.9,5.5-80.2-3.4-119.2-12.1 c-32.3-7.2-65.6-14.6-98.9-13.9c-66.5,1.3-128.9,35.2-175.7,64.6c-11.9,7.5-23.9,15.3-35.5,22.8c-40.5,26.4-82.5,53.8-128.4,70.7 c-2.1,0.8-4.2,1.5-6.2,2.2L0,301.9c3.3-1.1,6.7-2.3,10.2-3.5c46.1-17,88.1-44.4,128.7-70.9c11.6-7.6,23.6-15.4,35.4-22.8 c46.7-29.3,108.9-63.1,175.1-64.4c33.1-0.6,66.4,6.8,98.6,13.9c39.1,8.7,79.6,17.7,119.7,12.1C634.8,157,688.3,110,740,64.6 c4.5-3.9,9-7.9,13.4-11.8C773.8,35,797,16.4,822.2,1l-0.7-1C796.2,15.4,773,34,752.5,51.9z" />
                </svg>
            </figure>

            <!-- SVG decoration -->
            <figure class="position-absolute bottom-0 start-50 translate-middle-x ms-n9 mb-5">
                <svg width="23px" height="23px">
                    <path class="fill-primary"
                        d="M23.003,11.501 C23.003,17.854 17.853,23.003 11.501,23.003 C5.149,23.003 -0.001,17.854 -0.001,11.501 C-0.001,5.149 5.149,-0.000 11.501,-0.000 C17.853,-0.000 23.003,5.149 23.003,11.501 Z">
                    </path>
                </svg>
            </figure>

            <!-- SVG decoration -->
            <figure class="position-absolute bottom-0 end-0 me-5 mb-5">
                <svg width="22px" height="22px">
                    <path class="fill-warning"
                        d="M22.003,11.001 C22.003,17.078 17.077,22.003 11.001,22.003 C4.925,22.003 -0.001,17.078 -0.001,11.001 C-0.001,4.925 4.925,-0.000 11.001,-0.000 C17.077,-0.000 22.003,4.925 22.003,11.001 Z">
                    </path>
                </svg>
            </figure>

            <div class="container position-relative">
                <div class="row justify-content-between align-items-center my-5">

                    <div class="col-lg-5 position-relative">
                        <!-- SVG decoration -->
                        <figure class="position-absolute top-0 start-0 translate-middle mt-n5">
                            <svg width="29px" height="29px">
                                <path class="fill-orange"
                                    d="M29.004,14.502 C29.004,22.512 22.511,29.004 14.502,29.004 C6.492,29.004 -0.001,22.512 -0.001,14.502 C-0.001,6.492 6.492,-0.001 14.502,-0.001 C22.511,-0.001 29.004,6.492 29.004,14.502 Z">
                                </path>
                            </svg>
                        </figure>

                        <!-- Title -->
                        <h3 class="h3">خراب شد؟ نگران نباش، لوپ همیشه آماده‌ست!</h3>
                        <p>
                            فقط چند کلیک تا تعمیر سریع و مطمئن فاصله داری.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <!-- =======================
            Video divider END -->

        <!-- =======================
            Event START -->
        <section class="pb-0 pb-md-5">
            <div class="container">
                <!-- Title -->
                <div class="row mb-4">
                    <h2 class="mb-0">جدید‌ترین <span class="text-warning">اخبار</span> و مقالات</h2>
                </div>
                <div class="row">
                    <!-- Slider START -->
                    <div class="tiny-slider arrow-round arrow-creative arrow-blur arrow-hover">
                        <div class="tiny-slider-inner" data-autoplay="false" data-arrow="true" data-dots="false"
                            data-items-xl="3" data-items-md="2" data-items-xs="1">

                            @if(isset($recentBlogs) && $recentBlogs->count() > 0)
                                @foreach($recentBlogs as $blog)
                                    <!-- Card item START -->
                                    <div class="card bg-transparent">
                                        <div class="position-relative">
                                            <!-- Image -->
                                            @if($blog->image_path)
                                                <img src="{{ asset($blog->image_path) }}" class="card-img" alt="{{ $blog->title }}">
                                            @else
                                                <img src="{{ asset('assets/default.jpg') }}" class="card-img" alt="{{ $blog->title }}">
                                            @endif
                                            <!-- Overlay -->
                                            <div class="card-img-overlay d-flex align-items-start flex-column p-3">
                                                <div class="w-100 mt-auto">
                                                    <!-- Category -->
                                                    <a href="#" class="badge text-bg-white fs-6 rounded-1">
                                                        <i class="fas fa-calendar-alt text-orange me-2"></i>
                                                        {{ \Morilog\Jalali\Jalalian::fromCarbon($blog->created_at)->format('j F Y') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card body -->
                                        <div class="card-body px-2">
                                            <!-- Title -->
                                            <h5 class="card-title fw-normal">
                                                <a href="{{ route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) }}">{{ $blog->title }}</a>
                                            </h5>
                                            <!-- Category and button -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="mb-0 text-muted small">
                                                    <i class="fas fa-tag me-2"></i>
                                                    {{ $blog->category ? $blog->category->title : 'عمومی' }}
                                                </span>
                                                <a href="{{ route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) }}" class="btn btn-sm btn-primary-soft mb-0">مطالعه</a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Card item END -->
                                @endforeach
                            @else
                                <!-- Default content when no blogs -->
                                <div class="col-12 text-center">
                                    <p class="text-muted">در حال حاضر مقاله‌ای موجود نیست</p>
                                </div>
                            @endif

                        </div>
                    </div>
                    <!-- Slider END -->
                </div>
                
                <!-- Button -->
                <div class="text-center mt-5">
                    <a href="{{ route('web.blogs') }}" class="btn btn-primary-soft mb-0">مشاهده همه مقالات<i class="fas fa-arrow-left ms-2"></i></a>
                </div>
            </div>
        </section>
        <!-- =======================
            Event END -->

        <!-- =======================
            Newsletter START -->
        <section class="mb-n9 position-relative z-index-9">
            <div class="container">
                <div class="row">
                    <div class="col-11 col-md-10 mx-auto">
                        <div class="bg-warning rounded-3 shadow p-3 p-sm-4 position-relative overflow-hidden">
                            <!-- SVG decoration -->
                            <figure class="position-absolute top-100 start-100 translate-middle mt-n6 ms-n5">
                                <svg width="211px" height="211px">
                                    <path class="fill-white opacity-4"
                                        d="M210.030,105.011 C210.030,163.014 163.010,210.029 105.012,210.029 C47.013,210.029 -0.005,163.014 -0.005,105.011 C-0.005,47.015 47.013,-0.004 105.012,-0.004 C163.010,-0.004 210.030,47.015 210.030,105.011 Z">
                                    </path>
                                </svg>
                            </figure>
                            <!-- SVG decoration -->
                            <figure class="position-absolute top-100 start-0 translate-middle mt-n6 ms-5">
                                <svg width="141px" height="141px">
                                    <path class="fill-white opacity-4"
                                        d="M140.520,70.258 C140.520,109.064 109.062,140.519 70.258,140.519 C31.454,140.519 -0.004,109.064 -0.004,70.258 C-0.004,31.455 31.454,-0.003 70.258,-0.003 C109.062,-0.003 140.520,31.455 140.520,70.258 Z">
                                    </path>
                                </svg>
                            </figure>
                            <!-- SVG decoration -->
                            <figure class="position-absolute top-0 start-50 mt-4 ms-n9">
                                <svg width="41px" height="41px">
                                    <path class="fill-white opacity-4"
                                        d="M40.531,20.265 C40.531,31.458 31.457,40.531 20.265,40.531 C9.072,40.531 -0.001,31.458 -0.001,20.265 C-0.001,9.073 9.072,-0.001 20.265,-0.001 C31.457,-0.001 40.531,9.073 40.531,20.265 Z">
                                    </path>
                                </svg>
                            </figure>

                            <div class="row">
                                <div class="col-md-8 mx-auto text-center py-5 position-relative">
                                    <!-- Title -->
                                    <h2>برای دریافت جدیدترین پیشنهادها و نکات تعمیر موبایل و لپ‌تاپ،در خبرنامه لوپ عضو شوید.</h2>
                                    <!-- Form -->
                                    <form class="row align-items-center justify-content-center mt-3">
                                        <div class="col-lg-8">
                                            <div class="bg-body shadow rounded-pill p-2">
                                                <div class="input-group">
                                                    <input class="form-control border-0 me-1" type="email"
                                                        placeholder="ایمیل">
                                                    <button type="button"
                                                        class="btn btn-blue mb-0 rounded-pill">عضویت</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div> <!-- Row END -->
                        </div>
                    </div>
                </div> <!-- Row END -->
            </div>
        </section>
        <!-- =======================
            Newsletter END -->

    </main>
@endsection
