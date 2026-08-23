@extends('layout.main.header')
@section('content')
    <main>
        <section class="pt-5 pb-0 pb-lg-5">
            <div class="container">
                <div class="row g-4 g-md-5">
                    <div class="col-lg-12">
                        <h4 style="text-align: center" >قوانین و مقررات لوپ</h4>

                        @foreach ($terms as $term)
                            <div class="row mt-3">
                                <div class="col-lg-12" style="text-align: justify">
                                    <h4 style="text-align: right">{{ $term->title }}</h4>
                                    {!! $term->description !!}
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </section>
    </main>
@endsection
