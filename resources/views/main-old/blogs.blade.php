@extends('layout.main.header')
@section('content')
    <main>
        
        <section class="py-5">
            <div class="container">
                <div class="row position-relative">
                    <figure class="position-absolute top-0 start-0 d-none d-sm-block">	
                        <svg width="22px" height="22px" viewBox="0 0 22 22">
                            <polygon class="fill-purple" points="22,8.3 13.7,8.3 13.7,0 8.3,0 8.3,8.3 0,8.3 0,13.7 8.3,13.7 8.3,22 13.7,22 13.7,13.7 22,13.7 "></polygon>
                        </svg>
                    </figure>
                    <div class="col-lg-10 mx-auto text-center position-relative">
                        <figure class="position-absolute top-50 end-0 translate-middle-y">
                            <svg width="27px" height="27px">
                                <path class="fill-orange" d="M13.122,5.946 L17.679,-0.001 L17.404,7.528 L24.661,5.946 L19.683,11.533 L26.244,15.056 L18.891,16.089 L21.686,23.068 L15.400,19.062 L13.122,26.232 L10.843,19.062 L4.557,23.068 L7.352,16.089 L-0.000,15.056 L6.561,11.533 L1.582,5.946 L8.839,7.528 L8.565,-0.001 L13.122,5.946 Z"></path>
                            </svg>
                        </figure>
                        <h1 class="fs-2">مجله لوپ</h1>
                    </div>
                </div>
            </div>
        </section>


        <section class="position-relative pt-0">
            <div class="container">
                <div class="row g-4 filter-container overflow-hidden" data-isotope='{"layoutMode": "masonry"}'>
                    @foreach($blogs as $blog)
                        <div class="col-sm-6 col-lg-4 grid-item">
                            <div class="card bg-transparent">
                                <div class="overflow-hidden rounded-3">
                                    <img src="{{ asset('storage/'.$blog->image_path) }}" 
                                         class="card-img" 
                                         alt="{{ $blog->title }}" 
                                         style="height:350px; object-fit:cover">
                                    <div class="bg-overlay bg-dark opacity-4"></div>
                                    <div class="card-img-overlay d-flex align-items-start p-3">
                                        <a href="{{ $blog->slug 
                                            ? route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) 
                                            : route('blog.detail', ['id' => $blog->id]) }}" 
                                            class="badge text-bg-danger" 
                                            style="text-wrap: wrap;max-width: 50%;line-height: 20px;">
                                            {{ $blog->category->title ?? 'بدون دسته‌بندی' }}
                                        </a>
                                    </div>
                                </div>

                                <div class="card-body px-3">
                                    <h2 class="card-title fw-normal">
                                        <a href="{{ $blog->slug 
                                            ? route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) 
                                            : route('blog.detail', ['id' => $blog->id]) }}" style="font-size: 24px; font-weight: bolder; text-align: center;">
                                            {{ $blog->title }}
                                        </a>
                                    </h2>

                                    <p class="text-truncate-2">
                                        {!! Str::words(strip_tags($blog->des), 20, '...') !!}
                                    </p>

                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0 fw-normal">
                                            <a href="{{ $blog->slug 
                                                ? route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]) 
                                                : route('blog.detail', ['id' => $blog->id]) }}">
                                                مشاهده
                                            </a>
                                        </h6>
                                        <span class="small">
                                            {{ \Morilog\Jalali\CalendarUtils::strftime('j F Y', strtotime($blog->created_at)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-center mt-4">
    {{ $blogs->appends(request()->input())->links('pagination::bootstrap-5') }}
</div>
                </div>              
            </div>              
        </section>              
    </main>
@endsection
