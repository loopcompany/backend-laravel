<?php

namespace App\Models;

use App\Support\Careers\CareerOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * پرونده‌ی استخدامی پرسنل/تکنسین/مدیر.
 *
 * طبق پیشنهاد سند در دو بخش نگهداری می‌شود:
 * - general: اطلاعاتی که مدیر مستقیم برای انجام وظایف لازم دارد،
 * - confidential: اطلاعات مالی، بانکی و توافق‌های حقوقی؛ رمزنگاری‌شده و فقط با مجوز view-employment-confidential.
 */
class EmploymentFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cooperation_request_id', 'personnel_code', 'person_type', 'full_name', 'national_code', 'mobile',
        'employment_status', 'file_status', 'general', 'confidential', 'attachments', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'general' => 'array',
        'confidential' => 'encrypted:array',
        'attachments' => 'array',
    ];

    protected $hidden = ['confidential'];

    public function cooperationRequest(): BelongsTo
    {
        return $this->belongsTo(CooperationRequest::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmploymentFileLog::class)->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /** پیش‌نویس پرونده از روی درخواست همکاری پذیرفته‌شده. */
    public static function draftFromRequest(CooperationRequest $request, ?int $adminId): self
    {
        $recruitment = $request->recruitment ?? [];
        $decision = $recruitment['decision'] ?? [];
        $hiring = $recruitment['hiring'] ?? [];

        return static::create([
            'cooperation_request_id' => $request->id,
            'personnel_code' => $hiring['employee_code'] ?? null,
            'person_type' => in_array($request->job_title, ['field_technician', 'internal_technician'], true) ? 'technician' : 'staff',
            'full_name' => $request->full_name,
            'national_code' => $request->national_code,
            'mobile' => $request->mobile,
            'employment_status' => 'awaiting_start',
            'file_status' => 'awaiting_documents',
            'general' => [
                'identity' => [
                    'marital_status' => $request->marital_status,
                    'city' => $request->city,
                ],
                'education' => [
                    'level' => $request->education_level,
                    'field' => $request->field_of_study,
                ],
                'employment' => [
                    'job_title' => $request->jobTitleLabel(),
                    'unit' => $hiring['unit'] ?? $decision['suggested_unit'] ?? null,
                    'position' => $hiring['position'] ?? $decision['suggested_position'] ?? null,
                    'direct_manager' => $hiring['direct_manager'] ?? $decision['suggested_manager'] ?? null,
                    'start_date' => $hiring['start_date'] ?? $decision['suggested_start_date'] ?? null,
                ],
                'technician' => [
                    'kind' => $request->job_title === 'field_technician' ? 'field' : ($request->job_title === 'internal_technician' ? 'internal' : null),
                    'vehicle' => $request->field_info['vehicle_type'] ?? null,
                ],
            ],
            'confidential' => [
                'financial' => [
                    'base_salary' => $decision['suggested_salary'] ?? null,
                ],
            ],
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
    }

    public function fileStatusLabel(): string
    {
        return CareerOptions::FILE_STATUS[$this->file_status] ?? $this->file_status;
    }
}
