<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <title>لوپ | پلتفرم خدمات تعمیرات موبایل، لپ‌تاپ و تبلت</title>

    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="">
    <meta name="description" content="">

    <!-- Dark mode -->
    <script>
        const storedTheme = localStorage.getItem('theme')

        const getPreferredTheme = () => {
            if (storedTheme) {
                return storedTheme
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        }

        const setTheme = function(theme) {
            if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-bs-theme', 'dark')
            } else {
                document.documentElement.setAttribute('data-bs-theme', theme)
            }
        }

        setTheme(getPreferredTheme())

        window.addEventListener('DOMContentLoaded', () => {
            var el = document.querySelector('.theme-icon-active');
            if (el != 'undefined' && el != null) {
                const showActiveTheme = theme => {
                    const activeThemeIcon = document.querySelector('.theme-icon-active use')
                    const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
                    const svgOfActiveBtn = btnToActive.querySelector('.mode-switch use').getAttribute('href')

                    document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
                        element.classList.remove('active')
                    })

                    btnToActive.classList.add('active')
                    activeThemeIcon.setAttribute('href', svgOfActiveBtn)
                }

                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    if (storedTheme !== 'light' || storedTheme !== 'dark') {
                        setTheme(getPreferredTheme())
                    }
                })

                showActiveTheme(getPreferredTheme())

                document.querySelectorAll('[data-bs-theme-value]')
                    .forEach(toggle => {
                        toggle.addEventListener('click', () => {
                            const theme = toggle.getAttribute('data-bs-theme-value')
                            localStorage.setItem('theme', theme)
                            setTheme(theme)
                            showActiveTheme(theme)
                        })
                    })

            }
        })
    </script>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Plugins CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/font-awesome/css/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/tiny-slider/tiny-slider.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/glightbox/css/glightbox.css') }}">

    <!-- Theme CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style-rtl.css') }}">

</head>

