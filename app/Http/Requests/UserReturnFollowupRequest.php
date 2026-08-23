<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserReturnFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => [
                'required',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'توضیحات پیگیری بازگشت الزامی است.',
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات نباید بیشتر از 2000 کاراکتر باشد.',
        ];
    }
}
