<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncreaseWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:10000', 'max:50000000'],
            'linking_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'مبلغ الزامی است.',
            'amount.numeric' => 'مبلغ باید عدد باشد.',
            'amount.min' => 'حداقل مبلغ شارژ ۱۰,۰۰۰ تومان است.',
            'amount.max' => 'حداکثر مبلغ شارژ ۵۰,۰۰۰,۰۰۰ تومان است.',
            'linking_url.url' => 'آدرس لینک معتبر نیست.',
            'linking_url.max' => 'آدرس لینک نباید بیشتر از ۵۰۰ کاراکتر باشد.',
        ];
    }
}
