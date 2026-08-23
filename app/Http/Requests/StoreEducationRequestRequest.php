<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationRequestRequest extends FormRequest
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
            'section' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'section.required' => 'بخش/موضوع درخواست الزامی است.',
            'section.string' => 'بخش/موضوع باید متن باشد.',
            'section.max' => 'بخش/موضوع نباید بیشتر از 255 کاراکتر باشد.',
            
            'description.required' => 'توضیحات درخواست الزامی است.',
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات نباید بیشتر از 5000 کاراکتر باشد.',
        ];
    }
}
