<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'داشبورد تکنسین') - لوپ</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Plugins CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/font-awesome/css/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Theme CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style-rtl.css') }}">

    @stack('styles')
</head>
<body>

<!-- Page content START -->
<section class="pt-5">
    <div class="container">
        <div class="row">
            <!-- Left sidebar START -->
            <div class="col-xl-3">
                <!-- Responsive offcanvas body START -->
                <div class="offcanvas-xl offcanvas-end" tabindex="-1" id="offcanvasSidebar">
                    <!-- Offcanvas header -->
                    <div class="offcanvas-header bg-light">
                        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">پروفایل</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasSidebar" aria-label="Close"></button>
                    </div>
                    <!-- Offcanvas body -->
                    <div class="offcanvas-body p-3 p-xl-0">
                        <div class="bg-dark border rounded-3 pb-0 p-3 w-100">
                            <!-- Dashboard menu -->
                            <div class="list-group list-group-dark list-group-borderless">
                                <a class="list-group-item {{ request()->routeIs('web.technician.dashboard') ? 'active' : '' }}" 
                                   href="{{ route('web.technician.dashboard') }}">
                                    <i class="bi bi-ui-checks-grid fa-fw me-2"></i>داشبورد
                                </a>
                                
                                <a class="list-group-item {{ request()->routeIs('web.technician.profile.show') ? 'active' : '' }}" 
                                   href="{{ route('web.technician.profile.show') }}">
                                    <i class="bi bi-person fa-fw me-2"></i>مشاهده پروفایل
                                </a>
                                
                                <a class="list-group-item {{ request()->routeIs('web.technician.profile.personal-info') ? 'active' : '' }}" 
                                   href="{{ route('web.technician.profile.personal-info') }}">
                                    <i class="bi bi-pencil-square fa-fw me-2"></i>ویرایش اطلاعات شخصی
                                </a>
                                
                                <a class="list-group-item {{ request()->routeIs('web.technician.profile.vehicle-info') ? 'active' : '' }}" 
                                   href="{{ route('web.technician.profile.vehicle-info') }}">
                                    <i class="bi bi-car-front fa-fw me-2"></i>اطلاعات وسیله نقلیه
                                </a>
                                
                                <a class="list-group-item {{ request()->routeIs('web.technician.profile.bank-info') ? 'active' : '' }}" 
                                   href="{{ route('web.technician.profile.bank-info') }}">
                                    <i class="bi bi-wallet2 fa-fw me-2"></i>اطلاعات بانکی
                                </a>
                                
                                <a class="list-group-item {{ request()->routeIs('web.technician.profile.password') ? 'active' : '' }}" 
                                   href="{{ route('web.technician.profile.password') }}">
                                    <i class="bi bi-shield-lock fa-fw me-2"></i>تغییر رمز عبور
                                </a>
                                
                                <a class="list-group-item {{ request()->routeIs('web.technician.orders') ? 'active' : '' }}" 
                                   href="{{ route('web.technician.orders') }}">
                                    <i class="bi bi-folder-check fa-fw me-2"></i>لیست سفارشات
                                </a>
                                
                                <form method="POST" action="{{ route('web.technician.logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="list-group-item text-danger bg-danger-soft-hover border-0 text-start w-100">
                                        <i class="fas fa-sign-out-alt fa-fw me-2"></i>خروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Responsive offcanvas body END -->
            </div>
            <!-- Left sidebar END -->

            <!-- Main content START -->
            <div class="col-xl-9">
                <!-- Advanced filter responsive toggler START -->
                <div class="d-xl-none mb-3">
                    <button class="btn btn-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                        <i class="fas fa-sliders-h me-2"></i>منوی کاربری
                    </button>
                </div>
                <!-- Advanced filter responsive toggler END -->

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>
            <!-- Main content END -->
        </div>
    </div>
</section>
<!-- Page content END -->

<!-- Bootstrap JS -->
<script src="{{ asset('assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>

<!-- Template Functions -->
<script src="{{ asset('assets/js/functions.js') }}"></script>

@stack('scripts')

</body>
</html>
