@extends('layout.main.header')

@section('meta')
    <title>{{ $blog->seo_title ?? $blog->title }}</title>
    <meta name="description" content="{{ $blog->meta_description ?? Str::limit(strip_tags($blog->short_des), 160) }}">
@endsection

@section('content')
    <div class="breatcome_area d-flex align-items-center">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breatcome_title">
                        <div class="breatcome_title_inner pb-2">
                            <h2>{{ Str::limit($blog->title, 50) }}</h2>
                        </div>
                        <div class="breatcome_content">
                            <ul>
                                <li><a href="{{ route('web.home') }}">خانه</a> 
                                    <i class="fa fa-angle-left"></i> 
                                    <a href="{{ route('web.blogs') }}">مقالات و آموزش</a>
                                    <i class="fa fa-angle-left"></i> 
                                    <span>{{ Str::limit($blog->title, 30) }}</span>
                                </li>
                            </ul>
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
    <!----- Start Blog Detail Area ----->
    <!--==================================================-->
    <div class="blog_detail_area pt-90 pb-90">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="blog_detail_content">
                        <!-- Blog Header -->
                        <div class="blog_detail_header mb-4">
                            <div class="blog_meta mb-3">
                                <ul class="list-inline">
                                    <li class="list-inline-item">
                                        <i class="fa fa-calendar"></i> {{ Morilog\Jalali\Jalalian::fromDateTime($blog->created_at)->format('Y/m/d') }}
                                    </li>
                                    @if($blog->category)
                                        <li class="list-inline-item">
                                            <i class="fa fa-folder"></i> {{ $blog->category->title }}
                                        </li>
                                    @endif
                                    <li class="list-inline-item">
                                        <i class="fa fa-clock-o"></i> {{ ceil(str_word_count(strip_tags($blog->des)) / 200) }} دقیقه مطالعه
                                    </li>
                                </ul>
                            </div>
                            <h1 class="blog_title">{{ $blog->title }}</h1>
                            @if($blog->short_des)
                                <p class="blog_excerpt">{!! $blog->short_des !!}</p>
                            @endif
                        </div>

                        <!-- Blog Image -->
                        @if($blog->image_path)
                            <div class="blog_image mb-4">
                                <img src="{{ asset('storage/' . $blog->image_path) }}" alt="{{ $blog->title }}" class="img-fluid rounded">
                            </div>
                        @endif

                        <!-- Blog Content -->
                        <div class="blog_content">
                            {!! $blog->des !!}
                        </div>

                        <!-- Blog Media -->
                        @if($blog->video_path || $blog->audio_path || $blog->document_path)
                            <div class="blog_media mt-5">
                                <h3>فایل‌های ضمیمه</h3>
                                <div class="row">
                                    @if($blog->video_path)
                                        <div class="col-md-12 mb-3">
                                            <div class="media_item">
                                                <h5><i class="fa fa-video-camera"></i> ویدیو</h5>
                                                <video controls class="w-100" style="max-height: 400px;">
                                                    <source src="{{ asset('storage/' . $blog->video_path) }}" type="video/mp4">
                                                    مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                                                </video>
                                            </div>
                                        </div>
                                    @endif

                                    @if($blog->audio_path)
                                        <div class="col-md-12 mb-3">
                                            <div class="media_item">
                                                <h5><i class="fa fa-music"></i> فایل صوتی</h5>
                                                <audio controls class="w-100">
                                                    <source src="{{ asset('storage/' . $blog->audio_path) }}" type="audio/mpeg">
                                                    مرورگر شما از پخش صوت پشتیبانی نمی‌کند.
                                                </audio>
                                            </div>
                                        </div>
                                    @endif

                                    @if($blog->document_path)
                                        <div class="col-md-12 mb-3">
                                            <div class="media_item">
                                                <h5><i class="fa fa-file-pdf-o"></i> فایل ضمیمه</h5>
                                                <a href="{{ asset('storage/' . $blog->document_path) }}" target="_blank" class="btn btn-outline-primary">
                                                    <i class="fa fa-download"></i> دانلود فایل
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- FAQs Section -->
                        @if($blog->faqs && count($blog->faqs) > 0)
                            <div class="blog_faqs mt-5">
                                <h3>سوالات متداول</h3>
                                <div class="accordion" id="blogFaqAccordion">
                                    @foreach($blog->faqs as $index => $faq)
                                        <div class="card">
                                            <div class="card-header" id="heading{{ $index }}">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse{{ $index }}" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                                        {{ $faq['question'] ?? '' }}
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="collapse{{ $index }}" class="collapse {{ $index == 0 ? 'show' : '' }}" aria-labelledby="heading{{ $index }}" data-parent="#blogFaqAccordion">
                                                <div class="card-body">
                                                    {!! $faq['answer'] ?? '' !!}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Share Section -->
                        <div class="blog_share mt-5 pt-4 border-top">
                            <h5>اشتراک‌گذاری:</h5>
                            <div class="share_buttons" style="margin-top: 10px">
                                <a href="https://t.me/share/url?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($blog->title) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-telegram"></i> تلگرام
                                </a>
                                <a href="https://wa.me/?text={{ urlencode($blog->title . ' ' . request()->fullUrl()) }}" target="_blank" class="btn btn-outline-success btn-sm">
                                    <i class="fa fa-whatsapp"></i> واتساپ
                                </a>
                                <a href="javascript:void(0)" onclick="copyToClipboard('{{ request()->fullUrl() }}')" class="btn btn-outline-secondary btn-sm">
                                    <i class="fa fa-copy"></i> کپی لینک
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="blog_sidebar">
                        <!-- Recent Blogs -->
                        @if($recent_blogs->count() > 0)
                            <div class="sidebar_widget mb-4">
                                <h4 class="widget_title">مقالات اخیر</h4>
                                <div class="recent_blogs">
                                    @foreach($recent_blogs as $recent_blog)
                                        <div class="recent_blog_item mb-3">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    @if($recent_blog->image_path)
                                                        <img src="{{ asset('storage/' . $recent_blog->image_path) }}" alt="{{ $recent_blog->title }}" class="img-fluid rounded">
                                                    @else
                                                        <div class="no_image_small">
                                                            <i class="fa fa-image"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-8">
                                                    <h6><a href="{{ route('blog.detail', ['id' => $recent_blog->id, 'slug' => $recent_blog->slug]) }}">{{ Str::limit($recent_blog->title, 60) }}</a></h6>
                                                    <small class="text-white">{{ Morilog\Jalali\Jalalian::fromDateTime($recent_blog->created_at)->format('Y/m/d') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Back to Blog -->
                        <div class="sidebar_widget">
                            <a href="{{ route('web.blogs') }}" class="btn btn-primary btn-block">
                                <i class="fa fa-arrow-right"></i> بازگشت به مقالات و آموزش
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--==================================================-->
    <!----- End Blog Detail Area ----->
    <!--==================================================-->

    <style>
        .blog_detail_content {
            background: #1d283a;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .blog_meta ul {
            padding: 0;
            margin: 0;
        }
        
        .blog_meta li {
            color: #fff;
            font-size: 14px;
            margin-left: 20px;
        }
        
        .blog_meta i {
            color: #007bff;
            margin-left: 5px;
        }
        
        .blog_title {
            font-size: 28px;
            font-weight: 700;
            color: #dba738;
            line-height: 1.3;
            margin-bottom: 15px;
        }
        
        .blog_excerpt {
            font-size: 16px;
            color: #ffffff;
            line-height: 1.6;
            font-weight: 500;
        }
        
        .blog_content {
            font-size: 16px;
            line-height: 1.8;
            color: #ffffff;
            text-align: justify;
        }
        
        .blog_content p {
            margin-bottom: 20px;
        }
        
        .media_item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .media_item h5 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .blog_faqs .card {
            margin-bottom: 10px;
            border: 1px solid #dca82c;
        }
        .accordion>.card>.card-header{
                background: #040b23;
        }
        
        .blog_faqs .btn-link {
            color: #ffffff;
            text-decoration: none;
            text-align: right;
            width: 100%;
        }
        
        .share_buttons .btn {
            margin-left: 10px;
            margin-bottom: 10px;
        }
        
        .blog_sidebar {
            padding-right: 30px;
        }
        
        .sidebar_widget {
            background: #1d283a;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .widget_title {
            font-size: 18px;
            font-weight: 600;
            color: #dca82c;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .recent_blog_item {
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .recent_blog_item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .recent_blog_item h6 a {
            color: #dca82c;
            text-decoration: none;
            font-weight: 500;
        }
        
        .recent_blog_item h6 a:hover {
            color: #007bff;
        }
        
        .no_image_small {
            height: 80px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .blog_sidebar {
                padding-right: 0;
                margin-top: 30px;
            }
            
            .blog_detail_content {
                padding: 20px;
            }
            
            .blog_title {
                font-size: 24px;
            }
        }
    </style>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('لینک کپی شد!');
            }, function(err) {
                console.error('خطا در کپی کردن: ', err);
            });
        }
    </script>
@endsection
