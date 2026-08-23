<?php

namespace App\Http\Controllers;

use App\DTOs\ForgotPasswordDTO;
use App\Http\Requests\ForgotPasswordRequest;
use App\Services\ForgotPasswordService;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected ForgotPasswordService $forgotPasswordService
    ) {}

    /**
     * Send password reset code to user
     */
    public function sendResetCode(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            // Create DTO from validated request data
            $dto = ForgotPasswordDTO::fromArray($request->validated());

            // Send reset code
            $result = $this->forgotPasswordService->sendResetCode($dto);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'phone' => $result['phone']
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال کد بازیابی. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}