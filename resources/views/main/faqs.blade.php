@extends('layout.main.header')
@section('content')
    <picture class="faqs-hero">
        <source
            media="(max-width: 767px)"
            srcset="{{ asset('assets/new-style/mobile/faq.png') }}"
            width="1024"
            height="1536"
        >
        <img
            src="{{ asset('assets/new-style/faq.png') }}"
            alt="سوالات متداول لوپ"
            width="1448"
            height="1086"
            fetchpriority="high"
        >
    </picture>
    <!-- ============================================================== -->
    <!-- End Techno Breatcome Area -->
    <!-- ============================================================== -->


    <div class="accordion_area pt-90 pb-90">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title text_center mb-50">
                        <div class="section_main_title">
                            <h1>برخی از سوالات و پاسخ آنها </h1>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>

                        {{-- فرم جستجو --}}
                        <div class="search_form mt-4">
                            <form method="GET" action="{{ route('web.faqs') }}">
                                <div class="row justify-content-center">
                                    <div class="col-lg-6">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" 
                                                   value="{{ request('search') }}" 
                                                   placeholder="جستجو در سوالات متداول...">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="fa fa-search"></i> جستجو
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if(request('search'))
                                    <div class="mt-2">
                                        <a href="{{ route('web.faqs') }}" class="btn btn-sm btn-secondary">
                                            <i class="fa fa-times"></i> پاک کردن جستجو
                                        </a>
                                    </div>
                                @endif
                            </form>
                        </div>
                        
                        @if(request('search'))
                            <div class="search_result_info mt-3">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    نتایج جستجو برای: <strong>"{{ request('search') }}"</strong>
                                    ({{ $faqs->count() }} نتیجه یافت شد)
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="row">
                @if($faqs->count() > 0)
                    @php 
                        $halfCount = ceil($faqs->count() / 2);
                        $firstHalf = $faqs->slice(0, $halfCount);
                        $secondHalf = $faqs->slice($halfCount);
                    @endphp
                    
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="panel-group default symb" id="accordion">
                            @foreach($firstHalf as $index => $faq)
                                <div class="panel panel-default">
                                    <div class="panel-heading mb-3">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#ac1{{ $faq->id }}">
                                                <i class="fa fa-check-circle"></i>
                                                {{ $faq->title }}
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="ac1{{ $faq->id }}" class="panel-collapse collapse {{ $index == 0 ? 'show' : '' }}">

                                        <div class="panel-body pl-4 pr-4">
                                            <p>{{ $faq->description }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="panel-group default symb" id="accordion2">
                            @foreach($secondHalf as $index => $faq)
                                <div class="panel panel-default">
                                    <div class="panel-heading mb-3">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion2" href="#ac2{{ $faq->id }}">
                                                <i class="fa fa-check-circle"></i>
                                                {{ $faq->title }}
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="ac2{{ $faq->id }}" class="panel-collapse collapse">
                                        <div class="panel-body pl-4 pr-4">
                                            <p>{{ $faq->description }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="col-lg-12 text-center">
                        <div class="alert alert-info">
                            @if(request('search'))
                                <i class="fa fa-search"></i>
                                نتیجه‌ای برای جستجوی شما یافت نشد.
                                <br>
                                <a href="{{ route('web.faqs') }}" class="btn btn-sm btn-primary mt-2">
                                    مشاهده همه سوالات
                                </a>
                            @else
                                <i class="fa fa-info-circle"></i>
                                هیچ سوال متداولی موجود نیست.
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <style>
        .faqs-hero,
        .faqs-hero img {
            display: block;
            width: 100%;
        }

        .faqs-hero img {
            height: auto;
        }
    </style>
@endsection
