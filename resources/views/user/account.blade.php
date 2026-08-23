@extends('layout.header')
@section('content')
<style>
jdp-container .jdp-month input option, jdp-container .jdp-month option, jdp-container .jdp-month select option, jdp-container .jdp-time input option, jdp-container .jdp-time option, jdp-container .jdp-time select option, jdp-container .jdp-year input option, jdp-container .jdp-year option, jdp-container .jdp-year select option {
    font-size: 95%;
    min-height: 1.3rem;
    outline: none;
    unicode-bidi: embed;
    padding: 0;
}
</style>
<main>

    @include('layout.user.nav')

    <section class="pt-0">
        <div class="container">
            <div class="row">

                @include('layout.user.sidebar')

                <div class="col-xl-9">

                <div class="card bg-transparent border rounded-3">
                    <div class="card-header bg-transparent border-bottom">
                        <h3 class="card-header-title mb-0 ff-vb fs-5">ویرایش پروفایل</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('user.account.update') }}" class="row g-4" enctype="multipart/form-data" >
                            @csrf

                            <div class="col-12">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ProfileAvatar" type="button">انتخاب تصویر پروفایل</button>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">نام <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="fname" value="{{ $user->fname}}" >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نام‌خانوادگی <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="lname" value="{{ $user->lname}}" >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"> تاریخ تولد<span class="text-danger">*</span> </label>
                                <div class="input-group">
                                    <input autocomplete="off" name="birth_date" type="text" value="{{ $user->birth_date }}" class="form-control" data-jdp >
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ایمیل<span class="text-danger">*</span></label>
                                <input class="form-control" type="email" name="email" value="{{ $user->email }}" placeholder="ایمیل">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">کد‌ملی <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="meli_code" value="{{ $user->meli_code}}" >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">شماره تماس</label>
                                <input type="text" class="form-control" value="{{ $user->phone }}"  readonly style="background-color: #24292d3b; " placeholder="شماره تماس">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">شماره شبا</label>
                                <input type="text" class="form-control" name="iban" value="{{ $user->iban}}" >
                            </div>

                            <div class="d-sm-flex justify-content-end">
                                <button type="submit" class="btn btn-primary mb-0">ذخیره</button>
                            </div>
                        </form>
                    </div>
                </div>

                </div>
            </div>
        </div>
    </section>
</main>


<x-modals.avatar />

@endsection
