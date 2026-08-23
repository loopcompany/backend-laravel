<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTechnicianOrderReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => [
                'required',
                'integer',
                'exists:technician_order_reports,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'report_id.required' => __("Report ID is required."),
            'report_id.integer' => __("The report ID must be a number."),
            'report_id.exists' => __("The desired report was not found."),
        ];
    }
}
