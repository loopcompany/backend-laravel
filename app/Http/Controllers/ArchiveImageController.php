<?php

namespace App\Http\Controllers;

use App\Models\ArchiveImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArchiveImageController extends Controller
{
    /**
     * Upload multiple images to archive
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        if (!$technicianId) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // Max 5MB per image
        ], [
            'images.required' => 'لطفاً حداقل یک تصویر انتخاب کنید.',
            'images.array' => 'فرمت ارسال تصاویر صحیح نیست.',
            'images.min' => 'لطفاً حداقل یک تصویر انتخاب کنید.',
            'images.max' => 'حداکثر 10 تصویر می‌توانید یکجا آپلود کنید.',
            'images.*.required' => 'تصویر الزامی است.',
            'images.*.image' => 'فایل باید از نوع تصویر باشد.',
            'images.*.mimes' => 'فرمت تصویر باید jpeg, jpg, png یا webp باشد.',
            'images.*.max' => 'حجم هر تصویر نباید بیشتر از 5 مگابایت باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $uploadedImages = [];

            foreach ($request->file('images') as $image) {
                // Generate unique filename
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Store in public/archive_images directory
                $path = $image->storeAs('archive_images', $filename, 'public');

                // Save to database
                $archiveImage = ArchiveImage::create([
                    'technician_id' => $technicianId,
                    'image_path' => $path,
                ]);

                $uploadedImages[] = [
                    'id' => $archiveImage->id,
                    'image_path' => $path,
                    'image_url' => asset('storage/' . $path),
                    'uploaded_at' => $archiveImage->created_at->toIso8601String(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedImages) . ' تصویر با موفقیت آپلود شد.',
                'data' => $uploadedImages,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود تصاویر.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of technician's archive images
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        if (!$technicianId) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            $images = ArchiveImage::where('technician_id', $technicianId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_path' => $image->image_path,
                        'image_url' => asset('storage/' . $image->image_path),
                        'uploaded_at' => $image->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'لیست تصاویر با موفقیت دریافت شد.',
                'data' => $images,
                'total' => $images->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست تصاویر.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an archive image
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        if (!$technicianId) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            $image = ArchiveImage::where('id', $id)
                ->where('technician_id', $technicianId)
                ->first();

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'تصویر یافت نشد یا دسترسی به حذف آن را ندارید.',
                ], 404);
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }

            // Delete from database
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'تصویر با موفقیت حذف شد.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف تصویر.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
