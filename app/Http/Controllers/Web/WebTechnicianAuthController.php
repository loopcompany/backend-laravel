<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicianRegistrationRequest;
use App\Http\Requests\TechnicianPhoneVerificationRequest;
use App\Http\Requests\TechnicianLoginRequest;
use App\Http\Requests\TechnicianForgotPasswordRequest;
use App\Services\TechnicianRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebTechnicianAuthController extends Controller
{
    public function __construct(
        protected TechnicianRegistrationService $service
    ) {}

    // ========== Registration Views & Actions ==========
    
    public function showRegisterForm()
    {
        return view('technician.auth.register');
    }

    public function register(TechnicianRegistrationRequest $request)
    {
        $data = $request->validated();
        
        // Add resume file if exists
        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume');
        }
        
        // Reuse existing service without modification
        $result = $this->service->register($data);
        
        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }
        
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
        
        return redirect()
            ->route('web.technician.login-form')
            ->with('success', 'شماره تلفن شما با موفقیت تأیید شد. اکنون می‌توانید وارد شوید.');
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
    
    public function showLoginForm()
    {
        return view('technician.auth.login');
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
        }
        
        return redirect()
            ->route('web.technician.dashboard')
            ->with('success', 'با موفقیت وارد شدید.');
    }

    public function logout(Request $request)
    {
        Auth::guard('technician')->logout();
        
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
        
        return redirect()
            ->route('web.technician.login-form')
            ->with('success', 'رمز عبور شما با موفقیت تغییر یافت. اکنون می‌توانید وارد شوید.');
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
