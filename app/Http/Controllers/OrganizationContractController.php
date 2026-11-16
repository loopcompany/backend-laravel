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

        if (!($user instanceof \App\Models\User) || !$user->isOrganization() || !$user->organization) {
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
        return response()->json(['success' => false, 'message' => 'نسخهٔ امضاشده باید حضوری یا با پیک تحویل شرکت شود؛ انتشار نسخهٔ نهایی توسط ادمین انجام می‌شود.'], 403);
    }
}
