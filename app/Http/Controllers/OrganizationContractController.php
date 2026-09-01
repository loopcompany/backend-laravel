<?php

namespace App\Http\Controllers;

use App\Models\OrganizationContract;
use App\Models\OrganizationContractRequest;
use App\Services\AdminPanelNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrganizationContractController extends Controller
{
    /**
     * دریافت لیست قراردادهای سازمان با تاریخچه
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isOrganization() || !$user->organization) {
            return response()->json([
                'success' => false,
                'message' => 'فقط کاربران سازمانی می‌توانند از این بخش استفاده کنند.',
            ], 403);
        }

        $contracts = OrganizationContractRequest::where('organization_id',$user->organization->id)->where('user_id', $user->id)
            ->get()
            ->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'contract_url' => $contract->contract_url,
                    'status' => $contract->status,
                    'status_label' => [
                        '0' => 'در انتظار بررسی',
                        '1' => 'درخواست تأیید شده',
                        '2' => 'درخواست رد شده',
                        '3' => 'قرارداد تأیید شده',
                        '4' => 'قرارداد رد شده',
                    ][$contract->status],
                    'rejection_reason' => $contract->rejection_reason,
                    'can_edit' => $contract->canBeEdited(),
                    'uploaded_at' => $contract->uploaded_at,
                    'reviewed_at' => $contract->reviewed_at,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'لیست قراردادها با موفقیت دریافت شد.',
            'data' => $contracts,
        ]);
    }

    /**
     * آپلود یا ویرایش قرارداد سازمان
     */
    public function upload(Request $request, AdminPanelNotificationService $adminNotifications): JsonResponse
    {
        $user = $request->user();

        if (!$user->isOrganization() || !$user->organization) {
            return response()->json([
                'success' => false,
                'message' => 'فقط کاربران سازمانی می‌توانند قرارداد آپلود کنند.',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'contract_file' => 'required|file|mimes:pdf|max:512000', // 500MB
        ], [
            'contract_file.required' => 'فایل قرارداد الزامی است.',
            'contract_file.file' => 'فایل معتبر نیست.',
            'contract_file.mimes' => 'فقط فایل‌های PDF مجاز هستند.',
            'contract_file.max' => 'حداکثر حجم فایل 500 مگابایت است.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors(),
            ], 422);
        }

        // بررسی آخرین قرارداد
        $latestContract = OrganizationContractRequest::where('organization_id', $user->organization->id)->where('user_id', $user->id)->first();
        if ($latestContract && $latestContract->status == '3') {
            return response()->json([
                'success' => false,
                'message' => 'قرارداد شما تایید شده است و امکان ویرایش وجود ندارد.',
            ], 400);
        }

        // آپلود فایل
        $file = $request->file('contract_file');
        $path = $file->store('organization_contracts', 'public');

        // ایجاد رکورد جدید (تاریخچه نگه‌داری می‌شود)
        // $contract = OrganizationContract::create([
        //     'organization_id' => $user->organization->id,
        //     'contract_file_path' => $path,
        //     'status' => OrganizationContract::STATUS_PENDING,
        //     'uploaded_at' => now(),
        // ]);
        $latestContract->update(['signed_contract_file_path'=>$path, 'uploaded_at'=>Carbon::now()]);
        // بروزرسانی وضعیت قرارداد در جدول organizations
        $user->organization->update([
            'contract_status' => 'pending'
        ]);

        $adminNotifications->sendToAdmins(
            'قرارداد سازمانی برای بررسی ارسال شد',
            'قرارداد امضاشده درخواست شماره ' . $latestContract->id . ' توسط ' . ($user->organization->organization_name ?: 'یک سازمان') . ' بارگذاری شد.'
        );

        return response()->json([
            'success' => true,
            'message' => __("The contract was successfully uploaded and is awaiting review."),
            'data' => [
                'id' => $latestContract->id,
                'contract_file_path' => $latestContract->contract_file_path,
                'status' => $latestContract->status,
                'signed_contract_file_path' => $latestContract->signed_contract_file_path,
            ],
        ], 201);
    }
}
