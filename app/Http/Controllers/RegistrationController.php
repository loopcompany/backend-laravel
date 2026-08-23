<?php

namespace App\Http\Controllers;

use App\DTOs\RegistrationDTO;
use App\Http\Requests\RegistrationRequest;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function __construct(
        protected RegistrationService $registrationService
    ) {}

    /**
     * Register a new user
     */
    public function register(RegistrationRequest $request): JsonResponse
    {
        try {
            // Create DTO from validated request data
            $dto = RegistrationDTO::fromArray($request->validated());

            // Register user
            $result = $this->registrationService->register($dto);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'user_id' => $result['user_id'],
                        'phone' => $result['phone'],
                    ]
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت نام. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}