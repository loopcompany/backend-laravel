@extends('layout.main.header')
@section('meta_title', 'مقالات و آموزش')
@section('content')
    <picture class="blogs-hero">
        <source
            media="(max-width: 767px)"
            srcset="{{ asset('assets/new-style/mobile/blog.png') }}"
            width="1024"
            height="1536"
        >
        <img
            src="{{ asset('assets/new-style/blog.png') }}"
            alt="مقالات و آموزش لوپ"
            width="1536"
            height="1024"
            fetchpriority="high"
        >
    </picture>
    <!-- ============================================================== -->
    <!-- End Techno Breatcome Area -->
    <!-- ============================================================== -->

    <!--==================================================-->
    <!----- Start Blog Area ----->
    <!--==================================================-->
    <div class="blog_area pt-90 pb-90">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title text_center mb-50">
                        <div class="section_main_title">
                            <h1>مقالات و آموزش</h1>
                        </div>
                        <div class="section_title_text pt-3">
                            <p>در این بخش آخرین مقالات، اخبار و اطلاعات مفید را مطالعه کنید.</p>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                @if($blogs->count() > 0)
                    @foreach($blogs as $blog)
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="single_blog mb-4">
                                <div class="single_blog_thumb">
                                    @if($blog->image_path)
                                        <img src="{{ asset('storage/' . $blog->image_path) }}" alt="{{ $blog->title }}" class="img-fluid">
                                    @else
                                        <div class="no_image_placeholder">
                                            <i class="fa fa-image"></i>
                                            <span>بدون تصویر</span>
                                        </div>
                                    @endif
                                    @if($blog->category)
                                        <div class="blog_category">
                                            <span class="badge badge-primary">{{ $blog->category->title }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="single_blog_content">
                                    <div class="single_blog_meta mb-2">
                                        <ul>
                                            <li><i class="fa fa-calendar"></i> {{ Morilog\Jalali\Jalalian::fromDateTime($blog->created_at)->format('Y/m/d') }}</li>
                                            @if($blog->category)
                                                <li><i class="fa fa-folder"></i> {{ $blog->category->title }}</li>
                                            @endif
                                        </ul>
                                    </div>
                                    <div class="single_blog_title">
                                        <h3><a href="{{ route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) }}">{{ $blog->title }}</a></h3>
                                    </div>
                                    @if($blog->short_des)
                                        <div class="single_blog_text">
                                            <p>{!! Str::limit($blog->short_des, 150) !!}</p>
                                        </div>
                                    @endif
                                    <div class="single_blog_button">
                                        <a href="{{ route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) }}" class="btn btn-primary btn-sm">
                                            ادامه مطلب <i class="fa fa-angle-left"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-lg-12">
                        <div class="no_blogs_content text-center">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                هیچ مقاله‌ای موجود نیست.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Pagination -->
            @if($blogs->count() > 0)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="pagination_area text-center mt-5">
                            {{ $blogs->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <!--==================================================-->
    <!----- End Blog Area ----->
    <!--==================================================-->

    <style>
        .blogs-hero,
        .blogs-hero img {
            display: block;
            width: 100%;
        }

        .blogs-hero img {
            height: auto;
        }

        .single_blog {
            background: #dca82c;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .single_blog:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .single_blog_thumb {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        
        .single_blog_thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .single_blog:hover .single_blog_thumb img {
            transform: scale(1.05);
        }
        
        .no_image_placeholder {
            height: 100%;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .no_image_placeholder i {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .blog_category {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .blog_category .badge {
            font-size: 12px;
            padding: 5px 10px;
        }
        
        .single_blog_content {
            padding: 25px;
        }
        
        .single_blog_meta ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 15px;
        }
        
        .single_blog_meta li {
            font-size: 13px;
            color: #fff;
        }
        
        .single_blog_meta i {
            margin-left: 5px;
            color: #007bff;
        }
        
        .single_blog_title h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .single_blog_title a {
            color: #dca82c;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .single_blog_title a:hover {
            color: #007bff;
        }
        
        .single_blog_text {
            margin-bottom: 20px;
        }
        
        .single_blog_text p {
            color: #fff;
            line-height: 1.6;
            margin: 0;
        }
        
        .single_blog_button .btn {
            font-size: 14px;
            padding: 8px 20px;
            border-radius: 5px;
        }
        
        .pagination_area .pagination {
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .single_blog_meta ul {
                flex-direction: column;
                gap: 5px;
            }
            
            .single_blog_content {
                padding: 20px;
            }
        }
    </style>
@endsection
