<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicianBankInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_shaba_number' => ['nullable', 'string', 'regex:/^IR[0-9]{24}$/'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_card_number' => ['nullable', 'string', 'regex:/^[0-9]{16}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_shaba_number.string' => 'شماره شبا باید متن باشد.',
            'bank_shaba_number.regex' => 'فرمت شماره شبا نامعتبر است. فرمت صحیح: IR به همراه 24 رقم',
            'bank_name.string' => 'نام بانک باید متن باشد.',
            'bank_name.max' => 'نام بانک نباید بیشتر از 100 کاراکتر باشد.',
            'bank_card_number.string' => 'شماره کارت باید متن باشد.',
            'bank_card_number.regex' => 'شماره کارت باید 16 رقم باشد.',
        ];
    }
}
