<?php

namespace App\Http\Controllers\user;

use App\DTOs\LoginDTO;
use App\DTOs\RegistrationDTO;
use App\DTOs\ForgotPasswordDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\VerifyPhoneRequest;
use App\Services\AuthService;
use App\Services\RegistrationService;
use App\Services\ForgotPasswordService;
use App\Services\PhoneVerificationService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected RegistrationService $registrationService,
        protected ForgotPasswordService $forgotPasswordService,
        protected PhoneVerificationService $verificationService,
        protected SmsService $smsService
    ) {}

    /**
     * نمایش فرم ورود
     */
 
    public function showLoginForm()
    {
        return view('auth.user.login');
    }

    /**
     * پردازش ورود کاربر
     */
    public function login(LoginRequest $request)
    {
        try {
            // استفاده از همان سرویس اپ
            $dto = LoginDTO::fromArray($request->validated());
            $result = $this->authService->login($dto);

            if ($result['success']) {
                // برای وب سایت از session استفاده می‌کنیم نه API token
                $user = \App\Models\User::where('phone', $dto->phone)->first();
                Auth::login($user);
                
                return redirect()->intended(route('web.dashboard'))->with('success', $result['message']);
            }

            // اگر نیاز به تایید شماره دارد
            if (isset($result['requires_verification']) && $result['requires_verification']) {
                return redirect()->route('web.verify-phone')
                    ->with('phone', $dto->phone)
                    ->with('error', $result['message']);
            }

            return back()->withErrors(['login' => $result['message']])->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'خطا در ورود. لطفاً مجدداً تلاش کنید.'])->withInput();
        }
    }

    /**
     * نمایش فرم ثبت‌نام
     */
    public function showRegisterForm()
    {
        return view('auth.user.register');
    }

    /**
     * پردازش ثبت‌نام کاربر
     */
    public function register(RegistrationRequest $request)
    {
        try {
            // استفاده از همان سرویس اپ
            $dto = RegistrationDTO::fromArray($request->validated());
            $result = $this->registrationService->register($dto);

            if ($result['success']) {
                return redirect()->route('web.verify-phone')
                    ->with('success', $result['message'])
                    ->with('phone', $result['phone'])
                    ->with('user_id', $result['user_id']);
            }

            return back()->withErrors(['register' => $result['message']])->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['register' => 'خطا در ثبت نام. لطفاً مجدداً تلاش کنید.'])->withInput();
        }
    }

    /**
     * خروج کاربر
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('web.login')->with('success', __("Exit was successful."));
    }

    /**
     * نمایش صفحه تایید شماره موبایل
     */
    public function showVerifyPhoneForm()
    {
        // فقط اگر phone در session موجود باشد
        if (!session('phone')) {
            return redirect()->route('web.register');
        }

        return view('auth.user.verify-phone');
    }

    /**
     * تایید شماره موبایل برای ثبت‌نام
     */
    public function verifyPhone(VerifyPhoneRequest $request)
    {
        try {
            $phone = $request->validated('phone');
            $code = $request->validated('verification_code');

            // استفاده از همان سرویس اپ برای تایید کد
            $result = $this->verificationService->verifyPhone($phone, $code);

            if ($result['success']) {
                // کاربر را وارد سیستم کن
                $user = $result['user'];
                Auth::login($user);
                
                return redirect()->route('web.dashboard')
                    ->with('success', 'شماره موبایل تایید شد. ثبت‌نام شما با موفقیت تکمیل شد!');
            }

            return back()->withErrors(['verification_code' => $result['message']])->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['verification_code' => 'خطا در تایید کد. لطفاً مجدداً تلاش کنید.'])->withInput();
        }
    }

    /**
     * ارسال مجدد کد تایید برای ثبت‌نام
     */
    public function resendVerificationCode(Request $request)
    {
        $phone = session('phone') ?? $request->input('phone');
        
        if (!$phone) {
            return redirect()->route('web.register');
        }

        try {
            // استفاده از همان منطق اپ
            $result = $this->verificationService->resendVerificationCode($phone, $this->smsService);
            
            if ($result['success']) {
                return back()->with('success', $result['message']);
            }

            return back()->withErrors(['resend' => $result['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['resend' => 'خطا در ارسال مجدد کد. لطفاً مجدداً تلاش کنید.']);
        }
    }

    /**
     * نمایش فرم فراموشی رمز عبور
     */
    public function showForgotPasswordForm()
    {
        return view('auth.user.forgot-password');
    }

    /**
     * ارسال کد بازیابی رمز عبور
     */
    public function sendResetCode(ForgotPasswordRequest $request)
    {
        try {
            $dto = ForgotPasswordDTO::fromArray($request->validated());
            $result = $this->forgotPasswordService->sendResetCode($dto);

            if ($result['success']) {
                return redirect()->route('web.verify-reset-code')
                    ->with('success', $result['message'])
                    ->with('phone', $result['phone']);
            }

            return back()->withErrors(['reset' => $result['message']])->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['reset' => 'خطا در ارسال کد بازیابی. لطفاً مجدداً تلاش کنید.'])->withInput();
        }
    }

    /**
     * نمایش فرم تایید کد بازیابی
     */
    public function showVerifyResetCodeForm()
    {
        if (!session('phone')) {
            return redirect()->route('web.forgot-password');
        }

        return view('auth.user.verify-reset-code');
    }

    /**
     * تایید کد بازیابی و ورود خودکار
     */
    public function verifyResetCode(VerifyPhoneRequest $request)
    {
        try {
            $phone = $request->validated('phone');
            $code = $request->validated('verification_code');

            // استفاده از همان سرویس اپ برای تایید کد
            $result = $this->verificationService->verifyPhone($phone, $code, true); // true for password reset

            if ($result['success']) {
                // کاربر را وارد سیستم کن
                $user = $result['user'];
                Auth::login($user);
                
                return redirect()->route('web.dashboard')
                    ->with('success', 'کد تایید شد. شما با موفقیت وارد شدید.');
            }

            return back()->withErrors(['code' => $result['message']])->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['code' => 'خطا در تایید کد. لطفاً مجدداً تلاش کنید.'])->withInput();
        }
    }

    /**
     * ارسال مجدد کد تایید برای بازیابی رمز
     */
    public function resendResetCode(Request $request)
    {
        $phone = session('phone');
        
        if (!$phone) {
            return redirect()->route('web.forgot-password');
        }

        try {
            // استفاده از همان منطق اپ
            $result = $this->verificationService->resendVerificationCode($phone, $this->smsService, true); // true for password reset
            
            if ($result['success']) {
                return back()->with('success', $result['message']);
            }

            return back()->withErrors(['resend' => $result['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['resend' => 'خطا در ارسال مجدد کد. لطفاً مجدداً تلاش کنید.']);
        }
    }
}