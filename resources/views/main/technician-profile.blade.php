@extends('layout.main.header')
@section('content')
    <!-- ============================================================== -->
    <!-- Start Breatcome Area -->
    <!-- ============================================================== -->
    <div class="breatcome_area d-flex align-items-center">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breatcome_title">
                        <div class="breatcome_title_inner pb-2">
                            <h2>اطلاعات تکنسین</h2>
                        </div>
                        <div class="breatcome_content">
                            <ul>
                                <li><a href="{{ route('web.home') }}">خانه</a> 
                                    <i class="fa fa-angle-left"></i> <span>اطلاعات تکنسین</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Breatcome Area -->
    <!-- ============================================================== -->

    @if(!$technician)
        <!-- ============================================================== -->
        <!-- Technician Not Found -->
        <!-- ============================================================== -->
        <div class="error_area pt-80 pb-80">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <div class="error_content">
                            <i class="fas fa-exclamation-triangle" style="font-size: 100px; color: #ff6b6b; margin-bottom: 30px;"></i>
                            <h2>تکنسین یافت نشد</h2>
                            <p class="mt-3">متاسفانه اطلاعات تکنسین مورد نظر در سیستم موجود نیست.</p>
                            <a href="{{ route('web.home') }}" class="btn btn-primary mt-4">بازگشت به صفحه اصلی</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- ============================================================== -->
        <!-- Start Technician Profile Area -->
        <!-- ============================================================== -->
        <div class="technician_profile_area pt-80 pb-80">
            <div class="container">
                <div class="row">
                    <!-- Profile Sidebar -->
                    <div class="col-lg-4 col-md-12">
                        <div class="technician_sidebar">
                            <!-- Profile Card -->
                            <div class="profile_card shadow-sm mb-4">
                                <div class="card-body text-center p-4">
                                    @if($technician->profile_photo_path)
                                        <img src="{{ url('storage/' . $technician->profile_photo_path) }}" 
                                             alt="{{ $technician->name }}" 
                                             class="rounded-circle mb-3"
                                             style="width: 150px; height: 150px; object-fit: cover; border: 5px solid #f8f9fa;">
                                    @else
                                        <div class="profile_placeholder rounded-circle mb-3 d-flex align-items-center justify-content-center mx-auto"
                                             style="width: 150px; height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 5px solid #f8f9fa;">
                                            <i class="fas fa-user" style="font-size: 60px; color: white;"></i>
                                        </div>
                                    @endif
                                    
                                    <h3 class="mb-2">{{ $technician->name }}</h3>
                                    
                                    @if($technician->referral_code)
                                        <p class="text-muted mb-3">
                                            <i class="fas fa-id-card ml-2"></i>
                                            کد پرسنلی: <strong>{{ $technician->referral_code }}</strong>
                                        </p>
                                    @endif

                                    @if($technician->has_access)
                                        <span class="badge badge-success p-2">
                                            <i class="fas fa-check-circle ml-1"></i> تایید شده
                                        </span>
                                    @else
                                        <span class="badge badge-warning p-2">
                                            <i class="fas fa-clock ml-1"></i> در انتظار تایید
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Contact Info Card -->
                            <div class="contact_info_card shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <h5 class="mb-3 pb-2 border-bottom">
                                        <i class="fas fa-address-book ml-2"></i> اطلاعات تماس
                                    </h5>
                                    
                                    @if($technician->phone)
                                        <div class="info_item mb-3">
                                            <i class="fas fa-phone ml-2 text-primary"></i>
                                            <strong>موبایل:</strong>
                                            <a href="tel:{{ $technician->phone }}" dir="ltr">{{ $technician->phone }}</a>
                                        </div>
                                    @endif

                                    @if($technician->email)
                                        <div class="info_item mb-3">
                                            <i class="fas fa-envelope ml-2 text-primary"></i>
                                            <strong>ایمیل:</strong>
                                            <a href="mailto:{{ $technician->email }}">{{ $technician->email }}</a>
                                        </div>
                                    @endif

                                    @if($technician->telephone)
                                        <div class="info_item mb-3">
                                            <i class="fas fa-phone-square ml-2 text-primary"></i>
                                            <strong>تلفن ثابت:</strong>
                                            <span dir="ltr">{{ $technician->telephone }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Stats Card -->
                            <div class="stats_card shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="mb-3 pb-2 border-bottom">
                                        <i class="fas fa-chart-line ml-2"></i> آمار فعالیت
                                    </h5>
                                    
                                    <div class="stat_item d-flex justify-content-between mb-3">
                                        <span><i class="fas fa-briefcase ml-2 text-success"></i> تعداد سفارشات:</span>
                                        <strong>{{ $ordersCount }}</strong>
                                    </div>
                                    
                                    <div class="stat_item d-flex justify-content-between">
                                        <span><i class="fas fa-star ml-2 text-warning"></i> امتیاز:</span>
                                        <strong>{{ number_format($averageRating, 1) }} از 5</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Content -->
                    <div class="col-lg-8 col-md-12">
                        <!-- Personal Information -->
                        <div class="info_section shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h4 class="mb-4 pb-2 border-bottom">
                                    <i class="fas fa-user ml-2"></i> اطلاعات شخصی
                                </h4>
                                
                                <div class="row">
                                    @if($technician->melicode)
                                        <div class="col-md-6 mb-3">
                                            <div class="info_box p-3 bg-light rounded">
                                                <small class="text-muted d-block mb-1">کد ملی</small>
                                                <strong dir="ltr">{{ $technician->melicode }}</strong>
                                            </div>
                                        </div>
                                    @endif

                                   

                                    @if($technician->education_status)
                                        <div class="col-md-6 mb-3">
                                            <div class="info_box p-3 bg-light rounded">
                                                <small class="text-muted d-block mb-1">میزان تحصیلات</small>
                                                <strong>{{ $technician->education_status }}</strong>
                                            </div>
                                        </div>
                                    @endif
                                    @if($technician->education_field)
                                        <div class="col-md-6 mb-3">
                                            <div class="info_box p-3 bg-light rounded">
                                                <small class="text-muted d-block mb-1">رشته تحصیلی</small>
                                                <strong>{{ $technician->education_field }}</strong>
                                            </div>
                                        </div>
                                    @endif

                                   
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        

                        <!-- Skills Information -->
                        @if($technician->software_skill || $technician->hardware_skill || $technician->idea)
                            <div class="info_section shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <h4 class="mb-4 pb-2 border-bottom">
                                        <i class="fas fa-tools ml-2"></i> مهارت‌ها و توانمندی‌ها
                                    </h4>
                                    
                                    @if($technician->software_skill)
                                        <div class="skill_box mb-3">
                                            <h6 class="text-primary mb-2">
                                                <i class="fas fa-laptop-code ml-2"></i> مهارت‌های نرم‌افزاری
                                            </h6>
                                            <p class="text-justify">{{ $technician->software_skill }}</p>
                                        </div>
                                    @endif

                                    @if($technician->hardware_skill)
                                        <div class="skill_box mb-3">
                                            <h6 class="text-primary mb-2">
                                                <i class="fas fa-microchip ml-2"></i> مهارت‌های سخت‌افزاری
                                            </h6>
                                            <p class="text-justify">{{ $technician->hardware_skill }}</p>
                                        </div>
                                    @endif

                                    @if($technician->idea)
                                        <div class="skill_box mb-3">
                                            <h6 class="text-primary mb-2">
                                                <i class="fas fa-lightbulb ml-2"></i> ایده‌ها و نظرات
                                            </h6>
                                            <p class="text-justify">{{ $technician->idea }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Vehicle Information -->
                        @if($technician->vehicle_type || $technician->car_model)
                            <div class="info_section shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <h4 class="mb-4 pb-2 border-bottom">
                                        <i class="fas fa-car ml-2"></i> اطلاعات وسیله نقلیه
                                    </h4>
                                    
                                    <div class="row">
                                        @if($technician->vehicle_type)
                                            <div class="col-md-6 mb-3">
                                                <div class="info_box p-3 bg-light rounded">
                                                    <small class="text-muted d-block mb-1">نوع وسیله</small>
                                                    <strong>{{ $technician->vehicle_type }}</strong>
                                                </div>
                                            </div>
                                        @endif

                                        @if($technician->car_model)
                                            <div class="col-md-6 mb-3">
                                                <div class="info_box p-3 bg-light rounded">
                                                    <small class="text-muted d-block mb-1">مدل خودرو</small>
                                                    <strong>{{ $technician->car_model }}</strong>
                                                </div>
                                            </div>
                                        @endif

                                        @if($technician->car_color)
                                            <div class="col-md-6 mb-3">
                                                <div class="info_box p-3 bg-light rounded">
                                                    <small class="text-muted d-block mb-1">رنگ خودرو</small>
                                                    <strong>{{ $technician->car_color }}</strong>
                                                </div>
                                            </div>
                                        @endif

                                        @if($technician->car_plate)
                                            <div class="col-md-6 mb-3">
                                                <div class="info_box p-3 bg-light rounded">
                                                    <small class="text-muted d-block mb-1">پلاک خودرو</small>
                                                    <strong dir="ltr">{{ $technician->car_plate }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Technician Profile Area -->
        <!-- ============================================================== -->
    @endif

    <style>
        .technician_sidebar .card-body,
        .info_section .card-body {
            background: white;
            border-radius: 10px;
        }
        
        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
        }
        
        .info_item a {
            color: #333;
            text-decoration: none;
        }
        
        .info_item a:hover {
            color: #667eea;
        }
        
        .badge {
            font-size: 14px;
        }
        
        .info_box {
            transition: all 0.3s ease;
        }
        
        .info_box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
        }
        
        .skill_box {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-right: 4px solid #667eea;
        }
    </style>
@endsection
