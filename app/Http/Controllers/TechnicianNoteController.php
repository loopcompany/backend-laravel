<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\TechnicianNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TechnicianNoteController extends Controller
{
    /**
     * ایجاد یادداشت جدید برای تکنسین
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

            // اعتبارسنجی ورودی
            $validator = Validator::make($request->all(), [
                'note' => 'required|string|max:5000',
            ], [
                'note.required' => 'متن یادداشت الزامی است.',
                'note.string' => 'متن یادداشت باید رشته متنی باشد.',
                'note.max' => 'متن یادداشت نباید بیشتر از 5000 کاراکتر باشد.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌ها.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ایجاد یادداشت
            $note = TechnicianNote::create([
                'technician_id' => $technician->id,
                'note' => $request->note,
            ]);

            return response()->json([
                'success' => true,
                'message' => __("Note created successfully."),
                'data' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $note->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد یادداشت.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت لیست یادداشت‌های تکنسین
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
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

            // دریافت یادداشت‌های تکنسین (جدیدترین اول)
            $notes = TechnicianNote::where('technician_id', $technician->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'note' => $note->note,
                        'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $note->updated_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'لیست یادداشت‌ها با موفقیت دریافت شد.',
                'data' => $notes,
                'total' => $notes->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست یادداشت‌ها.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * نمایش جزئیات یک یادداشت خاص
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id)
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

            // جستجوی یادداشت با بررسی مالکیت
            $note = TechnicianNote::where('id', $id)
                ->where('technician_id', $technician->id)
                ->first();

            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'یادداشت مورد نظر یافت نشد.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'جزئیات یادداشت با موفقیت دریافت شد.',
                'data' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $note->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جزئیات یادداشت.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ویرایش یادداشت تکنسین
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
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

            // جستجوی یادداشت با بررسی مالکیت
            $note = TechnicianNote::where('id', $id)
                ->where('technician_id', $technician->id)
                ->first();

            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'یادداشت مورد نظر یافت نشد.',
                ], 404);
            }

            // اعتبارسنجی ورودی
            $validator = Validator::make($request->all(), [
                'note' => 'required|string|max:5000',
            ], [
                'note.required' => 'متن یادداشت الزامی است.',
                'note.string' => 'متن یادداشت باید رشته متنی باشد.',
                'note.max' => 'متن یادداشت نباید بیشتر از 5000 کاراکتر باشد.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌ها.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // بروزرسانی یادداشت
            $note->update([
                'note' => $request->note,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'یادداشت با موفقیت ویرایش شد.',
                'data' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $note->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ویرایش یادداشت.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف یادداشت تکنسین
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id)
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

            // جستجوی یادداشت با بررسی مالکیت
            $note = TechnicianNote::where('id', $id)
                ->where('technician_id', $technician->id)
                ->first();

            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'یادداشت مورد نظر یافت نشد.',
                ], 404);
            }

            // حذف یادداشت
            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'یادداشت با موفقیت حذف شد.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف یادداشت.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
