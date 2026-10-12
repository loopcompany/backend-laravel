<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * «درخواست ویرایش» فیلدهای قفل حساب سازمانی/شرکتی با نامه‌ی رسمی مهر و امضادار.
 * pending → approved (فیلدها یک بار باز) → used (پس از ذخیره دوباره قفل) | rejected
 */
class OrganizationEditRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_USED = 'used';

    public const STATUSES = [
        self::STATUS_PENDING => 'در انتظار بررسی',
        self::STATUS_APPROVED => 'تأیید شده (فیلدها باز)',
        self::STATUS_REJECTED => 'رد شده',
        self::STATUS_USED => 'ویرایش انجام شد',
    ];

    /** فیلدهای قفل؛ فقط پس از تأیید درخواست ویرایش و فقط یک بار. */
    public const LOCKED_FIELDS = [
        'organization_name' => 'نام سازمان / شرکت',
        'manager_mobile' => 'موبایل ثبت‌شده‌ی حساب',
        'manager_national_code' => 'کد ملی مدیرعامل',
        'manager_full_name' => 'نام مدیرعامل',
        'business_name' => 'نام تجاری',
        'melicode' => 'شناسه ملی سازمان',
        'registration_number' => 'شماره ثبت',
        'economic_code' => 'شماره اقتصادی',
        'manager_birthdate' => 'تاریخ تولد مدیرعامل',
    ];

    /** مدت اعتبار لینک امضاشده‌ی نامه */
    public const URL_TTL_MINUTES = 30;

    protected $fillable = [
        'organization_id',
        'user_id',
        'status',
        'description',
        'letter_path',
        'fields',
        'unlocked_fields',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'used_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'unlocked_fields' => 'array',
        'reviewed_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /** فیلدهای قابل ویرایش این درخواست تأییدشده (null در unlocked_fields یعنی همه‌ی فیلدهای قفل). */
    public function editableFields(): array
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return [];
        }

        return $this->unlocked_fields ?: array_keys(self::LOCKED_FIELDS);
    }

    public function letterUrl(): string
    {
        return url(URL::temporarySignedRoute(
            'api.organization.edit-requests.letter',
            now()->addMinutes(self::URL_TTL_MINUTES),
            ['editRequest' => $this->id],
            absolute: false
        ));
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'description' => $this->description,
            'letter_url' => $this->letterUrl(),
            'fields' => $this->fields ?? [],
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }
}
