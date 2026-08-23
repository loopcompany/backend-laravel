<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicianForgotPasswordRequest;
use App\Services\TechnicianRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TechnicianForgotPasswordController extends Controller
{
    public function __construct(private TechnicianRegistrationService $service) {}

    public function sendResetCode(TechnicianForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->service->sendResetCode($request->validated());
        
        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'REGISTRATION_NOT_COMPLETE' => 403,
                'DATA_MISMATCH' => 422,
                default => 400
            };
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'RESET_CODE_ERROR'
            ], $statusCode);
        }
        
        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'phone' => $result['phone']
            ]
        ]);
    }

    public function verifyResetCode(Request $request): JsonResponse
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
            $statusCode = match($result['error_code'] ?? '') {
                'TECHNICIAN_NOT_FOUND' => 404,
                'INVALID_RESET_CODE' => 422,
                default => 400
            };
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'VERIFY_ERROR'
            ], $statusCode);
        }
        
        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
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
            $statusCode = match($result['error_code'] ?? '') {
                'TECHNICIAN_NOT_FOUND' => 404,
                default => 400
            };
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'RESET_PASSWORD_ERROR'
            ], $statusCode);
        }
        
        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }
}
