@extends('layout.main.header')
@section('content')
<style>
    p{
        text-align: justify;
    }
</style>

<main>


    <section>
        <div class="container">

            <div class="row g-4">
                <div class="col-10 text-center mx-auto position-relative">
                    <h1 class="position-relative fs-3">درباره لوپ</h1>
                </div>
            </div>

            <div class="row g-4 mt-0 mt-lg-5">

                <div class="col-6 col-md-4">
                    <div class="row g-4">
                        <div class="col-10 col-lg-6">
                            <img class="rounded-4" src="{{ asset('assets/images/about/654.webp') }}" alt="">
                        </div>
                        <div class="col-12">
                            <img class="rounded-4" src="{{ asset('assets/images/about/banner.webp') }}" alt="">
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 position-relative">
                    <img class="rounded-4" src="{{ asset('assets/images/about/1.jpeg') }}" alt="">
                </div>

                <div class="col-md-4">
                    <div class="row g-4">
                        <div class="col-sm-6 col-md-12">
                            <div class="bg-grad rounded-4 p-5 text-start">
                                <span class="text-white">هدف ما:</span>
                                <h3 class="text-white ff-vb">“ارائه بهترین خدمات با مناسب‌ترین قیمت‌ها است”</h3>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-12 col-lg-6">
                            <img class="rounded-4" src="{{ asset('assets/images/about/684684.webp') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pt-0 pt-md-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-lg-12">
                    @foreach($abouts as $about)
                        <div class="row" @if($loop->iteration%2 == 0) dir="ltr" @endif >
                            <div class="col-lg-9">
                                <h4 style="text-align: right">{{ $about->title }}</h4>
                                {!! $about->des !!}
                            </div>
                            <div class="col-lg-3">
                                <img class="rounded-4" src="{{ asset('storage/'.$about->image_path) }}" style="height:100%; width:100%; object-fit:cover;" />
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>


        </div>
    </section>


</main>


@endsection
