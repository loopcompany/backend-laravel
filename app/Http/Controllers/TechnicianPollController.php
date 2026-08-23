<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\TechnicianPoll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TechnicianPollController extends Controller
{
    /**
     * ثبت نظرات و پیشنهادات تکنسین
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // دریافت تکنسین لاگین شده
            $technician = auth('sanctum')->user();
            
            // بررسی احراز هویت
            if (!$technician instanceof Technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            // بررسی اینکه آیا قبلاً نظر ثبت کرده یا نه
            $existingPoll = TechnicianPoll::where('technician_id', $technician->id)->first();
            
            if ($existingPoll) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما قبلاً نظر خود را ثبت کرده‌اید.',
                    'data' => [
                        'id' => $existingPoll->id,
                        'submitted_at' => $existingPoll->created_at->format('Y-m-d H:i:s'),
                    ],
                ], 400);
            }

            // اعتبارسنجی ورودی
            $validator = Validator::make($request->all(), [
                'user_application' => 'nullable|string|max:1000',
                'technician_application' => 'nullable|string|max:1000',
                'inner_personnel' => 'nullable|string|max:1000',
                'field_personnel' => 'nullable|string|max:1000',
                'other' => 'nullable|string|max:2000',
            ], [
                'user_application.string' => 'نظر درباره اپلیکیشن کاربران باید متن باشد.',
                'user_application.max' => 'نظر درباره اپلیکیشن کاربران نباید بیشتر از 1000 کاراکتر باشد.',
                'technician_application.string' => 'نظر درباره اپلیکیشن تکنسین‌ها باید متن باشد.',
                'technician_application.max' => 'نظر درباره اپلیکیشن تکنسین‌ها نباید بیشتر از 1000 کاراکتر باشد.',
                'inner_personnel.string' => 'نظر درباره پرسنل داخلی باید متن باشد.',
                'inner_personnel.max' => 'نظر درباره پرسنل داخلی نباید بیشتر از 1000 کاراکتر باشد.',
                'field_personnel.string' => 'نظر درباره پرسنل میدانی باید متن باشد.',
                'field_personnel.max' => 'نظر درباره پرسنل میدانی نباید بیشتر از 1000 کاراکتر باشد.',
                'other.string' => 'سایر نظرات باید متن باشد.',
                'other.max' => 'سایر نظرات نباید بیشتر از 2000 کاراکتر باشد.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌ها.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // بررسی حداقل یک فیلد پر شده باشد
            if (!$request->filled(['user_application', 'technician_application', 'inner_personnel', 'field_personnel', 'other'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لطفاً حداقل یکی از فیلدها را پر کنید.',
                ], 422);
            }

            // ایجاد نظرسنجی
            $poll = TechnicianPoll::create([
                'technician_id' => $technician->id,
                'user_application' => $request->user_application,
                'technician_application' => $request->technician_application,
                'inner_personnel' => $request->inner_personnel,
                'field_personnel' => $request->field_personnel,
                'other' => $request->other,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'نظرات شما با موفقیت ثبت شد. از همکاری شما سپاسگزاریم.',
                'data' => [
                    'id' => $poll->id,
                    'submitted_at' => $poll->created_at->format('Y-m-d H:i:s'),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت نظرات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * بررسی اینکه آیا تکنسین نظر داده یا نه
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSubmission(Request $request)
    {
        try {
            // دریافت تکنسین لاگین شده
            $technician = auth('sanctum')->user();
            
            // بررسی احراز هویت
            if (!$technician instanceof Technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            $poll = TechnicianPoll::where('technician_id', $technician->id)->first();

            if ($poll) {
                return response()->json([
                    'success' => true,
                    'message' => 'شما قبلاً نظرات خود را ثبت کرده‌اید.',
                    'data' => [
                        'has_submitted' => true,
                        'submitted_at' => $poll->created_at->format('Y-m-d H:i:s'),
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'شما هنوز نظرات خود را ثبت نکرده‌اید.',
                'data' => [
                    'has_submitted' => false,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بررسی وضعیت نظرسنجی.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