<body>

    <!-- Top header START -->
    <div class="navbar-top navbar-dark bg-grad-blue d-none d-xl-block py-2 mx-2 mx-md-4 rounded-bottom-4">
        <div class="container">
            <div class="d-lg-flex justify-content-lg-between align-items-center">
                <!-- Navbar top Left-->
                <!-- Top info -->
                <ul class="nav align-items-center justify-content-center">
                    <li class="nav-item me-3" data-bs-toggle="tooltip" data-bs-animation="false"
                        data-bs-placement="bottom" data-bs-original-title="Sunday CLOSED">
                        <span class="text-white"><i class="far fa-clock me-2"></i>زمان بازدید: دوشنبه تا شنبه دقیقه 9:00
                            الی 19:00</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-headset me-2"></i>با ما تماس بگیرید:
                            093200000000</a>
                    </li>
                </ul>

                <!-- Navbar top Right-->
                <div class="nav d-flex align-items-center justify-content-center">
                    <!-- Top social -->
                    <ul class="list-unstyled d-flex mb-0">
                        <li> <a class="px-2 nav-link" href="#"><i class="fab fa-facebook"></i></a> </li>
                        <li> <a class="px-2 nav-link" href="#"><i class="fab fa-instagram"></i></a> </li>
                        <li> <a class="px-2 nav-link" href="#"><i class="fab fa-twitter"></i></a> </li>
                        <li> <a class="ps-2 nav-link" href="#"><i class="fab fa-linkedin-in"></i></a> </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- Top header END -->

    <!-- Header START -->
    <header class="navbar-light header-static navbar-sticky">
        <!-- Logo Nav START -->
        <nav class="navbar navbar-expand-xl">
            <div class="container">
                <!-- Logo START -->
                <a class="navbar-brand me-0" href="index.html">
                    <img class="light-mode-item navbar-brand-item" src="{{ asset('assets/images/logo.png') }}"
                        alt="logo">
                    <img class="dark-mode-item navbar-brand-item" src="{{ asset('assets/images/logo-light.png') }}"
                        alt="logo">
                </a>
                <!-- Logo END -->

                <!-- Responsive navbar toggler -->
                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-animation">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <!-- Main navbar START -->
                <div class="navbar-collapse collapse" id="navbarCollapse">

                    <!-- Nav Search END -->
                    <ul class="navbar-nav navbar-nav-scroll mx-auto">
                        <!-- Nav item 1 Demos -->
                         <li class="nav-item">
                            <a class="nav-link active" href="{{ route('web.home') }}" id="demoMenu">صفحه اصلی</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="demoMenu">خدمات لوپ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('web.faqs') }}" id="demoMenu">سوالات متداول</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('web.about') }}" id="demoMenu">درباره ما</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('web.contact') }}" id="demoMenu">تماس با ما</a>
                        </li>


                    </ul>
                </div>
                <!-- Main navbar END -->

                <!-- Nav Search START -->
                <div class="nav nav-item dropdown nav-search px-1 px-lg-3">
                    <a class="nav-link text-black-50" role="button" href="#" id="navSearch"
                        data-bs-toggle="dropdown" aria-expanded="true" data-bs-auto-close="outside"
                        data-bs-display="static">
                        <i class="bi bi-search fs-4"> </i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow rounded p-2" aria-labelledby="navSearch"
                        data-bs-popper="none">
                        <form class="input-group">
                            <input class="form-control border-primary" type="search" placeholder="جستجو..."
                                aria-label="Search">
                            <button class="btn btn-primary m-0" type="submit">جستجو</button>
                        </form>

                        <!-- Recent search -->
                        <ul class="list-group list-group-borderless p-2 small">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-bold">جستجوهای اخیر</span>
                                <button class="btn btn-sm btn-link mb-0 px-0">حذف همه</button>
                            </li>
                            <li class="list-group-item text-primary-hover text-truncate">
                                <a href="#" class="text-body"> <i class="far fa-clock me-1"></i>تعمیر
                                    صفحه‌نمایش آیفون</a>
                            </li>
                            <li class="list-group-item text-primary-hover text-truncate">
                                <a href="#" class="text-body"> <i class="far fa-clock me-1"></i>تعویض باتری
                                    سامسونگ</a>
                            </li>
                            <li class="list-group-item text-primary-hover text-truncate">
                                <a href="#" class="text-body"> <i class="far fa-clock me-1"></i>نصب ویندوز و
                                    درایورها لپ‌تاپ</a>
                            </li>
                            <li class="list-group-item text-primary-hover text-truncate">
                                <a href="#" class="text-body"> <i class="far fa-clock me-1"></i>تعمیر پورت شارژ
                                    تبلت</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- Nav Search END -->

                <!-- Profile START -->
                <div class="dropdown ms-1 ms-lg-0">
                    <a class="avatar avatar-sm p-0" href="#" id="profileDropdown" role="button"
                        data-bs-auto-close="outside" data-bs-display="static" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <img class="avatar-img rounded-circle" src="{{ asset('assets/images/avatar/user.png') }}"
                            alt="avatar">
                    </a>
                    <ul class="dropdown-menu dropdown-animation dropdown-menu-end shadow pt-3"
                        aria-labelledby="profileDropdown">
                        <!-- Profile info -->
                        <li class="px-3 mb-3">
                            <div class="d-flex align-items-center">
                                <!-- Avatar -->
                                <div class="avatar me-3">
                                    <img class="avatar-img rounded-circle shadow"
                                        src="{{ asset('assets/images/avatar/user.png') }}" alt="avatar">
                                </div>
                                <div>
                                    <a class="h6" href="#">الهام حسینی</a>
                                    <p class="small m-0">example@gmail.com</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <!-- Links -->
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person fa-fw me-2"></i>ویرایش</a>
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear fa-fw me-2"></i>تنظیمات</a>
                        </li>
                        <li><a class="dropdown-item" href="#"><i
                                    class="bi bi-info-circle fa-fw me-2"></i>پشتیبانی</a></li>
                        <li><a class="dropdown-item bg-danger-soft-hover" href="#"><i
                                    class="bi bi-power fa-fw me-2"></i>خروج</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <!-- Dark mode options START -->
                        <li>
                            <div
                                class="bg-light dark-mode-switch theme-icon-active d-flex align-items-center p-1 rounded mt-2">
                                <button type="button" class="btn btn-sm mb-0" data-bs-theme-value="light">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-sun fa-fw mode-switch" viewBox="0 0 16 16">
                                        <path
                                            d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z">
                                        </path>
                                        <use href="#"></use>
                                    </svg> روشن
                                </button>
                                <button type="button" class="btn btn-sm mb-0" data-bs-theme-value="dark">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-moon-stars fa-fw mode-switch"
                                        viewBox="0 0 16 16">
                                        <path
                                            d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278zM4.858 1.311A7.269 7.269 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.316 7.316 0 0 0 5.205-2.162c-.337.042-.68.063-1.029.063-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286z">
                                        </path>
                                        <path
                                            d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.734 1.734 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.734 1.734 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.734 1.734 0 0 0 1.097-1.097l.387-1.162zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L13.863.1z">
                                        </path>
                                        <use href="#"></use>
                                    </svg> تیره
                                </button>
                                <button type="button" class="btn btn-sm mb-0 active" data-bs-theme-value="auto">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-circle-half fa-fw mode-switch"
                                        viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"></path>
                                        <use href="#"></use>
                                    </svg> خودکار
                                </button>
                            </div>
                        </li>
                        <!-- Dark mode options END-->
                    </ul>
                </div>
                <!-- Profile START -->
            </div>
        </nav>
        <!-- Logo Nav END -->
    </header>
    <!-- Header END -->

    <!-- **************** MAIN CONTENT START **************** -->



    @yield('content')




    <!-- **************** MAIN CONTENT END **************** -->

    <!-- =======================
