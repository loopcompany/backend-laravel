<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;
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
                    'edited_organization' => $edited_organization
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
     * به‌روزرسانی اطلاعات سازمان
     */
    public function updateOrganization(int $userId, array $data): array
    {
        $newImagePath = null;
        $oldPendingImagePath = null;

        DB::beginTransaction();

        try {
            $organization = $this->organizationRepository->findByUserId($userId);

            if (!$organization) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'message' => 'اطلاعات سازمان یافت نشد.',
                ];
            }

            $user = $organization->user;

            if (!$user) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'message' => 'کاربر مربوط به سازمان یافت نشد.',
                ];
            }

            $editRequest = \App\Models\EditRequest::query()
                ->where('user_id', $user->id)
                ->where('organization_id', $organization->id)
                ->where('status', \App\Models\EditRequest::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            $currentRegionId = $user->region_id;
            $currentRegionCode = $user->region;

            if (!$currentRegionId && filled($currentRegionCode)) {
                $currentRegion = \App\Models\Region::query()
                    ->where('code', $currentRegionCode)
                    ->first();

                if ($currentRegion) {
                    $currentRegionId = $currentRegion->id;
                    $currentRegionCode = $currentRegion->code;
                }
            }

            if (!$editRequest && !$currentRegionId && !array_key_exists('region', $data)) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'message' => 'منطقه فعلی کاربر مشخص نیست. لطفاً کد منطقه را ارسال کنید.',
                ];
            }

            /*
             * هنگام ساخت اولین درخواست، اطلاعات فعلی کاربر و سازمان
             * به‌عنوان مقدار پایه داخل edit_requests ذخیره می‌شود.
             */
            $requestData = $editRequest ? [] : [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'profile_image' => $user->profile_photo_path,
                'organization_name' => $organization->organization_name,
                'business_name' => $organization->business_name,
                'manager_full_name' => $organization->manager_full_name,
                'birth_date' => $user->birth_date,
                'agent_name' => $organization->agent_name,
                'agent_phone' => $organization->agent_phone,
                'history' => $organization->history,
                'email' => $user->email,
                'organization_phone' => $organization->organization_phone,
                'region' => $currentRegionCode,
                'region_id' => $currentRegionId,
                'organization_address' => $organization->organization_address,
                'postal_code' => $user->postal_code,
                'status' => \App\Models\EditRequest::STATUS_PENDING,
            ];

            $fieldMap = [
                'organization_name' => 'organization_name',
                'business_name' => 'business_name',
                'manager_full_name' => 'manager_full_name',
                'agent_name' => 'agent_name',
                'agent_phone' => 'agent_phone',
                'history' => 'history',
                'organization_email' => 'email',
                'organization_phone' => 'organization_phone',
                'organization_address' => 'organization_address',
                'postal_code' => 'postal_code',
            ];

            foreach ($fieldMap as $inputField => $editRequestField) {
                if (array_key_exists($inputField, $data)) {
                    $requestData[$editRequestField] = $data[$inputField];
                }
            }

            /*
             * کاربر فقط کد region را ارسال می‌کند.
             * region_id از روی regions.code پیدا می‌شود و از ورودی گرفته نمی‌شود.
             */
            if (array_key_exists('region', $data)) {
                $regionCode = filter_var($data['region'], FILTER_VALIDATE_INT);

                if ($regionCode === false || $regionCode < 1 || $regionCode > 22) {
                    DB::rollBack();

                    return [
                        'status' => 'error',
                        'message' => 'کد منطقه باید عددی بین 1 تا 22 باشد.',
                    ];
                }

                $region = \App\Models\Region::query()
                    ->where('code', $regionCode)
                    ->first();

                if (!$region) {
                    DB::rollBack();

                    return [
                        'status' => 'error',
                        'message' => 'منطقه‌ای با این کد پیدا نشد.',
                    ];
                }

                $requestData['region'] = (string) $region->code;
                $requestData['region_id'] = $region->id;
            }

            if (array_key_exists('manager_birthdate', $data)) {
                if (blank($data['manager_birthdate'])) {
                    $requestData['birth_date'] = null;
                } else {
                    try {
                        $birthDate = str_replace('-', '/', trim((string) $data['manager_birthdate']));
                        $requestData['birth_date'] = $birthDate;
                    } catch (\Throwable $exception) {
                        DB::rollBack();

                        return [
                            'status' => 'error',
                            'message' => 'فرمت تاریخ تولد معتبر نیست.',
                        ];
                    }
                }
            }

            if (
                isset($data['profile_image'])
                && $data['profile_image'] instanceof \Illuminate\Http\UploadedFile
            ) {
                $newImagePath = $data['profile_image']->store(
                    'edit-requests/organization-profile-images',
                    'public'
                );

                $requestData['profile_image'] = $newImagePath;

                if (
                    $editRequest
                    && filled($editRequest->profile_image)
                    && str_starts_with(
                        $editRequest->profile_image,
                        'edit-requests/organization-profile-images/'
                    )
                    && $editRequest->profile_image !== $newImagePath
                ) {
                    $oldPendingImagePath = $editRequest->profile_image;
                }
            }

            if ($editRequest) {
                $editRequest->fill($requestData);
                $editRequest->status = \App\Models\EditRequest::STATUS_PENDING;
                $editRequest->save();

                $message = 'درخواست ویرایش در انتظار بررسی به‌روزرسانی شد.';
            } else {
                $editRequest = \App\Models\EditRequest::query()->create($requestData);
                $message = 'درخواست ویرایش ثبت شد و در انتظار بررسی ادمین است.';
            }

            DB::commit();

            if (
                $oldPendingImagePath
                && Storage::disk('public')->exists($oldPendingImagePath)
            ) {
                Storage::disk('public')->delete($oldPendingImagePath);
            }

            Log::info('Organization edit request saved.', [
                'edit_request_id' => $editRequest->id,
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'status' => $editRequest->status,
            ]);

            $result = $this->getOrganizationByUserId($userId);
            $result['message'] = $message;

            return $result;
        } catch (\Throwable $exception) {
            DB::rollBack();

            if (
                $newImagePath
                && Storage::disk('public')->exists($newImagePath)
            ) {
                Storage::disk('public')->delete($newImagePath);
            }

            Log::error('Error saving organization edit request.', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'خطا در ثبت درخواست ویرایش اطلاعات سازمان: ' . $exception->getMessage(),
            ];
        }
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