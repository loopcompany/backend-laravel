     @php
         $headerEmail = $globalContacts->where('type', 'email')->first();
         $headerPhone = $globalContacts->where('type', 'phone')->first();
         $headerOffice = $globalContacts->where('type', 'office')->first();
     @endphp
     @if ($headerEmail || $headerPhone || $headerOffice || $globalSocials->count() > 0)
         <div class="header_top_menu pt-2 pb-2 bg_color" style="background: #050b16">
             <div class="container">
                 <div class="row">
                     <div class="col-lg-8 col-sm-8">
                         <div class="header_top_menu_address">
                             <div class="header_top_menu_address_inner">
                                 <ul>


                                     @if ($headerEmail)
                                         <li><a href="{{ $headerEmail->link }}"><i
                                                     class="fa fa-envelope-o"></i>{{ $headerEmail->name }}</a></li>
                                     @endif

                                     @if ($headerOffice)
                                         <li><a href="{{ $headerOffice->link }}"><i
                                                     class="fa fa-map-marker"></i>{{ $headerOffice->name }}</a>
                                         </li>
                                     @endif

                                     @if ($headerPhone)
                                         <li><a href="{{ $headerPhone->link }}"><i
                                                     class="fa fa-phone"></i>{{ $headerPhone->name }}</a></li>
                                     @endif
                                 </ul>
                             </div>
                         </div>
                     </div>
                     <div class="col-lg-4 col-sm-4">
                         <div class="header_top_menu_icon">
                             <div class="header_top_menu_icon_inner">
                                 <ul>
                                     @if ($globalSocials && $globalSocials->count() > 0)
                                         @foreach ($globalSocials->take(4) as $social)
                                             <li><a href="{{ $social->link }}" title="{{ $social->title }}">
                                                     @if ($social->icon)
                                                         <img src="{{ asset('storage/' . $social->icon) }}"
                                                             alt="{{ $social->title }}"
                                                             style="width: 20px; height: 20px; display: inline;">
                                                     @else
                                                         <i class="fa fa-link"></i>
                                                     @endif
                                                 </a></li>
                                         @endforeach
                                     @endif
                                 </ul>
                             </div>
                         </div>
                     </div>

                 </div>
             </div>
         </div>
     @endif
     <div id="sticky-header" class="techno_nav_manu d-md-none d-lg-block d-sm-none d-none">
         <div class="container">
             <div class="row">
                 <div class="col-md-3">
                     <div class="logo" style="margin-top: 11px;">
                         <a class="logo_img" href="{{ route('web.home') }}" title="لوپ">
                             <img src="{{ asset('assets/images/1.png') }}" alt="لوپ">
                         </a>
                         <a class="main_sticky" href="{{ route('web.home') }}" title="لوپ">
                             <img src="{{ asset('assets/images/logo.png') }}" alt="لوپ">
                         </a>
                     </div>
                 </div>
                 <div class="col-md-9">
                     <nav class="techno_menu">
                         <!-- منوی عادی -->
                         <ul class="nav_scroll nav-normal">
                             <li><a href="{{ route('web.home') }}">خانه</a></li>
                             <li><a href="{{ route('web.about') }}">درباره ما</a></li>
                             <li><a href="{{ route('web.blogs') }}">مقالات و آموزش</a></li>
                             <li><a href="{{ route('web.faqs') }}">سوالات متداول</a></li>
                             <li><a href="{{ route('web.contact') }}">تماس با ما</a></li>
                             <li><a href="{{ rtrim(config('app.user_panel_url'), '/') }}" target="_blank">پنل کاربر</a></li>
                             <li><a href="{{ rtrim(config('app.technician_panel_url'), '/') }}" target="_blank">پنل تکنسین</a></li>
                         </ul>

                         <!-- منوی استیکی -->
                         <ul class="nav_scroll nav-sticky">
                             <li><a href="{{ config('app.user_panel_grouping_url') }}" target="_blank">پنل
                                     سازمانی/شرکتی</a></li>
                             <li><a href="{{ rtrim(config('app.user_panel_url'), '/') }}" target="_blank">پنل کاربر</a></li>
                             <li><a href="{{ rtrim(config('app.technician_panel_url'), '/') }}" target="_blank">پنل تکنسین</a></li>
                             <li><a href="#contact">راهنما</a>
                                 <ul class="sub-menu">
                                     <li><a href="{{ asset('assets/guid/user.pdf') }}">راهنمای اپلیکیشن کاربر</a></li>
                                     <li><a href="{{ asset('assets/guid/organ.pdf') }}">راهنمای اپلیکیشن کاربری سازمانی
                                             شرکتی</a></li>
                                     <li><a href="{{ asset('assets/guid/tech.pdf') }}">راهنمای اپلیکیشن تکنسین</a></li>
                                 </ul>
                             </li>
                             <li><a href="{{ route('web.faqs') }}">سوالات متداول</a></li>
                             <li><a href="{{ route('web.blogs') }}">مقالات و آموزش</a></li>
                             <li><a href="{{ route('web.about') }}">درباره ما</a></li>
                             <li><a href="{{ route('web.contact') }}">تماس با ما</a></li>
                             <li class="phone"><a href="{{ $headerPhone?->link }}">{{ $headerPhone?->name }}</a>
                             </li>
                         </ul>
                     </nav>

                 </div>
             </div>
         </div>
     </div>

     <!----- Techno Mobile Menu Area ----->
     <div class="mobile-menu-area d-sm-block d-md-block d-lg-none ">
         <div class="mobile-menu">
             <nav class="techno_menu">
                 <ul class="nav_scroll">
                     <li><a href="{{ route('web.home') }}">خانه</a></li>
                     <li><a href="{{ route('web.about') }}">درباره ما</a></li>

                     <li><a href="{{ route('web.blogs') }}">مقالات و آموزش</a></li>
                     <li><a href="{{ route('web.faqs') }}">سوالات متداول</a></li>
                     <li><a href="{{ route('web.contact') }}">تماس با ما</a></li>
                     <li><a href="{{ rtrim(config('app.user_panel_url'), '/') }}" target="_blank">پنل کاربر</a></li>
                     <li><a href="{{ rtrim(config('app.technician_panel_url'), '/') }}" target="_blank">پنل متخصص</a></li>
                 </ul>
             </nav>
         </div>
     </div>

     <style>
         .nav-sticky {
             display: none;
         }

         #sticky-header .nav-sticky {
             display: none !important;
         }

         /* وقتی sticky شد */
         #sticky-header.sticky .nav-normal {
             display: none !important;
         }

         #sticky-header.sticky .nav-sticky {
             display: flex !important;
             gap: 8px;
             align-items: center;
         }

         #sticky-header.sticky .nav-sticky .phone {
             margin-right: auto;
             font-weight: 700;
         }
     </style>