Footer START -->
    <footer class="pt-0 bg-blue rounded-4 position-relative mx-2 mx-md-4 mb-3">
        <!-- SVG decoration for curve -->
        <figure class="mb-0">
            <svg class="fill-body rotate-180" width="100%" height="150" viewBox="0 0 500 150"
                preserveAspectRatio="none">
                <path d="M0,150 L0,40 Q250,150 500,40 L580,150 Z"></path>
            </svg>
        </figure>

        <div class="container">
            <div class="row mx-auto">
                <div class="col-lg-6 mx-auto text-center my-5">
                    <!-- Logo -->
                    <img class="mx-auto h-40px" src="{{ asset('assets/images/logo-light.png') }}" alt="logo">
                    <p class="mt-3 text-white">
                        لوپ، پلتفرمی هوشمند برای اتصال کاربران به تعمیرکاران حرفه‌ای در حوزه‌ی موبایل، لپ‌تاپ و تبلت
                        است.
                        ما با هدف ایجاد شفافیت، اعتماد و سرعت در خدمات تعمیرات، بستری امن و کارآمد فراهم کرده‌ایم تا
                        کاربران بتوانند به‌راحتی بهترین تکنسین‌ها را انتخاب کنند.
                    </p>
                    <!-- Links -->
                    <ul class="nav justify-content-center text-primary-hover mt-3 mt-md-0">
                        <li class="nav-item"><a class="nav-link text-white" href="#">درباره ما</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="#">شرایط</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="#">قوانین</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="#">نحوه استفاده</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="#">تماس با ما</a></li>
                        <li class="nav-item"><a class="nav-link text-white pe-0" href="#">کوکی ها</a></li>
                    </ul>
                    <!-- Social media button -->
                    <ul class="list-inline mt-3 mb-0">
                        <li class="list-inline-item">
                            <a class="btn btn-white btn-sm shadow px-2 text-facebook" href="#">
                                <i class="fab fa-fw fa-facebook-f"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a class="btn btn-white btn-sm shadow px-2 text-instagram" href="#">
                                <i class="fab fa-fw fa-instagram"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a class="btn btn-white btn-sm shadow px-2 text-twitter" href="#">
                                <i class="fab fa-fw fa-twitter"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a class="btn btn-white btn-sm shadow px-2 text-linkedin" href="#">
                                <i class="fab fa-fw fa-linkedin-in"></i>
                            </a>
                        </li>
                    </ul>
                    <!-- Bottom footer link -->
                    <div class="mt-3 text-white">تمامی حقوق محفوظ است.</div>
                </div>
            </div>
        </div>
    </footer>
    <!-- =======================
Footer END -->



    <!-- Back to top -->
    <div class="back-top"><i class="bi bi-arrow-up-short position-absolute top-50 start-50 translate-middle"></i>
    </div>

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Vendors -->
    <script src="{{ asset('assets/vendor/tiny-slider/tiny-slider-rtl.js') }}"></script>
    <script src="{{ asset('assets/vendor/glightbox/js/glightbox.js') }}"></script>

    <!-- Template Functions -->
    <script src="{{ asset('assets/js/functions.js') }}"></script>



</body>

</html>
