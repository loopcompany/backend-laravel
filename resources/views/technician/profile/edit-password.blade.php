@extends('technician.layouts.dashboard')

@section('title', 'تغییر رمز عبور')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb-main">
                <h4 class="text-capitalize breadcrumb-title">تغییر رمز عبور</h4>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-Vertical card-default card-md mb-4">
                <div class="card-header">
                    <h6>تغییر رمز عبور</h6>
                </div>
                <div class="card-body py-md-30">
                    <form action="{{ route('web.technician.profile.update-password') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="current_password" class="color-dark fs-14 fw-500 align-center mb-10">رمز عبور فعلی <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('current_password') is-invalid @enderror" id="current_password" name="current_password" placeholder="رمز عبور فعلی خود را وارد کنید">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-12"></div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="new_password" class="color-dark fs-14 fw-500 align-center mb-10">رمز عبور جدید <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('new_password') is-invalid @enderror" id="new_password" name="new_password" placeholder="رمز عبور جدید خود را وارد کنید">
                                    @error('new_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">رمز عبور باید حداقل 8 کاراکتر باشد</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="new_password_confirmation" class="color-dark fs-14 fw-500 align-center mb-10">تکرار رمز عبور جدید <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control ih-medium ip-gray radius-xs b-light px-15" id="new_password_confirmation" name="new_password_confirmation" placeholder="رمز عبور جدید را مجدداً وارد کنید">
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <strong>نکات امنیتی:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>از رمز عبور قوی استفاده کنید (ترکیب حروف، اعداد و علائم)</li>
                                        <li>رمز عبور خود را با دیگران به اشتراک نگذارید</li>
                                        <li>به صورت دوره‌ای رمز عبور خود را تغییر دهید</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-default btn-squared">تغییر رمز عبور</button>
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
