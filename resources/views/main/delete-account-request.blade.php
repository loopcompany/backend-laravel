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
    <script src="{{asset('assets/js/jquery-3.4.1.slim.min.js')}}">
    </script>
    <script src="{{asset('assets/js/popper.min.js')}}">
    </script>
    <script src="{{asset('assets/js/bootstrap.min.js')}}">
    </script>

</head>

<body dir="ltr" style="font-family: sans-serif !important; text-align: left;">
    <div class="breatcome_area d-flex align-items-center">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breatcome_title">
                        <div class="breatcome_title_inner pb-2">
                            <h2>Delete Account Request</h2>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Techno Breatcome Area -->
    <!-- ============================================================== -->

    <!--==================================================-->
    <!----- Start Delete Account Request Area ----->
    <!--==================================================-->
    <div class="main_contact_area style_three pt-80 pb-90">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact_from">
                        <div class="contact_from_box">
                            <div class="contact_title pb-4">
                                <h3>Submit Account Deletion Request</h3>
                            </div>

                            <div class="alert alert-warning mb-4">
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>Warning:</strong> This action is irreversible. Once your account is deleted, all your data will be permanently removed and cannot be recovered.
                            </div>

                            <div class="section_title_text pt-2 pb-4">
                                <p style="font-family: sans-serif;">Please complete the form below to request account deletion. Our team will review your request and contact you within 3-5 business days.</p>
                            </div>

                            <form id="delete_account_request_form" action="{{ route('submit.delete-account-request') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form_box mb-30">
                                            <label for="phone" class="form-label text-primary ">Phone Number <span class="text-danger">*</span></label>
                                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter your phone number" required style="text-align: left; direction: ltr;">
                                            @error('phone')
                                                <div class="text-danger mt-1"><small>{{ $message }}</small></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form_box mb-30">
                                            <label for="title" class="form-label text-primary ">Reason for Deletion <span class="text-danger">*</span></label>
                                            <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="Briefly describe the reason for deletion" required>
                                            @error('title')
                                                <div class="text-danger mt-1"><small>{{ $message }}</small></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form_box mb-30">
                                            <label for="description" class="form-label text-primary ">Detailed Description <span class="text-danger">*</span></label>
                                            <textarea id="description" name="description" cols="30" rows="10" placeholder="Please provide more details about why you want to delete your account  " required>{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="text-danger mt-1"><small>{{ $message }}</small></div>
                                            @enderror
                                        </div>
                                        <div class="quote_btn">
                                            <button class="btn btn-danger" type="submit">Submit Deletion Request</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            {{-- Display success and error messages --}}
                            @if(session('success'))
                                <div class="alert alert-success mt-3">
                                    <i class="fa fa-check-circle"></i> {{ session('success') }}
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger mt-3">
                                    <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                                </div>
                            @endif

                            <p class="form-message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--==================================================-->
    <!----- End Delete Account Request Area ----->
    <!--==================================================-->

 <script type="text/javascript" src="{{ asset('assets/js/vendor/jquery-3.2.1.min.js') }}"></script>
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
    <script type="text/javascript" src="{{ asset('assets/js/testimonial.js') }}"></script>
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



</body>

</html>

