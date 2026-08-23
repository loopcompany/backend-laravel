@extends('layout.header')
@section('content')

<main>

    @include('layout.user.nav')

    <section class="pt-0">
        <div class="container">
            <div class="row">

                @include('layout.user.sidebar')


                <div class="col-xl-9">
                    <div class="row g-4">

                        <div class="col-sm-6 col-lg-4">
                            <div
                                class="d-flex justify-content-center align-items-center p-4 bg-warning bg-opacity-15 rounded-3">
                                <span class="display-6 text-warning mb-0"><i class="fas fa-redo fa-fw"></i></span>
                                <div class="ms-4">
                                    <div class="d-flex">
                                        <h5 class="purecounter mb-0 fw-bold" data-purecounter-start="0" data-purecounter-end="{{count(Auth::user()->orders->where('status', 1))}}" data-purecounter-delay="200">0</h5>
                                    </div>
                                    <span class="mb-0 h6 fw-light">سفارشات جاری</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-4">
                            <div
                                class="d-flex justify-content-center align-items-center p-4 bg-purple bg-opacity-10 rounded-3">
                                <span class="display-6 text-success mb-0"><i
                                        class="far fa-check-square fa-fw"></i></span>
                                <div class="ms-4">
                                    <div class="d-flex">
                                        <h5 class="purecounter mb-0 fw-bold" data-purecounter-start="0" data-purecounter-end="{{count(Auth::user()->orders->where('status', 2))}}" data-purecounter-delay="200">0</h5>
                                    </div>
                                    <span class="mb-0 h6 fw-light">سفارشات تکمیل</span>
                                </div>
                            </div>
                        </div>


                        <div class="col-sm-6 col-lg-4">
                            <div
                                class="d-flex justify-content-center align-items-center p-4 bg-primary bg-opacity-10 rounded-3">
                                <span class="display-6 mb-0" style="color:#991d9c"><i class="fas fa-gem fa-fw"></i></span>
                                <div class="ms-4">
                                    <div class="d-flex">
                                        <h5 class="purecounter mb-0 fw-bold" data-purecounter-start="0" data-purecounter-end="{{Auth::user()->getGemsAttribute()}}" data-purecounter-delay="300">0</h5>
                                    </div>
                                    <span class="mb-0 h6 fw-light">پا</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card border bg-transparent rounded-3 mt-5">

                                <div class="card-header bg-transparent border-bottom">
                                    <div class="d-sm-flex justify-content-sm-between align-items-center">
                                        <h3 class="mb-2 mb-sm-0 fs-5 ff-vb">سفارشات اخیر شما</h3>
                                    </div>
                                </div>

                                <div class="card-body">

                                    @if(Auth::user()->orders()->orderByDesc('id')->take(3)->count() < 1)
                                        سفارشی یافت نشد.
                                    @endif
                                    @foreach(Auth::user()->orders()->orderByDesc('id')->take(3)->get() as $order)
                                        {{-- <x-user.order :order="$order" /> --}}
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
