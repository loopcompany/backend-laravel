<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'mobile' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'region' => 'required|numeric|between:1,22',
            'unit' => 'required|numeric',
            'number' => 'required|numeric',
            'floor' => 'required|numeric',
            'region_id' => 'nullable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان آدرس الزامی است.',
            'title.max' => 'عنوان آدرس نباید بیش از 255 کاراکتر باشد.',
            'fname.required' => 'نام الزامی است.',
            'fname.max' => 'نام نباید بیش از 255 کاراکتر باشد.',
            'lname.required' => 'نام خانوادگی الزامی است.',
            'lname.max' => 'نام خانوادگی نباید بیش از 255 کاراکتر باشد.',
            'telephone.max' => 'تلفن ثابت نباید بیش از 20 کاراکتر باشد.',
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.max' => 'شماره موبایل نباید بیش از 20 کاراکتر باشد.',
            'city.required' => 'شهر الزامی است.',
            'city.max' => 'شهر نباید بیش از 255 کاراکتر باشد.',
            'region.required' => 'منطقه الزامی است.',
            'region.max' => 'منطقه نباید بیش از 255 کاراکتر باشد.',
            'region.numeric' => 'منطقه باید به عدد انگلیسی وارد شود',
            'latitude.numeric' => 'عرض جغرافیایی باید عدد باشد.',
            'latitude.between' => 'عرض جغرافیایی باید بین -90 تا 90 باشد.',
            'longitude.numeric' => 'طول جغرافیایی باید عدد باشد.',
            'longitude.between' => 'طول جغرافیایی باید بین -180 تا 180 باشد.',
            'address.required' => 'آدرس الزامی است.',
            'address.max' => 'آدرس نباید بیش از 1000 کاراکتر باشد.',
        ];
    }
}