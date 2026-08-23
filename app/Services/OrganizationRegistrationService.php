<?php

namespace App\Services;

use App\DTOs\OrganizationRegistrationDTO;
use App\Models\Region;
use App\Models\User;
use App\Models\Organization;
use App\Repositories\UserRepository;
use App\Repositories\OrganizationRepository;
use Exception;
use Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class OrganizationRegistrationService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected OrganizationRepository $organizationRepository,
        protected SmsService $smsService
    ) {
    }

    /**
     * ثبت‌نام سازمان جدید و ارسال کد تایید
     */
    public function register(OrganizationRegistrationDTO $dto): array
    {
        try {
            return DB::transaction(function () use ($dto) {
                // بررسی وجود کاربر با این شماره موبایل
                $existingUser = $this->userRepository->findByPhone($dto->manager_mobile);

                $user = null;
                $isUpdate = false;

                if ($existingUser && !$existingUser->phone_verified_at) {
                    // کاربر وجود دارد اما تایید نشده - به‌روزرسانی
                    $isUpdate = true;
                    $user = $this->updateExistingUser($existingUser, $dto);

                    Log::info('Updated existing unverified organization user', [
                        'user_id' => $existingUser->id,
                        'phone' => $dto->manager_mobile,
                    ]);

                } elseif ($existingUser && $existingUser->phone_verified_at) {
                    // کاربر تایید شده وجود دارد
                    return [
                        'success' => false,
                        'message' => 'این شماره موبایل قبلاً ثبت و تایید شده است.',
                        'error' => 'phone_already_verified',
                        'user_id' => $existingUser->id,
                    ];

                } else {
                    // کاربر جدید - ایجاد
                    $isUpdate = false;
                    $user = $this->createNewUser($dto);

                    Log::info('Created new organization user', [
                        'user_id' => $user->id,
                        'phone' => $dto->manager_mobile,
                        'organization_name' => $dto->organization_name,
                    ]);
                }

                if (!$user) {
                    throw new Exception('Failed to create or update user');
                }
                $start_with = 7000000;
                if ($user->account_type != 'company') {
                    $start_with = 9000000;
                }
                $region = Region::find($dto->region_id);
                $code = $this->userRepository->createUniquCodeForUsers(21, $region?->code, $user, $start_with);
                $user->code = $code;
                $user->save();

                // ایجاد یا به‌روزرسانی Organization
                $organization = $this->createOrUpdateOrganization($user->id, $dto, $isUpdate);

                if (!$organization) {
                    throw new Exception('Failed to create or update organization');
                }

                // تولید و ارسال کد تایید
                $verificationCode = $this->smsService->generateVerificationCode();
                $hashedCode = $this->smsService->hashVerificationCode($verificationCode);

                Log::info('Generated verification code for organization', [
                    'user_id' => $user->id,
                    'phone' => $dto->manager_mobile,
                    'organization_code' => $organization->organization_code,
                ]);

                // به‌روزرسانی کد تایید
                $this->userRepository->updateVerificationCode($dto->manager_mobile, $hashedCode);
                // ارسال SMS
                $smsSent = $this->smsService->sendVerificationCode($dto->manager_mobile, $verificationCode, $dto->hashApp);

                if (!$smsSent) {
                    Log::error('SMS send failed for organization', [
                        'user_id' => $user->id,
                        'phone' => $dto->manager_mobile,
                    ]);
                    throw new Exception('Failed to send verification SMS');
                }

                $actionLog = $isUpdate ? 'Organization updated and verification resent' : 'Organization registered successfully';
                Log::info($actionLog, [
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                    'organization_code' => $organization->organization_code,
                    'organization_name' => $organization->organization_name,
                ]);

                return [
                    'success' => true,
                    'message' => $isUpdate
                        ? 'اطلاعات سازمان به‌روزرسانی شد. کد تایید مجدداً ارسال شد.'
                        : 'ثبت‌نام سازمان با موفقیت انجام شد. کد تایید به شماره موبایل ارسال شد.',
                    'data' => [
                        'user_id' => $user->id,
                        'organization_id' => $organization->id,
                        'organization_code' => $organization->organization_code,
                        'phone' => $dto->manager_mobile,
                        'email' => $dto->organization_email,
                        'is_update' => $isUpdate,
                    ],
                ];
            });
        } catch (Exception $e) {
            Log::error('Organization registration failed: ' . $e->getMessage(), [
                'phone' => $dto->manager_mobile,
                'organization_name' => $dto->organization_name,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت‌نام سازمان. لطفاً دوباره تلاش کنید.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ایجاد کاربر جدید برای سازمان
     */
    private function createNewUser(OrganizationRegistrationDTO $dto): User
    {
        \Log::info($dto->password);
        // دریافت نام city از city_id
        $cityName = $dto->city;
        if ($dto->city_id && !$cityName) {
            $city = \App\Models\City::find($dto->city_id);
            if ($city) {
                $cityName = $city->title;
            }
        }

        // دریافت نام region از region_id
        $regionName = $dto->region;
        if ($dto->region_id && !$regionName) {
            $region = \App\Models\Region::find($dto->region_id);
            if ($region) {
                $regionName = $region->title;
            }
        }

        $userData = [
            'phone' => $dto->manager_mobile,
            'email' => $dto->organization_email,
            'password' => bcrypt($dto->password),
            'birth_date' => gregorian_to_jalali($dto->manager_birthdate), // تبدیل میلادی به شمسی
            'city' => $cityName,
            'region' => $regionName,
            'melicode' => $dto->melicode,
            'postal_code' => $dto->postal_code,
            'province_id' => $dto->province_id,
            'city_id' => $dto->city_id,
            'region_id' => $dto->region_id,
            'account_type' => $dto->account_type,
            'name' => explode(' ', $dto->manager_full_name)[0] ?? 'مدیر',
            'last_name' => implode(' ', array_slice(explode(' ', $dto->manager_full_name), 1)) ?? 'سازمان',
        ];

        // آپلود تصویر پروفایل (اختیاری)
        if ($dto->profile_image) {
            $userData['profile_photo_path'] = $this->uploadProfileImage($dto->profile_image);
        }

        return $this->userRepository->create($userData);
    }

    /**
     * به‌روزرسانی کاربر موجود
     */
    private function updateExistingUser(User $existingUser, OrganizationRegistrationDTO $dto): User
    {
        \Log::info($dto->password);
        // دریافت نام city از city_id
        $cityName = $dto->city;
        if ($dto->city_id && !$cityName) {
            $city = \App\Models\City::find($dto->city_id);
            if ($city) {
                $cityName = $city->title;
            }
        }

        // دریافت نام region از region_id
        $regionName = $dto->region;
        if ($dto->region_id && !$regionName) {
            $region = Region::find($dto->region_id);
            if ($region) {
                $regionName = $region->title;
            }
        }

        $userData = [
            'email' => $dto->organization_email,
            'birth_date' => gregorian_to_jalali($dto->manager_birthdate), // تبدیل میلادی به شمسی
            'city' => $cityName,
            'region' => $regionName,
            'melicode' => $dto->melicode,
            'postal_code' => $dto->postal_code,
            'province_id' => $dto->province_id,
            'city_id' => $dto->city_id,
            'region_id' => $dto->region_id,
            'account_type' => $dto->account_type,
            'name' => explode(' ', $dto->manager_full_name)[0] ?? 'مدیر',
            'last_name' => implode(' ', array_slice(explode(' ', $dto->manager_full_name), 1)) ?? 'سازمان',
            'phone_verified_at' => null,
            'phone_verify_code' => null,
        ];
        if ($dto->password) {
            $userData['password'] = bcrypt($dto->password);
        }
        // آپلود تصویر جدید (اختیاری)
        if ($dto->profile_image) {
            // حذف تصویر قدیمی
            if ($existingUser->profile_photo_path) {
                Storage::disk('public')->delete($existingUser->profile_photo_path);
            }
            $userData['profile_photo_path'] = $this->uploadProfileImage($dto->profile_image);
        }

        return $this->userRepository->update($existingUser->id, $userData);
    }

    /**
     * ایجاد یا به‌روزرسانی Organization
     */
    private function createOrUpdateOrganization(int $userId, OrganizationRegistrationDTO $dto, bool $isUpdate): Organization
    {
        $user = User::findOrFail($userId);
        $start_with = 7000000;
        if ($user->account_type != 'company') {
            $start_with = 9000000;
        }
        $region = Region::find($dto->region_id);
        // تولید کد سازمانی یونیک
        $organizationCode = $this->userRepository->createUniquCodeForUsers(21, $region?->code, $user, $start_with);

        $organizationData = [
            'user_id' => $userId,
            'account_type' => $dto->account_type,
            'organization_name' => $dto->organization_name,
            'business_name' => $dto->business_name,
            'agent_name' => $dto->agent_name,
            'agent_phone' => $dto->agent_phone,
            'history' => $dto->history,
            'organization_code' => $organizationCode,
            'organization_phone' => $dto->organization_phone,
            'organization_address' => $dto->organization_address,
            'manager_full_name' => $dto->manager_full_name,
            'manager_national_code' => $dto->manager_national_code,
        ];

        if ($isUpdate) {
            // بررسی وجود Organization قبلی
            $existingOrg = $this->organizationRepository->findByUserId($userId);

            if ($existingOrg) {
                // حفظ کد سازمانی قبلی
                $organizationData['organization_code'] = $existingOrg->organization_code;
                return $this->organizationRepository->update($existingOrg->id, $organizationData);
            }
        }

        return $this->organizationRepository->create($organizationData);
    }

    /**
     * آپلود تصویر پروفایل
     */
    private function uploadProfileImage(UploadedFile $file): string
    {
        try {
            $filePath = $file->store('profiles/organizations', 'public');
            return $filePath;
        } catch (Exception $e) {
            Log::error('Profile image upload failed: ' . $e->getMessage());
            throw new Exception('خطا در آپلود تصویر پروفایل.');
        }
    }

    /**
     * ورود سازمان با کد سازمانی و رمز عبور
     */
    public function login(string $organizationCode, string $password): array
    {
        try {
            // پیدا کردن سازمان با کد سازمانی
            $organization = $this->organizationRepository->findByOrganizationCode($organizationCode);

            if (!$organization) {
                return [
                    'success' => false,
                    'message' => 'کد سازمانی یافت نشد.',
                    'error' => 'organization_not_found',
                ];
            }

            // دریافت کاربر مرتبط
            $user = $organization->user;

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'کاربر یافت نشد.',
                    'error' => 'user_not_found',
                ];
            }

            // بررسی تایید شماره موبایل
            if (!$user->phone_verified_at) {
                return [
                    'success' => false,
                    'message' => 'شماره موبایل هنوز تایید نشده است.',
                    'error' => 'phone_not_verified',
                ];
            }

            // بررسی دسترسی
            if (!$user->hasAccess()) {
                return [
                    'success' => false,
                    'message' => 'حساب کاربری شما غیرفعال است.',
                    'error' => 'account_disabled',
                ];
            }

            // بررسی رمز عبور 
            if (!Hash::check($password, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'رمز عبور اشتباه است.',
                    'error' => 'invalid_password',
                ];
            }

            // تولید توکن
            $token = $user->createToken('organization-token')->plainTextToken;

            Log::info('Organization logged in successfully', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'organization_code' => $organizationCode,
            ]);

            return [
                'success' => true,
                'message' => 'ورود با موفقیت انجام شد.',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'account_type' => $user->account_type,
                    ],
                    'organization' => [
                        'id' => $organization->id,
                        'organization_name' => $organization->organization_name,
                        'organization_code' => $organization->organization_code,
                        'manager_full_name' => $organization->manager_full_name,
                    ],
                ],
            ];
        } catch (Exception $e) {
            Log::error('Organization login failed: ' . $e->getMessage(), [
                'organization_code' => $organizationCode,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ورود. لطفاً دوباره تلاش کنید.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
