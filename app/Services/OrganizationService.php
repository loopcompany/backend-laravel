<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationEditRequest;
use App\Models\Region;
use App\Models\User;
use App\Repositories\OrganizationRepository;
use App\Services\Security\AccountSecurityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;

class OrganizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository
    ) {
    }

    /**
     * دریافت اطلاعات سازمان کاربر
     */
    public function getOrganizationByUserId(int $userId): array
    {
        try {
            $organization = $this->organizationRepository->findByUserId($userId);

            if (!$organization) {
                return [
                    'status' => 'error',
                    'message' => 'اطلاعات سازمان یافت نشد.',
                ];
            }

            $edited_organization = $this->organizationRepository->findEditRequestByUserId($userId);
            $editRequest = $organization->latestEditRequest();
            $unlocked = $editRequest?->status === OrganizationEditRequest::STATUS_APPROVED;
            // دریافت اطلاعات کاربر (برای فیلدهای موجود در جدول users)
            $user = $organization->user;

            // تبدیل تاریخ تولد به شمسی
            $birthdate = $user?->birth_date;


            return [
                'status' => 'success',
                'message' => 'اطلاعات پروفایل با موفقیت بازیابی شد',
                'data' => [
                    // اطلاعات از جدول organizations
                    'organization_name' => $organization->organization_name,
                    // شناسه‌ی ملی سازمان/شرکت (همان کلید فرم ثبت‌نام)
                    'melicode' => $user?->melicode,
                    'registration_number' => $organization->registration_number,
                    'economic_code' => $organization->economic_code,
                    'verification_status' => $organization->verificationStatus(),
                    'verification_reason' => $organization->verificationReason(),
                    'agent_name' => $organization->agent_name,
                    'agent_phone' => $organization->agent_phone,
                    'business_name' => $organization->business_name,
                    'history' => $organization->history,
                    'organization_phone' => $organization->organization_phone,
                    'organization_address' => $organization->organization_address,
                    'manager_full_name' => $organization->manager_full_name,
                    'manager_national_code' => $organization->manager_national_code,
                    'profile_image' => $user->profile_photo_path,
                    // اطلاعات از جدول users
                    'organization_email' => $user?->email,
                    'manager_mobile' => $user?->phone,
                    'manager_birthdate' => $birthdate,
                    'province_id' => $user?->province_id,
                    'city_id' => $user?->city_id,
                    'region_id' => $user?->region_id,
                    'postal_code' => $user?->postal_code,
                    // درخواست ویرایش قدیمی (صف تأیید پیشین) — برای سازگاری نسخه‌های قبلی اپ
                    'edited_organization' => $edited_organization,
                    // «درخواست ویرایش» فیلدهای قفل با نامه‌ی رسمی (بخش ۱۱)
                    'edit_request' => $editRequest?->toApiArray(),
                    'locked_fields_unlocked' => $unlocked,
                    'unlocked_fields' => $unlocked && $editRequest->unlocked_fields ? array_values($editRequest->unlocked_fields) : null,
                    'locked_fields' => array_keys(OrganizationEditRequest::LOCKED_FIELDS),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطای سرور: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * به‌روزرسانی اطلاعات سازمان (سند اپ کاربر، بخش ۱۱-۳):
     * - به‌روزرسانی جزئی: فیلدی که نیامده دست نمی‌خورد.
     * - فیلدهای آزاد فوراً و بدون بررسی ادمین اعمال می‌شوند.
     * - فیلدهای قفل فقط با «درخواست ویرایش» تأییدشده و فقط یک بار؛ وگرنه 403 FIELDS_LOCKED.
     *   مقدار بدون تغییرِ فیلد قفل (فرم کامل نسخه‌های قدیمی اپ) خطا نیست و نادیده گرفته می‌شود.
     */
    public function updateOrganization(int $userId, array $data): array
    {
        $newImagePath = null;
        $oldImagePath = null;

        DB::beginTransaction();

        try {
            $organization = Organization::query()->where('user_id', $userId)->lockForUpdate()->first();
            $user = $organization?->user;

            if (!$organization || !$user) {
                DB::rollBack();

                return ['status' => 'error', 'message' => 'اطلاعات سازمان یافت نشد.', 'http' => 404];
            }

            // ----------------------------------------------------- فیلدهای قفل
            $lockedChanges = $this->changedLockedFields($organization, $user, $data);
            $editRequest = null;

            if ($lockedChanges !== []) {
                $editRequest = $organization->editRequests()
                    ->where('status', OrganizationEditRequest::STATUS_APPROVED)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                $notAllowed = array_diff(array_keys($lockedChanges), $editRequest?->editableFields() ?? []);
                if ($notAllowed !== []) {
                    DB::rollBack();

                    return [
                        'status' => 'error',
                        'success' => false,
                        'message' => 'این فیلدها قفل هستند و فقط پس از تأیید «درخواست ویرایش» قابل تغییرند: '
                            . implode('، ', array_map(fn ($f) => OrganizationEditRequest::LOCKED_FIELDS[$f], $notAllowed)),
                        'error_code' => 'FIELDS_LOCKED',
                        'locked_fields' => array_values($notAllowed),
                        'http' => 403,
                    ];
                }

                if (array_key_exists('manager_mobile', $lockedChanges)) {
                    $mobileTaken = User::query()->where('phone', $lockedChanges['manager_mobile'])->whereKeyNot($user->id)->exists();
                    if ($mobileTaken) {
                        DB::rollBack();

                        return ['status' => 'error', 'success' => false, 'message' => 'این شماره موبایل متعلق به حساب دیگری است.', 'error_code' => 'MOBILE_TAKEN', 'http' => 422];
                    }
                }
            }

            // ----------------------------------------------------- فیلدهای آزاد
            $organizationData = array_intersect_key($data, array_flip([
                'agent_name', 'agent_phone', 'history', 'organization_phone', 'organization_address',
            ]));
            $userData = array_intersect_key($data, array_flip(['postal_code', 'city', 'province_id', 'city_id']));

            if (array_key_exists('organization_email', $data)) {
                $email = $data['organization_email'] ?: null;
                if ($email && User::query()->where('email', $email)->whereKeyNot($user->id)->exists()) {
                    DB::rollBack();

                    return ['status' => 'error', 'success' => false, 'message' => 'این ایمیل قبلاً برای کاربر دیگری ثبت شده است.', 'error_code' => 'EMAIL_TAKEN', 'http' => 422];
                }
                $userData['email'] = $email;
            }

            // کاربر فقط کد منطقه را می‌فرستد؛ region_id از روی regions.code پیدا می‌شود
            if (array_key_exists('region', $data) && filled($data['region'])) {
                $regionCode = filter_var($data['region'], FILTER_VALIDATE_INT);
                $region = $regionCode !== false ? Region::query()->where('code', $regionCode)->first() : null;

                if (!$region) {
                    DB::rollBack();

                    return ['status' => 'error', 'message' => 'منطقه‌ای با این کد پیدا نشد.', 'http' => 422];
                }

                $userData['region'] = (string) $region->code;
                $userData['region_id'] = $region->id;
            } elseif (array_key_exists('region_id', $data)) {
                $userData['region_id'] = $data['region_id'];
            }

            // عکس پروفایل (آزاد): آپلود جدید یا حذف
            if (($data['profile_image'] ?? null) instanceof UploadedFile) {
                $newImagePath = $data['profile_image']->store('images/profiles', 'public');
                $oldImagePath = $user->profile_photo_path;
                $userData['profile_photo_path'] = $newImagePath;
            } elseif (!empty($data['remove_profile_image'])) {
                $oldImagePath = $user->profile_photo_path;
                $userData['profile_photo_path'] = null;
            }

            // ----------------------------------------------------- اعمال قفل‌های باز شده
            foreach ($lockedChanges as $field => $value) {
                match ($field) {
                    'manager_mobile' => $userData['phone'] = $value,
                    'melicode' => $userData['melicode'] = $value,
                    'manager_birthdate' => $userData['birth_date'] = $value,
                    default => $organizationData[$field] = $value,
                };
            }

            $organization->forceFill($organizationData)->save();
            $user->forceFill($userData)->save();

            // پس از ذخیره‌ی موفق، فیلدهای قفل دوباره قفل می‌شوند
            $editRequest?->forceFill(['status' => OrganizationEditRequest::STATUS_USED, 'used_at' => now()])->save();

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            Log::error('Error updating organization profile.', ['user_id' => $userId, 'error' => $exception->getMessage()]);

            return ['status' => 'error', 'message' => 'خطا در به‌روزرسانی اطلاعات سازمان.', 'http' => 500];
        }

        if ($oldImagePath && $oldImagePath !== $newImagePath && !str_starts_with($oldImagePath, 'http')) {
            Storage::disk('public')->delete($oldImagePath);
        }

        if (array_key_exists('manager_mobile', $lockedChanges ?? [])) {
            $logger = app(AccountSecurityLogger::class);
            $logger->activity($user, 'mobile_changed', request());
            $logger->alert($user, 'mobile_changed', request());
        }

        Log::info('Organization profile updated.', [
            'user_id' => $userId,
            'locked_fields' => array_keys($lockedChanges ?? []),
        ]);

        $result = $this->getOrganizationByUserId($userId);
        $result['message'] = 'اطلاعات حساب با موفقیت به‌روزرسانی شد.';

        return $result;
    }

    /**
     * فیلدهای قفلی که در درخواست آمده‌اند و مقدارشان واقعاً با مقدار فعلی فرق دارد.
     *
     * @return array<string, mixed>
     */
    private function changedLockedFields(Organization $organization, User $user, array $data): array
    {
        $current = [
            'organization_name' => $organization->organization_name,
            'manager_mobile' => $user->phone,
            'manager_national_code' => $organization->manager_national_code,
            'manager_full_name' => $organization->manager_full_name,
            'business_name' => $organization->business_name,
            'melicode' => $user->melicode,
            'registration_number' => $organization->registration_number,
            'economic_code' => $organization->economic_code,
            'manager_birthdate' => $user->birth_date,
        ];

        $normalize = function (string $field, $value): ?string {
            $value = trim((string) $value);
            if ($field === 'manager_birthdate') {
                $value = str_replace('-', '/', $value);
            }

            return $value === '' ? null : $value;
        };

        $changes = [];
        foreach ($current as $field => $currentValue) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $new = $normalize($field, $data[$field]);
            if ($new !== $normalize($field, $currentValue)) {
                $changes[$field] = $new;
            }
        }

        return $changes;
    }

    /**
     * دریافت وضعیت تایید و دسترسی سازمان
     */
    public function getAccessStatus(int $userId): array
    {
        try {
            $organization = $this->organizationRepository->findByUserId($userId);

            if (!$organization) {
                return [
                    'success' => false,
                    'message' => 'اطلاعات سازمان یافت نشد.',
                ];
            }

            $hasCompleteAccess = $organization->hasCompleteAccess();

            // محاسبه next_steps
            $nextSteps = $this->calculateNextSteps($organization);

            // محاسبه blocked_message
            $blockedMessage = $this->getBlockedMessage($organization);

            return [
                'success' => true,
                'data' => [
                    'profile_status' => $organization->profile_status,
                    'verification_status' => $organization->verificationStatus(),
                    'verification_reason' => $organization->verificationReason(),
                    'contract_status' => $organization->contract_status,
                    'has_complete_access' => $hasCompleteAccess,
                    'profile_approved_at' => $organization->profile_approved_at?->toIso8601String(),
                    'contract_approved_at' => $organization->contract_approved_at?->toIso8601String(),
                    'profile_rejection_reason' => $organization->profile_rejection_reason,
                    'contract_rejection_reason' => $organization->contract_rejection_reason,
                    'blocked_message' => $blockedMessage,
                    'next_steps' => $nextSteps,
                    'profile_status_label' => $organization->profile_status_label,
                    'contract_status_label' => $organization->contract_status_label,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching organization access status', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت وضعیت دسترسی: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * محاسبه مراحل بعدی که سازمان باید انجام دهد
     */
    private function calculateNextSteps($organization): array
    {
        $steps = [];

        // اگر قرارداد آپلود نشده
        if ($organization->isContractNotUploaded()) {
            $steps[] = 'upload_contract';
        }

        // اگر قرارداد رد شده
        if ($organization->isContractRejected()) {
            $steps[] = 'reupload_contract';
        }

        // اگر پروفایل رد شده
        if ($organization->isProfileRejected()) {
            $steps[] = 'update_profile';
        }

        // اگر هر کدام pending هستند
        if ($organization->isProfilePending() || $organization->isContractPending()) {
            $steps[] = 'wait_for_approval';
        }

        // اگر هر دو تایید شدند
        if ($organization->hasCompleteAccess()) {
            $steps[] = 'full_access_granted';
        }

        return $steps;
    }

    /**
     * دریافت پیام محدودیت دسترسی
     */
    private function getBlockedMessage($organization): ?string
    {
        // اگر دسترسی کامل دارد
        if ($organization->hasCompleteAccess()) {
            return null;
        }

        // اگر پروفایل رد شده
        if ($organization->isProfileRejected()) {
            return 'پروفایل شما رد شده است. لطفا اطلاعات خود را اصلاح کنید.';
        }

        // اگر قرارداد رد شده
        if ($organization->isContractRejected()) {
            return 'قرارداد شما رد شده است. لطفا مجددا قرارداد را آپلود کنید.';
        }

        // اگر قرارداد آپلود نشده
        if ($organization->isContractNotUploaded()) {
            return 'لطفا قرارداد امضا شده را آپلود کنید.';
        }

        // اگر در حال بررسی هستند
        if ($organization->isProfilePending() || $organization->isContractPending()) {
            return 'اطلاعات شما در حال بررسی توسط ادمین است. لطفا منتظر بمانید.';
        }

        return 'دسترسی محدود است.';
    }
}