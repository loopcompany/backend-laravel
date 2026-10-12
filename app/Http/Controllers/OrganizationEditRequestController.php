<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationEditRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * «درخواست ویرایش» فیلدهای قفل حساب سازمانی با نامه‌ی رسمی (سند اپ کاربر، بخش ۱۱-۱).
 */
class OrganizationEditRequestController extends Controller
{
    private const DISK = 'local';

    /** POST /api/organization/edit-requests (multipart: letter, description, fields[]) */
    public function store(Request $request): JsonResponse
    {
        $organization = $this->organization($request);

        $data = $request->validate([
            'letter' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'description' => 'required|string|max:2000',
            'fields' => 'nullable|array',
            'fields.*' => ['string', Rule::in(array_keys(OrganizationEditRequest::LOCKED_FIELDS))],
        ], [
            'letter.required' => 'بارگذاری نامه‌ی رسمی با مهر و امضا الزامی است.',
            'letter.mimes' => 'فرمت نامه باید PDF یا تصویر (jpg، png، webp) باشد.',
            'letter.max' => 'حجم نامه حداکثر ۱۰ مگابایت است.',
            'letter.uploaded' => 'بارگذاری نامه ناموفق بود. حجم فایل حداکثر ۱۰ مگابایت است.',
            'description.required' => 'توضیح تغییرات درخواستی الزامی است.',
            'description.max' => 'توضیحات حداکثر ۲۰۰۰ کاراکتر است.',
            'fields.*.in' => 'فیلد انتخاب‌شده جزو فیلدهای قابل درخواست نیست.',
        ]);

        $hasOpenRequest = $organization->editRequests()
            ->whereIn('status', [OrganizationEditRequest::STATUS_PENDING, OrganizationEditRequest::STATUS_APPROVED])
            ->exists();

        if ($hasOpenRequest) {
            return response()->json([
                'success' => false,
                'message' => 'یک درخواست ویرایش در جریان دارید. تا بررسی یا استفاده از آن صبر کنید.',
                'error_code' => 'EDIT_REQUEST_EXISTS',
            ], 409);
        }

        $editRequest = $organization->editRequests()->create([
            'user_id' => $request->user()->id,
            'status' => OrganizationEditRequest::STATUS_PENDING,
            'description' => $data['description'],
            'letter_path' => $data['letter']->store("organization-edit-requests/{$organization->id}", self::DISK),
            'fields' => array_values(array_unique($data['fields'] ?? [])) ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'درخواست ویرایش ثبت شد و پس از بررسی نتیجه اعلام می‌شود.',
            'data' => $editRequest->toApiArray(),
        ], 201);
    }

    /** GET /api/organization/edit-requests/{id}/letter — لینک امضاشده‌ی موقت (بدون توکن) */
    public function letter(OrganizationEditRequest $editRequest): StreamedResponse|JsonResponse
    {
        if (!Storage::disk(self::DISK)->exists($editRequest->letter_path)) {
            return response()->json(['success' => false, 'message' => 'فایل پیدا نشد.', 'error_code' => 'FILE_NOT_FOUND'], 404);
        }

        return Storage::disk(self::DISK)->response($editRequest->letter_path, null, ['Cache-Control' => 'private, max-age=300']);
    }

    private function organization(Request $request): Organization
    {
        $user = $request->user();
        $organization = method_exists($user, 'isOrganization') && $user->isOrganization() ? $user->organization : null;

        abort_if(!$organization, response()->json([
            'success' => false,
            'message' => 'فقط کاربران سازمانی می‌توانند از این بخش استفاده کنند.',
            'error_code' => 'INVALID_USER_TYPE',
        ], 403));

        return $organization;
    }
}
