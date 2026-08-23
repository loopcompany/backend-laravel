<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Services\ShahkarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShahkarController extends Controller
{
    public function __construct(
        protected ShahkarService $shahkarService
    ) {
    }

    /**
     * استعلام تطابق شماره موبایل با کد ملی
     */
    public function inquiry(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'mobile' => [
                    'required',
                    'string',
                    'regex:/^09[0-9]{9}$/',
                ],
                'national_code' => [
                    'required',
                    'string',
                    'digits:10',
                ],
            ], [
                'mobile.required' => 'شماره موبایل الزامی است.',
                'mobile.regex' => 'فرمت شماره موبایل صحیح نیست.',
                'national_code.required' => 'کد ملی الزامی است.',
                'national_code.digits' => 'کد ملی باید ۱۰ رقم باشد.',
            ]);

            $mobile = $request->input('mobile');
            $nationalCode = $request->input('national_code');

            if (!Helper::is_valid_national_code($nationalCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'کد ملی وارد شده معتبر نیست.',
                ], 422);
            }

            $result = $this->shahkarService->inquiry($mobile, $nationalCode);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['matched']
                    ? 'شماره موبایل و کد ملی مطابقت دارند.'
                    : 'شماره موبایل و کد ملی مطابقت ندارند.',
                'data' => [
                    'matched' => $result['matched'],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در استعلام شاهکار. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
