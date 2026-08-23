<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ProfileUpdateService;
use App\DTOs\UpdateProfileDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserDashboardController extends Controller
{
    public function __construct(
        protected ProfileUpdateService $profileUpdateService
    ) {}

    /**
     * نمایش داشبورد کاربر
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // آمار کاربر (در صورت نیاز می‌تونیم از API اپ استفاده کنیم)
        $stats = [
            'total_orders' => 0, // تعداد سفارشات
            'active_services' => 0, // خدمات فعال
            'completed_services' => 0, // خدمات تکمیل شده
        ];
        
        return view('user.dashboard', compact('user', 'stats'));
    }

    /**
     * نمایش فرم ویرایش پروفایل
     */
    public function editProfile()
    {
        $user = Auth::user();
        return view('user.edit-profile', compact('user'));
    }

    /**
     * بروزرسانی پروفایل کاربر
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // استفاده از همان سرویس اپ
            $dto = UpdateProfileDTO::fromArray($request->validated());
            $result = $this->profileUpdateService->updateProfile($user, $dto);

            if ($result['success']) {
                return redirect()->route('web.profile.edit')
                    ->with('success', $result['message']);
            }

            return back()->withErrors(['update' => $result['message']])->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['update' => 'خطا در بروزرسانی پروفایل. لطفاً مجدداً تلاش کنید.'])->withInput();
        }
    }

    /**
     * تغییر رمز عبور با استفاده از همان validation API
     */
    public function changePassword(UpdateProfileRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // ایجاد DTO فقط با پسورد
            $dto = new UpdateProfileDTO(
                password: $request->input('password')
            );

            // استفاده از همان سرویس API
            $result = $this->profileUpdateService->updateProfile($user, $dto);

            if ($result['success']) {
                return back()->with('success', $result['message']);
            }

            return back()->withErrors(['password' => $result['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['password' => 'خطا در تغییر رمز عبور. لطفاً مجدداً تلاش کنید.']);
        }
    }

    /**
     * نمایش پروفایل کاربر (صرفاً نمایش)
     */
    public function showProfile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }
}
