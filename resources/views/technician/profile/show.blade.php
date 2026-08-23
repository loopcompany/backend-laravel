@extends('technician.layouts.dashboard')

@section('title', 'پروفایل من')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb-main">
                <h4 class="text-capitalize breadcrumb-title">پروفایل من</h4>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-Vertical card-default card-md mb-4">
                <div class="card-header">
                    <h6>اطلاعات شخصی</h6>
                </div>
                <div class="card-body py-md-30">
                    <div class="row">
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label for="name" class="color-dark fs-14 fw-500 align-center mb-10">نام و نام خانوادگی</label>
                                <p class="fs-14">{{ $technician->full_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label for="phone" class="color-dark fs-14 fw-500 align-center mb-10">شماره تلفن</label>
                                <p class="fs-14">{{ $technician->phone }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label for="national_code" class="color-dark fs-14 fw-500 align-center mb-10">کد ملی</label>
                                <p class="fs-14">{{ $technician->national_code }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label for="email" class="color-dark fs-14 fw-500 align-center mb-10">ایمیل</label>
                                <p class="fs-14">{{ $technician->email ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-12 mb-25">
                            <div class="form-group">
                                <label for="address" class="color-dark fs-14 fw-500 align-center mb-10">آدرس</label>
                                <p class="fs-14">{{ $technician->address ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <a href="{{ route('web.technician.profile.edit-personal-info') }}" class="btn btn-primary btn-default btn-squared">ویرایش اطلاعات شخصی</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-Vertical card-default card-md mb-4">
                <div class="card-header">
                    <h6>اطلاعات وسیله نقلیه</h6>
                </div>
                <div class="card-body py-md-30">
                    <div class="row">
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label class="color-dark fs-14 fw-500 align-center mb-10">نوع وسیله نقلیه</label>
                                <p class="fs-14">{{ $technician->vehicle_type ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label class="color-dark fs-14 fw-500 align-center mb-10">پلاک خودرو</label>
                                <p class="fs-14">{{ $technician->license_plate ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label class="color-dark fs-14 fw-500 align-center mb-10">شماره گواهینامه</label>
                                <p class="fs-14">{{ $technician->driver_license_number ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <a href="{{ route('web.technician.profile.edit-vehicle-info') }}" class="btn btn-primary btn-default btn-squared">ویرایش اطلاعات وسیله نقلیه</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-Vertical card-default card-md mb-4">
                <div class="card-header">
                    <h6>اطلاعات بانکی</h6>
                </div>
                <div class="card-body py-md-30">
                    <div class="row">
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label class="color-dark fs-14 fw-500 align-center mb-10">نام بانک</label>
                                <p class="fs-14">{{ $technician->bank_name ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label class="color-dark fs-14 fw-500 align-center mb-10">شماره حساب</label>
                                <p class="fs-14">{{ $technician->account_number ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-25">
                            <div class="form-group">
                                <label class="color-dark fs-14 fw-500 align-center mb-10">شماره شبا</label>
                                <p class="fs-14">{{ $technician->sheba_number ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <a href="{{ route('web.technician.profile.edit-bank-info') }}" class="btn btn-primary btn-default btn-squared">ویرایش اطلاعات بانکی</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-Vertical card-default card-md mb-4">
                <div class="card-header">
                    <h6>امنیت حساب کاربری</h6>
                </div>
                <div class="card-body py-md-30">
                    <div class="row">
                        <div class="col-md-12">
                            <a href="{{ route('web.technician.profile.edit-password') }}" class="btn btn-warning btn-default btn-squared">تغییر رمز عبور</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
