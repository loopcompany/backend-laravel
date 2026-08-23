@extends('layout.main.header')
@section('content')

<main>

    <section class="bg-light py-5">
        <div class="container">
            <div class="row g-4 g-md-5 position-relative">
                
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
                    <h1 class="fs-3">سلام چجوری میتونیم کمک کنیم؟</h1>
                    <div class="col-lg-8 mx-auto text-center mt-4">
                        <form method="GET" action="{{ url()->current() }}" class="bg-body shadow rounded p-2">
                            @csrf
                            <div class="input-group">
                                <input 
                                    class="form-control border-0 me-1" 
                                    type="text" 
                                    name="search" 
                                    placeholder="جستجو در سوالات متداول..."
                                    value="{{ request('search') }}"
                                >
                                <button type="submit" class="btn btn-blue mb-0 rounded">
                                    جستجو
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    
    <section class="pt-5 pb-0 pb-lg-5">
        <div class="container">
        
            <div class="row g-4 g-md-5">
                
                <div class="col-lg-12">
                    <h3 class="mb-4 fs-5">سوالات متداول</h3>
    
                    <div class="accordion accordion-icon accordion-bg-light" id="accordionExample2">
                        @if($faqs->count() > 0)
                            @foreach($faqs as $faq)
                                <div class="accordion-item mb-3">
                                    <h6 class="accordion-header font-base" id="heading-{{ $faq->id }}">
                                        <button class="accordion-button fw-bold rounded d-inline-block collapsed d-block pe-5" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#collapse-{{ $faq->id }}" 
                                            aria-expanded="false" aria-controls="collapse-{{ $faq->id }}">
                                            {{ strip_tags($faq->title) }}
                                        </button>
                                    </h6>
                                    <div id="collapse-{{ $faq->id }}" class="accordion-collapse collapse" 
                                        aria-labelledby="heading-{{ $faq->id }}" data-bs-parent="#accordionExample{{ $faq->id }}">
                                        <div class="accordion-body mt-3">
                                            {!! nl2br(strip_tags($faq->des)) !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                متاسفانه نتیجه‌ای یافت نشد.
                            </div>
                        @endif
                    </div>
                </div>
    
            </div>
        </div>
    </section>

    </main>

@endsection