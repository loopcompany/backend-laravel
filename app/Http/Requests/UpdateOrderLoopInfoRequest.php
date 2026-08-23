<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderLoopInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duration' => [
                'nullable',
                'integer',
                'min:1',
                'max:365', // حداکثر یک سال (365 روز کاری)
            ],
            'loop_cost_estimate' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'loop_description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'duration.integer' => 'مدت زمان باید عدد باشد.',
            'duration.min' => 'مدت زمان باید حداقل 1 روز کاری باشد.',
            'duration.max' => 'مدت زمان نباید بیشتر از 365 روز کاری باشد.',
            
            'loop_cost_estimate.numeric' => 'هزینه تقریبی باید عدد باشد.',
            'loop_cost_estimate.min' => 'هزینه تقریبی نمی‌تواند منفی باشد.',
            
            'loop_description.string' => 'توضیحات باید متن باشد.',
            'loop_description.max' => 'توضیحات نباید بیشتر از 2000 کاراکتر باشد.',
        ];
    }
}
