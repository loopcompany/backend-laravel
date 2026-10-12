<?php

namespace App\Http\Controllers;

use App\DTOs\UpdateProfileDTO;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ProfileUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileUpdateService $profileUpdateService
    ) {}

    /**
     * Get user profile information
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر یافت نشد.',
                ], 401);
            }

            $result = $this->profileUpdateService->getProfile($user);
            
            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('Profile retrieval failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات پروفایل.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر یافت نشد.',
                ], 401);
            }

            // Check if user has access
            if (!$user->hasAccess()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی شما محدود شده است.',
                ], 403);
            }

            // دریافت داده‌های validated
            $data = $request->validated();
            
            // اگر فایل تصویر آپلود شده، آن را به داده‌ها اضافه کن
            if ($request->hasFile('profile_photo_path')) {
                $data['profile_photo_path'] = $request->file('profile_photo_path');
            }
            
            // حذف عکس پروفایل (سند اپ کاربر، بخش ۱۲): { remove_profile_photo: 1 } کنار بقیه‌ی فیلدها
            $removedPhoto = false;
            if ($request->boolean('remove_profile_photo') && !$request->hasFile('profile_photo_path')) {
                $oldPhoto = $user->profile_photo_path;
                $user->forceFill(['profile_photo_path' => null])->save();
                if ($oldPhoto && !str_starts_with($oldPhoto, 'http')) {
                    Storage::disk('public')->delete($oldPhoto);
                }
                unset($data['profile_photo_path']);
                $removedPhoto = true;
            }
            unset($data['remove_profile_photo']);

            // Create DTO from validated data
            $dto = UpdateProfileDTO::fromArray($data);

            if ($removedPhoto && !$dto->hasChanges()) {
                return response()->json([
                    'success' => true,
                    'message' => 'عکس پروفایل حذف شد.',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'profile_photo_path' => null,
                    ],
                ]);
            }

            // Update profile
            $result = $this->profileUpdateService->updateProfile($user, $dto);

            // Determine response status code
            $statusCode = $result['success'] ? 200 : 400;
            
            if (isset($result['requires_verification']) && $result['requires_verification']) {
                $statusCode = 202; // Accepted - requires verification
            }

            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => $request->user()?->id,
                'request_data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی پروفایل.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر یافت نشد.',
                ], 401);
            }

            // Check if user has access
            if (!$user->hasAccess()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی شما محدود شده است.',
                ], 403);
            }

            // Ensure password is provided
            if (!$request->has('password')) {
                return response()->json([
                    'success' => false,
                    'message' => 'رمز عبور جدید الزامی است.',
                ], 400);
            }

            // Create DTO with only password
            $dto = new UpdateProfileDTO(
                password: $request->input('password')
            );

            // Update profile (only password)
            $result = $this->profileUpdateService->updateProfile($user, $dto);

            $statusCode = $result['success'] ? 200 : 400;

            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در تغییر رمز عبور.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}