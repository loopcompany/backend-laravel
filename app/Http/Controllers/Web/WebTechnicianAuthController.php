<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicianRegistrationRequest;
use App\Http\Requests\TechnicianPhoneVerificationRequest;
use App\Http\Requests\TechnicianLoginRequest;
use App\Http\Requests\TechnicianForgotPasswordRequest;
use App\Models\Expertise;
use App\Rules\TechnicianBirthYear;
use App\Services\AdminPanelNotificationService;
use Morilog\Jalali\CalendarUtils;
use App\Services\TechnicianRegistrationService;
use App\Services\LoginActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebTechnicianAuthController extends Controller
{
    public function __construct(
        protected TechnicianRegistrationService $service,
        protected LoginActivityService $loginActivityService
    ) {}

    // ========== Registration Views & Actions ==========
    
    public function showRegisterForm()
    {
        $maxBirthYear = TechnicianBirthYear::maxAllowedJalaliYear();

        return view('technician.auth.register', [
            'expertises' => Expertise::select('id', 'title')->orderBy('title')->get(),
            // آخرین روز سال مجاز (اسفند ۲۹ یا ۳۰) — همان سقفی که اپ تکنسین در تقویم تولد اعمال می‌کند
            'maxBirthDate' => sprintf('%d/12/%d', $maxBirthYear, CalendarUtils::isLeapJalaliYear($maxBirthYear) ? 30 : 29),
        ]);
    }

    public function register(TechnicianRegistrationRequest $request, AdminPanelNotificationService $adminNotifications)
    {
        // فیلدهایی که اپ تکنسین در سمت کلاینت اجباری می‌کند ولی API آن‌ها را اختیاری پذیرفته است
        $request->validate([
            'melicode' => 'required',
            'birth_date' => 'required',
            'email' => 'required',
            'expertise_ids' => 'required|array|min:1',
            'resume' => 'required',
            'accept_terms' => 'accepted',
        ], [
            'melicode.required' => 'کد ملی الزامی است.',
            'birth_date.required' => 'تاریخ تولد الزامی است.',
            'email.required' => 'آدرس ایمیل الزامی است.',
            'expertise_ids.required' => 'لطفاً حداقل یک تخصص انتخاب کنید.',
            'expertise_ids.min' => 'لطفاً حداقل یک تخصص انتخاب کنید.',
            'resume.required' => 'بارگذاری رزومه الزامی است.',
            'accept_terms.accepted' => 'پذیرش قوانین و مقررات الزامی می‌باشد.',
        ]);

        $data = $request->validated();
        
        // Add resume file if exists
        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume');
        }
        
        $result = $this->service->register($data);
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }

        $adminNotifications->sendToAdmins(
            'ثبت‌نام تکنسین جدید',
            'درخواست ثبت‌نام یک تکنسین جدید برای بررسی در پنل ثبت شد.'
        );
        
        return redirect()
            ->route('web.technician.verify-phone-form')
            ->with('success', $result['message'])
            ->with('phone', $data['phone']);
    }

    // ========== Phone Verification Views & Actions ==========
    
    public function showVerifyPhoneForm()
    {
        return view('technician.auth.verify-phone');
    }

    public function verifyPhone(TechnicianPhoneVerificationRequest $request)
    {
        $result = $this->service->verifyPhone($request->phone, $request->code);
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }
        
        // کد پرسنلی و رمز عبور پیامک شده است؛ ورود در خود اپ تکنسین انجام می‌شود
        return redirect()->away($this->technicianPanelLoginUrl());
    }

    public function resendVerificationCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ], [
            'phone.required' => 'شماره تلفن الزامی است.',
            'phone.regex' => 'شماره تلفن باید با 09 شروع شده و 11 رقم باشد.',
        ]);

        $result = $this->service->resendVerificationCode($request->phone);
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }
        
        return back()->with('success', $result['message']);
    }

    // ========== Login Views & Actions ==========
    
    // ورود و پنل تکنسین همان اپ تکنسین (نسخه‌ی وب) است، نه صفحه‌های جداگانه‌ی سایت
    public function showLoginForm()
    {
        return redirect()->away($this->technicianPanelLoginUrl());
    }

    private function technicianPanelLoginUrl(): string
    {
        return rtrim(config('app.technician_panel_url'), '/') . '/login';
    }

    public function login(TechnicianLoginRequest $request)
    {
        $result = $this->service->login($request->referral_code, $request->password);
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }
        
        // Log in the technician using the technician guard
        $technician = \App\Models\Technician::where('referral_code', $request->referral_code)->first();
        
        if ($technician) {
            Auth::guard('technician')->login($technician, $request->filled('remember'));
            $this->loginActivityService->logLogin('technician', (int) $technician->id, $request);
        }
        
        return redirect()
            ->route('web.technician.dashboard')
            ->with('success', 'با موفقیت وارد شدید.');
    }

    public function logout(Request $request)
    {
        $technician = Auth::guard('technician')->user();
        Auth::guard('technician')->logout();
        if ($technician) {
            $this->loginActivityService->logLogout('technician', (int) $technician->id, $request);
        }
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()
            ->route('web.technician.login-form')
            ->with('success', 'با موفقیت خارج شدید.');
    }

    // ========== Forgot Password Views & Actions ==========
    
    public function showForgotPasswordForm()
    {
        return view('technician.auth.forgot-password');
    }

    public function sendResetCode(TechnicianForgotPasswordRequest $request)
    {
        $result = $this->service->sendResetCode($request->validated());
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }
        
        return redirect()
            ->route('web.technician.verify-reset-code-form')
            ->with('success', $result['message'])
            ->with('phone', $result['phone']);
    }

    public function showVerifyResetCodeForm()
    {
        return view('technician.auth.verify-reset-code');
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|size:6',
        ], [
            'phone.required' => 'شماره تلفن الزامی است.',
            'phone.regex' => 'شماره تلفن باید با 09 شروع شده و 11 رقم باشد.',
            'code.required' => 'کد تأیید الزامی است.',
            'code.size' => 'کد تأیید باید 6 رقم باشد.',
        ]);

        $result = $this->service->verifyResetCode($request->phone, $request->code);
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }
        
        return redirect()
            ->route('web.technician.reset-password-form')
            ->with('success', $result['message'])
            ->with('phone', $request->phone);
    }

    public function showResetPasswordForm()
    {
        return view('technician.auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'phone.required' => 'شماره تلفن الزامی است.',
            'phone.regex' => 'شماره تلفن باید با 09 شروع شده و 11 رقم باشد.',
            'new_password.required' => 'رمز عبور جدید الزامی است.',
            'new_password.min' => 'رمز عبور جدید باید حداقل 6 کاراکتر باشد.',
            'new_password.confirmed' => 'تأیید رمز عبور با رمز عبور جدید مطابقت ندارد.',
        ]);

        $result = $this->service->resetPassword($request->phone, $request->new_password);
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }
        
        return redirect()->away($this->technicianPanelLoginUrl());
    }

    // ========== Dashboard (Placeholder) ==========
    
    public function dashboard()
    {
        // Get authenticated technician from guard
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }
        
        return view('technician.auth.dashboard', compact('technician'));
    }
}
