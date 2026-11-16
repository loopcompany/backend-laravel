<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\OrganizationContractGallery;
use App\Models\OrganizationContractRequest;
use App\Services\AdminPanelNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * دریافت آخرین قرارداد عمومی فعال
     * فقط برای کاربران سازمانی
     */
    public function getLatest(Request $request): JsonResponse
    {
        $user = $request->user();

        // بررسی نوع حساب کاربر
        if (!$user || !$user->isOrganization()) {
            return response()->json([
                'success' => false,
                'message' => 'فقط کاربران سازمانی می‌توانند قرارداد عمومی را دریافت کنند.',
            ], 403);
        }

        // دریافت آخرین قرارداد فعال
        $contract_request = OrganizationContractRequest::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->with('gallery')
            ->first();

        if (!$contract_request) {
            return response()->json([
                'success' => false,
                'message' => 'در حال حاضر درخواستی موجود نیست.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'آخرین درخواست با موفقیت دریافت شد.',
            'data' => [
                'id' => $contract_request->id,
                'status' => $contract_request->status,
                'need_docs' => $contract_request->need_docs,
                'reject_reason' => $contract_request->reject_reason,
                'contract_file_path' => $contract_request->contract_file_path,
                'signed_contract_file_path' => $contract_request->signed_contract_file_path,
                'title' => $contract_request->title,
                'information' => $contract_request->information,
                'gallery' => $contract_request->gallery,
                'status_label' => [
                    '0' => 'در انتظار بررسی',
                    '1' => 'درخواست تأیید شده',
                    '2' => 'درخواست رد شده',
                    '3' => 'قرارداد تأیید شده',
                    '4' => 'قرارداد رد شده',
                ][$contract_request->status],
                'uploaded_by_admin_at' => $contract_request->uploaded_by_admin_at,
                'uploaded_at' => $contract_request->uploaded_at,
            ],
        ]);
    }
    public function submitRequest(Request $request, AdminPanelNotificationService $adminNotifications): JsonResponse
    {
        // Keep the legacy URL while enforcing the same explicit, eligible request.
        return app(OrganizationAgreementController::class)->store($request, app(\App\Services\OrganizationAgreementService::class), $adminNotifications);
    }
    public function submitInformationForRequest(Request $request, AdminPanelNotificationService $adminNotifications): JsonResponse
    {
        $request->validate(['information'=>['nullable','string','max:20000'],'files'=>['sometimes','array','max:5'],
            'files.*'=>['file','mimes:pdf,png,jpg,jpeg','max:20480']]);
        $user = $request->user();

        if (!$user instanceof \App\Models\User || !$user->isOrganization()) {
            return response()->json([
                'success' => false,
                'message' => __("Just organizational users can submit information for contract request."),
            ], 429);
        }

        $contract_request = OrganizationContractRequest::where('user_id', $user->id)
            ->when($request->filled('contract_request_id'), fn ($query) => $query->whereKey($request->integer('contract_request_id')))
            ->latest()->first();

        if (!$contract_request) {
            return response()->json([
                'success' => false,
                'message' => __("You have not submitted any contract request yet."),
            ], 429);
        }

        abort_unless(in_array((string)$contract_request->status,['0','1','4'],true),409,'این سابقه قابل ویرایش نیست؛ درخواست جدید ثبت کنید.');
        // آپدیت اطلاعات
        $contract_request->update([
            'information' => $request->information,
        ]);

        // بررسی وجود فایل
        if ($request->hasFile('files')) {

            foreach ($request->file('files') as $file) {

                $path = $file->store('organization-contracts', 'public');

                OrganizationContractGallery::create([
                    'organization_contract_id' => $contract_request->id,
                    'file_path' => $path
                ]);
            }
        }

        $adminNotifications->sendToAdmins(
            'مدارک درخواست سازمانی دریافت شد',
            'اطلاعات یا مدارک درخواست قرارداد سازمانی شماره ' . $contract_request->id . ' ارسال شد.'
        );

        return response()->json([
            'success' => true,
            'message' => __("Your request submitted successfully."),
        ], 200);
    }
    public function deleteGalleryItem(Request $request): JsonResponse
    {
        $user = $request->user();

        $contract_request = OrganizationContractRequest::where('user_id', $user->id)
            ->where('id', $request->contract_request_id)
            ->first();

        if (!$contract_request) {
            return response()->json([
                'success' => false,
                'message' => __("You have not submitted any contract request yet."),
            ], 409);
        }

        $gallery = OrganizationContractGallery::where('organization_contract_id', $request->contract_request_id)->where('id', $request->gallery_id)->first();

        if (!$gallery) {
            return response()->json([
                'success' => false,
                'message' => __("You have not submitted any contract request yet."),
            ], 409);
        } else {
            $gallery->delete();
            return response()->json([
                'success' => true,
                'message' => __("Your request submitted successfully."),
            ], 200);
        }
    }

}
