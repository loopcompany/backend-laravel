@extends('layout.main.header')

@section('meta')
    <title>{{ $blog->seo_title ?? $blog->title }}</title>
    <meta name="description" content="{{ $blog->meta_description ?? Str::limit(strip_tags($blog->short_des), 160) }}">
@endsection

@section('content')
<main>
    <section class="pt-3 pt-xl-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="row g-4">
                        <div class="col-12">
                            <h1>{{ $blog->title }}</h1>
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item fw-light h6 me-3 mb-1 mb-sm-0">
                                    دسته بندی:
                                    <a class="fz-16" href="#">
                                        <span class="post_tag post_tag">{{ $blog->category ? $blog->category->title : 'عمومی' }}</span>
                                    </a>
                                </li>
                                <li class="list-inline-item fw-light h6 me-3 mb-1 mb-sm-0">
                                    <i class="fas fa-fw fa-clock"></i> آخرین بروزرسانی:
                                    {{ \Morilog\Jalali\CalendarUtils::strftime('j F Y', strtotime($blog->updated_at)) }}
                                </li>
                            </ul>
                        </div>
                        
                        <div class="col-12">
                            <img src="{{ asset('storage/'.$blog->image_path) }}" alt="{{ $blog->title }}" 
                                 style="max-height:470px; object-fit:cover; width:100%; border-radius: 8px;" />
                        </div>
                        
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body aten" style="direction: rtl !important;">
                                    <div class="det">
                                        {!! $blog->des !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-4">
                    @if($recent_blogs->count() > 0)
                    <div class="card card-body shadow p-4 mb-4">
                        <h3 class="mb-3 fs-5">آخرین مجله‌ها</h3>
                        @foreach($recent_blogs as $recent)
                            <div class="row gx-3 mb-3">
                                <div class="col-4">
                                    <a href="{{ route('blog.detail', ['id'=> $recent->id]) }}">
                                        <img class="rounded" src="{{ asset('storage/'.$recent->image_path) }}" 
                                             alt="{{ $recent->title }}" style="max-height:72px; width:100%; object-fit:cover">
                                    </a>
                                </div>
                                <div class="col-8">
                                    <h4 class="mb-0 fw-normal">
                                        <a style="font-size: large;" href="{{ route('blog.detail', ['id'=> $recent->id]) }}">
                                            {{ $recent->title }}
                                        </a>
                                    </h4>
                                    <ul class="list-group list-group-borderless mt-1 d-flex justify-content-between">
                                        <li class="list-group-item px-0 d-flex justify-content-between">
                                            <span class="text-success" style="font-size: large;">
                                                {{ \Morilog\Jalali\CalendarUtils::strftime('j F Y', strtotime($recent->created_at)) }}
                                            </span>
                                            <a href="{{ route('blog.detail', ['id'=> $recent->id]) }}">
                                                <span class="h6 fw-light">مشاهده</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</main>

<style>
    .aten h2{ direction:rtl; font-size:28px !important; line-height: inherit !important; }
    .aten h3{ direction:rtl; font-size:22px !important; line-height: inherit !important; }
    .aten p{ direction:rtl; text-align: justify; }
    .aten p img{ height: auto !important; width: 100% !important; border-radius: 12px !important; }
    .aten span{ direction: rtl; text-align: justify !important; }
    .aten li{ direction: rtl; text-align: justify !important; }
    .aten ul{ direction: rtl; text-align: justify !important; }
    .aten img{ border-radius: 12px !important; }
    .aten table{ width: 100% !important; }
    .det img { width: 70% !important; max-width: 100% !important; max-height: 400px !important; object-fit: cover !important; }
    .det { text-align: justify; }
    .det figure { max-width: 100% !important; }
    .det figure table { max-width: 100% !important; }
</style>
@endsection
