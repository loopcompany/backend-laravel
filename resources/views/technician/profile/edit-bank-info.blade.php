@extends('technician.layouts.dashboard')

@section('title', 'ویرایش اطلاعات بانکی')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb-main">
                <h4 class="text-capitalize breadcrumb-title">ویرایش اطلاعات بانکی</h4>
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
                    <form action="{{ route('web.technician.profile.update-bank-info') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="bank_name" class="color-dark fs-14 fw-500 align-center mb-10">نام بانک <span class="text-danger">*</span></label>
                                    <select class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name">
                                        <option value="">انتخاب کنید</option>
                                        <option value="ملی" {{ old('bank_name', $technician->bank_name) == 'ملی' ? 'selected' : '' }}>ملی</option>
                                        <option value="ملت" {{ old('bank_name', $technician->bank_name) == 'ملت' ? 'selected' : '' }}>ملت</option>
                                        <option value="صادرات" {{ old('bank_name', $technician->bank_name) == 'صادرات' ? 'selected' : '' }}>صادرات</option>
                                        <option value="تجارت" {{ old('bank_name', $technician->bank_name) == 'تجارت' ? 'selected' : '' }}>تجارت</option>
                                        <option value="سپه" {{ old('bank_name', $technician->bank_name) == 'سپه' ? 'selected' : '' }}>سپه</option>
                                        <option value="کشاورزی" {{ old('bank_name', $technician->bank_name) == 'کشاورزی' ? 'selected' : '' }}>کشاورزی</option>
                                        <option value="مسکن" {{ old('bank_name', $technician->bank_name) == 'مسکن' ? 'selected' : '' }}>مسکن</option>
                                        <option value="پست بانک" {{ old('bank_name', $technician->bank_name) == 'پست بانک' ? 'selected' : '' }}>پست بانک</option>
                                        <option value="توسعه تعاون" {{ old('bank_name', $technician->bank_name) == 'توسعه تعاون' ? 'selected' : '' }}>توسعه تعاون</option>
                                        <option value="اقتصاد نوین" {{ old('bank_name', $technician->bank_name) == 'اقتصاد نوین' ? 'selected' : '' }}>اقتصاد نوین</option>
                                        <option value="پارسیان" {{ old('bank_name', $technician->bank_name) == 'پارسیان' ? 'selected' : '' }}>پارسیان</option>
                                        <option value="پاسارگاد" {{ old('bank_name', $technician->bank_name) == 'پاسارگاد' ? 'selected' : '' }}>پاسارگاد</option>
                                        <option value="کارآفرین" {{ old('bank_name', $technician->bank_name) == 'کارآفرین' ? 'selected' : '' }}>کارآفرین</option>
                                        <option value="سامان" {{ old('bank_name', $technician->bank_name) == 'سامان' ? 'selected' : '' }}>سامان</option>
                                        <option value="سینا" {{ old('bank_name', $technician->bank_name) == 'سینا' ? 'selected' : '' }}>سینا</option>
                                        <option value="سرمایه" {{ old('bank_name', $technician->bank_name) == 'سرمایه' ? 'selected' : '' }}>سرمایه</option>
                                        <option value="شهر" {{ old('bank_name', $technician->bank_name) == 'شهر' ? 'selected' : '' }}>شهر</option>
                                        <option value="دی" {{ old('bank_name', $technician->bank_name) == 'دی' ? 'selected' : '' }}>دی</option>
                                        <option value="صنعت و معدن" {{ old('bank_name', $technician->bank_name) == 'صنعت و معدن' ? 'selected' : '' }}>صنعت و معدن</option>
                                        <option value="رسالت" {{ old('bank_name', $technician->bank_name) == 'رسالت' ? 'selected' : '' }}>رسالت</option>
                                        <option value="انصار" {{ old('bank_name', $technician->bank_name) == 'انصار' ? 'selected' : '' }}>انصار</option>
                                        <option value="رفاه" {{ old('bank_name', $technician->bank_name) == 'رفاه' ? 'selected' : '' }}>رفاه</option>
                                        <option value="ایران زمین" {{ old('bank_name', $technician->bank_name) == 'ایران زمین' ? 'selected' : '' }}>ایران زمین</option>
                                    </select>
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="account_number" class="color-dark fs-14 fw-500 align-center mb-10">شماره حساب <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $technician->account_number) }}" placeholder="شماره حساب خود را وارد کنید">
                                    @error('account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="sheba_number" class="color-dark fs-14 fw-500 align-center mb-10">شماره شبا <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">IR</span>
                                        <input type="text" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('sheba_number') is-invalid @enderror" id="sheba_number" name="sheba_number" value="{{ old('sheba_number', $technician->sheba_number) }}" placeholder="24 رقم شماره شبا را وارد کنید" maxlength="24">
                                    </div>
                                    @error('sheba_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">شماره شبا باید 24 رقم باشد (بدون IR)</small>
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
