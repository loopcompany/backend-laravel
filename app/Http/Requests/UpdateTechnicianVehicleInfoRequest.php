<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicianVehicleInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'car_model' => ['nullable', 'string', 'max:100'],
            'car_color' => ['nullable', 'string', 'max:50'],
            'car_plate' => ['nullable', 'string', 'max:20'],
            'car_year' => ['nullable', 'string', 'max:4'],
            'car_fuel_type' => ['nullable', 'string', 'max:50'],
            'car_vin' => ['nullable', 'string', 'max:17'], // VIN معمولاً 17 کاراکتر است
            'car_insurance_code' => ['nullable', 'string', 'max:50'],
            'car_insurance_expiry_date' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_type.string' => 'نوع وسیله نقلیه باید متن باشد.',
            'vehicle_type.max' => 'نوع وسیله نقلیه نباید بیشتر از 100 کاراکتر باشد.',
            'car_model.string' => 'مدل وسیله نقلیه باید متن باشد.',
            'car_model.max' => 'مدل وسیله نقلیه نباید بیشتر از 100 کاراکتر باشد.',
            'car_color.string' => 'رنگ وسیله نقلیه باید متن باشد.',
            'car_color.max' => 'رنگ وسیله نقلیه نباید بیشتر از 50 کاراکتر باشد.',
            'car_plate.string' => 'پلاک باید متن باشد.',
            'car_plate.max' => 'پلاک نباید بیشتر از 20 کاراکتر باشد.',
            'car_year.string' => 'سال ساخت باید متن باشد.',
            'car_year.max' => 'سال ساخت نباید بیشتر از 4 کاراکتر باشد.',
            'car_fuel_type.string' => 'نوع سوخت باید متن باشد.',
            'car_fuel_type.max' => 'نوع سوخت نباید بیشتر از 50 کاراکتر باشد.',
            'car_vin.string' => 'شماره شناسه وسیله (VIN) باید متن باشد.',
            'car_vin.max' => 'شماره شناسه وسیله (VIN) نباید بیشتر از 17 کاراکتر باشد.',
            'car_insurance_code.string' => 'کد یکتای بیمه باید متن باشد.',
            'car_insurance_code.max' => 'کد یکتای بیمه نباید بیشتر از 50 کاراکتر باشد.',
            'car_insurance_expiry_date.string' => 'تاریخ انقضاء بیمه باید متن باشد.',
            'car_insurance_expiry_date.max' => 'تاریخ انقضاء بیمه نباید بیشتر از 20 کاراکتر باشد.',
        ];
    }
}
