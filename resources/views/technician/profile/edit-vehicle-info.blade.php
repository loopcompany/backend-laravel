@extends('technician.layouts.dashboard')

@section('title', 'ویرایش اطلاعات وسیله نقلیه')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb-main">
                <h4 class="text-capitalize breadcrumb-title">ویرایش اطلاعات وسیله نقلیه</h4>
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
                    <form action="{{ route('web.technician.profile.update-vehicle-info') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="vehicle_type" class="color-dark fs-14 fw-500 align-center mb-10">نوع وسیله نقلیه <span class="text-danger">*</span></label>
                                    <select class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('vehicle_type') is-invalid @enderror" id="vehicle_type" name="vehicle_type">
                                        <option value="">انتخاب کنید</option>
                                        <option value="motorcycle" {{ old('vehicle_type', $technician->vehicle_type) == 'motorcycle' ? 'selected' : '' }}>موتورسیکلت</option>
                                        <option value="car" {{ old('vehicle_type', $technician->vehicle_type) == 'car' ? 'selected' : '' }}>خودرو</option>
                                        <option value="van" {{ old('vehicle_type', $technician->vehicle_type) == 'van' ? 'selected' : '' }}>ون</option>
                                        <option value="truck" {{ old('vehicle_type', $technician->vehicle_type) == 'truck' ? 'selected' : '' }}>کامیون</option>
                                    </select>
                                    @error('vehicle_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="license_plate" class="color-dark fs-14 fw-500 align-center mb-10">پلاک خودرو <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('license_plate') is-invalid @enderror" id="license_plate" name="license_plate" value="{{ old('license_plate', $technician->license_plate) }}" placeholder="مثال: 12ا345-67">
                                    @error('license_plate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label for="driver_license_number" class="color-dark fs-14 fw-500 align-center mb-10">شماره گواهینامه <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control ih-medium ip-gray radius-xs b-light px-15 @error('driver_license_number') is-invalid @enderror" id="driver_license_number" name="driver_license_number" value="{{ old('driver_license_number', $technician->driver_license_number) }}" placeholder="شماره گواهینامه خود را وارد کنید">
                                    @error('driver_license_number')
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
