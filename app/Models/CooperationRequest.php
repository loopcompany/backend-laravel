<?php

namespace App\Models;

use App\Support\Careers\CareerOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * درخواست «همکاری با لوپ» از سایت + فرم مدیریت گزینش و مصاحبه (ستون recruitment).
 */
class CooperationRequest extends Model
{
    use SoftDeletes;

    public const DISK = 'local';

    protected $fillable = [
        'tracking_code', 'status', 'source',
        'full_name', 'mobile', 'national_code', 'shahkar_status', 'city', 'district', 'age', 'gender',
        'marital_status', 'military_status', 'military_status_other',
        'job_title', 'job_title_other', 'cooperation_type',
        'education_level', 'education_level_other', 'field_of_study', 'work_experience', 'related_experience',
        'skills', 'interest_areas', 'interest_other', 'has_certificates', 'extra_skills',
        'field_info',
        'start_availability', 'salary_type', 'salary_amount', 'overtime', 'shift_work',
        'resume_path', 'certificates_path', 'portfolio_path', 'portfolio_link',
        'confirmed_at', 'ip', 'user_agent', 'sms_sent',
        'recruitment',
    ];

    protected $casts = [
        'age' => 'integer',
        'skills' => 'array',
        'interest_areas' => 'array',
        'field_info' => 'array',
        'recruitment' => 'array',
        'has_certificates' => 'boolean',
        'sms_sent' => 'boolean',
        'salary_amount' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'PCS-' . random_int(100000, 999999);
        } while (static::withTrashed()->where('tracking_code', $code)->exists());

        return $code;
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CooperationRequestLog::class)->latest('id');
    }

    public function employmentFile(): HasOne
    {
        return $this->hasOne(EmploymentFile::class);
    }

    public function isFieldTechnician(): bool
    {
        return $this->job_title === 'field_technician';
    }

    public function statusLabel(): string
    {
        return CareerOptions::STATUSES[$this->status] ?? $this->status;
    }

    public function jobTitleLabel(): string
    {
        return $this->job_title === 'other' && $this->job_title_other
            ? 'سایر: ' . $this->job_title_other
            : (CareerOptions::JOB_TITLES[$this->job_title] ?? $this->job_title);
    }

    /** تغییر وضعیت + ثبت در تاریخچه‌ی گزینش. */
    public function changeStatus(string $status, ?int $adminId, ?string $note = null, string $action = 'status_changed'): void
    {
        $from = $this->status;

        if ($from === $status && $note === null) {
            return;
        }

        $this->update(['status' => $status]);

        $this->logs()->create([
            'admin_id' => $adminId,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $status,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
