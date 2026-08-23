<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserOrderDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'string',
                'in:ok,no',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'تصمیم باید مشخص شود.',
            'decision.in' => 'تصمیم باید ok یا no باشد.',
            
            'reason.string' => 'دلیل باید متن باشد.',
            'reason.max' => 'دلیل نباید بیشتر از 1000 کاراکتر باشد.',
        ];
    }
}
