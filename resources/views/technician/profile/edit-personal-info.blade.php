@extends('technician.layouts.dashboard')

@section('title', 'ویرایش اطلاعات شخصی')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb-main">
                <h4 class="text-capitalize breadcrumb-title">ویرایش اطلاعات شخصی</h4>
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
                    <form action="{{ route('web.technician.profile.update-personal-info') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="full_name" class="color-dark fs-14 fw-500 align-center mb-10">نام و نام خانوادگی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('full_name') is-invalid @enderror" id="full_name" name="full_name" value="{{ old('full_name', $technician->full_name) }}" placeholder="نام و نام خانوادگی خود را وارد کنید">
                                    @error('full_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="phone" class="color-dark fs-14 fw-500 align-center mb-10">شماره تلفن</label>
                                    <input type="text" class="form-control ih-medium ip-gray radius-xs b-light px-15" id="phone" value="{{ $technician->phone }}" disabled>
                                    <small class="form-text text-muted">شماره تلفن قابل تغییر نیست</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="national_code" class="color-dark fs-14 fw-500 align-center mb-10">کد ملی</label>
                                    <input type="text" class="form-control ih-medium ip-gray radius-xs b-light px-15" id="national_code" value="{{ $technician->national_code }}" disabled>
                                    <small class="form-text text-muted">کد ملی قابل تغییر نیست</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="email" class="color-dark fs-14 fw-500 align-center mb-10">ایمیل</label>
                                    <input type="email" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $technician->email) }}" placeholder="ایمیل خود را وارد کنید">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-25">
                                <div class="form-group">
                                    <label for="address" class="color-dark fs-14 fw-500 align-center mb-10">آدرس</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="آدرس خود را وارد کنید">{{ old('address', $technician->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-default btn-squared">ذخیره تغییرات</button>
                                <a href="{{ route('web.technician.profile') }}" class="btn btn-light btn-default btn-squared">انصراف</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
