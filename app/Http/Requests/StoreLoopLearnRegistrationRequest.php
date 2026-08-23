<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoopLearnRegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'class' => 'required|string',
            'register_as' => 'required|string',
            'mastery_soft_level' => 'required|string',
            'mastery_hard_level' => 'required|string',
            'goal' => 'required|string',
            'name' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'marriage' => 'required|string',
            'gender' => 'required|string',
            'nationality' => 'required|string',
            'education' => 'required|string',
            'phone' => 'required|string|size:11',
            'telephone' => 'nullable|string',
            'address' => 'required|string',
            'vehicle' => 'required|string',
            'certificate' => 'required|string',
        ];
    }
}
