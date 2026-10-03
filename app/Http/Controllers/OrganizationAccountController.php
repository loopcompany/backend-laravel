<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationDocument;
use App\Models\OrganizationUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * مدارک سازمان و کاربران مجاز سازمان (بخش ۳-۳ و ۳-۴).
 */
class OrganizationAccountController extends Controller
{
    private const DISK = 'local';
    private const MAX_DOCUMENTS = 50;
    private const MAX_USERS = 50;

    // ----------------------------------------------------------------- مدارک

    /** GET /api/organization/documents */
    public function documents(Request $request): JsonResponse
    {
        $organization = $this->organization($request);

        return response()->json([
            'success' => true,
            'data' => $organization->documents()->latest('id')->get()->map->toApiArray()->values(),
        ]);
    }

    /** POST /api/organization/documents (multipart: file, title?) */
    public function storeDocument(Request $request): JsonResponse
    {
        $organization = $this->organization($request);

        $data = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'title' => 'nullable|string|max:191',
        ], [
            'file.required' => 'فایل مدرک الزامی است.',
            'file.mimes' => 'فرمت فایل باید PDF یا تصویر (jpg، png، webp) باشد.',
            'file.max' => 'حجم فایل حداکثر ۱۰ مگابایت است.',
            'file.uploaded' => 'بارگذاری فایل ناموفق بود. حجم فایل حداکثر ۱۰ مگابایت است.',
        ]);

        if ($organization->documents()->count() >= self::MAX_DOCUMENTS) {
            return $this->fail('حداکثر تعداد مدارک ثبت شده است.', 'DOCUMENTS_LIMIT', 422);
        }

        $file = $data['file'];
        $path = $file->store("organization-documents/{$organization->id}", self::DISK);

        $document = $organization->documents()->create([
            'title' => $data['title'] ?? null,
            'file_path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 191),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'مدرک بارگذاری شد و پس از بررسی تأیید می‌شود.',
            'data' => $document->toApiArray(),
        ], 201);
    }

    /** DELETE /api/organization/documents/{id} */
    public function destroyDocument(Request $request, int $id): JsonResponse
    {
        $document = $this->organization($request)->documents()->whereKey($id)->first();

        if (!$document) {
            return $this->fail('مدرک پیدا نشد.', 'DOCUMENT_NOT_FOUND', 404);
        }

        Storage::disk(self::DISK)->delete($document->file_path);
        $document->delete();

        return response()->json(['success' => true, 'message' => 'مدرک حذف شد.']);
    }

    /** GET /api/organization/documents/{document}/file — فقط با لینک امضاشده (middleware signed). */
    public function documentFile(OrganizationDocument $document): StreamedResponse|JsonResponse
    {
        if (!Storage::disk(self::DISK)->exists($document->file_path)) {
            return $this->fail('فایل پیدا نشد.', 'FILE_NOT_FOUND', 404);
        }

        return Storage::disk(self::DISK)->response(
            $document->file_path,
            $document->original_name,
            ['Cache-Control' => 'private, max-age=300']
        );
    }

    // ----------------------------------------------------------------- کاربران مجاز

    /** GET /api/organization/users */
    public function users(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->organization($request)->authorizedUsers()->orderBy('id')->get()->map->toApiArray()->values(),
        ]);
    }

    /** POST /api/organization/users { full_name, mobile, role? } */
    public function storeUser(Request $request): JsonResponse
    {
        $organization = $this->organization($request);

        $data = $request->validate([
            'full_name' => 'required|string|max:191',
            'mobile' => [
                'required', 'regex:/^09[0-9]{9}$/',
                Rule::unique('organization_users', 'mobile')->where('organization_id', $organization->id),
            ],
            'role' => 'nullable|string|max:50',
        ], [
            'full_name.required' => 'نام و نام خانوادگی الزامی است.',
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'شماره موبایل باید با 09 شروع شده و 11 رقم باشد.',
            'mobile.unique' => 'این شماره قبلاً در فهرست کاربران مجاز ثبت شده است.',
        ]);

        if ($organization->authorizedUsers()->count() >= self::MAX_USERS) {
            return $this->fail('حداکثر تعداد کاربران مجاز ثبت شده است.', 'USERS_LIMIT', 422);
        }

        $user = $organization->authorizedUsers()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'کاربر مجاز اضافه شد.',
            'data' => $user->toApiArray(),
        ], 201);
    }

    /** DELETE /api/organization/users/{id} */
    public function destroyUser(Request $request, int $id): JsonResponse
    {
        $deleted = $this->organization($request)->authorizedUsers()->whereKey($id)->delete();

        if (!$deleted) {
            return $this->fail('کاربر پیدا نشد.', 'USER_NOT_FOUND', 404);
        }

        return response()->json(['success' => true, 'message' => 'کاربر از فهرست حذف شد.']);
    }

    // -----------------------------------------------------------------

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

    private function fail(string $message, string $code, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'error_code' => $code], $status);
    }
}
