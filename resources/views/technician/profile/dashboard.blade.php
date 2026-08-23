@extends('technician.layouts.dashboard')

@section('title', 'داشبورد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb-main">
                <h4 class="text-capitalize breadcrumb-title">داشبورد</h4>
                <div class="breadcrumb-action justify-content-center flex-wrap">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active" aria-current="page">داشبورد</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    
    <!-- خوش آمدگویی -->
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-success">
                <strong>خوش آمدید {{ Auth::guard('technician')->user()->full_name }}!</strong>
                <p class="mb-0">شما با موفقیت وارد حساب کاربری خود شدید.</p>
            </div>
        </div>
    </div>
    
    <!-- کارت‌های آماری -->
    <div class="row">
        <div class="col-xxl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
                <div>
                    <div class="overview-content">
                        <h1>0</h1>
                        <p>سفارش‌های جدید</p>
                    </div>
                </div>
                <div class="ap-po-details-icon">
                    <i class="uil uil-shopping-cart-alt"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xxl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
                <div>
                    <div class="overview-content">
                        <h1>0</h1>
                        <p>سفارش‌های در حال انجام</p>
                    </div>
                </div>
                <div class="ap-po-details-icon">
                    <i class="uil uil-process"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xxl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
                <div>
                    <div class="overview-content">
                        <h1>0</h1>
                        <p>سفارش‌های تکمیل شده</p>
                    </div>
                </div>
                <div class="ap-po-details-icon">
                    <i class="uil uil-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xxl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
                <div>
                    <div class="overview-content">
                        <h1>0 تومان</h1>
                        <p>درآمد کل</p>
                    </div>
                </div>
                <div class="ap-po-details-icon">
                    <i class="uil uil-wallet"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- اطلاعات پروفایل -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-default card-md mb-4">
                <div class="card-header">
                    <h6>اطلاعات شخصی</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <strong>نام و نام خانوادگی:</strong>
                            <span class="float-end">{{ Auth::guard('technician')->user()->full_name }}</span>
                        </li>
                        <li class="mb-3">
                            <strong>شماره تلفن:</strong>
                            <span class="float-end">{{ Auth::guard('technician')->user()->phone }}</span>
                        </li>
                        <li class="mb-3">
                            <strong>کد ملی:</strong>
                            <span class="float-end">{{ Auth::guard('technician')->user()->national_code }}</span>
                        </li>
                        <li>
                            <strong>ایمیل:</strong>
                            <span class="float-end">{{ Auth::guard('technician')->user()->email ?? 'ثبت نشده' }}</span>
                        </li>
                    </ul>
                    <div class="mt-4">
                        <a href="{{ route('web.technician.profile') }}" class="btn btn-primary btn-sm">مشاهده پروفایل کامل</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card card-default card-md mb-4">
                <div class="card-header">
                    <h6>دسترسی سریع</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('web.technician.orders') }}" class="btn btn-outline-primary">مشاهده سفارش‌ها</a>
                        <a href="{{ route('web.technician.profile.edit-personal-info') }}" class="btn btn-outline-primary">ویرایش اطلاعات شخصی</a>
                        <a href="{{ route('web.technician.profile.edit-vehicle-info') }}" class="btn btn-outline-primary">ویرایش اطلاعات وسیله نقلیه</a>
                        <a href="{{ route('web.technician.profile.edit-bank-info') }}" class="btn btn-outline-primary">ویرایش اطلاعات بانکی</a>
                        <a href="{{ route('web.technician.profile.edit-password') }}" class="btn btn-outline-warning">تغییر رمز عبور</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

