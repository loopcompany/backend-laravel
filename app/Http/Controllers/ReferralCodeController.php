<?php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralCodeController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'error_code' => 'REFERRAL_CODES_RETIRED',
            'message' => 'سیستم کد معرف حذف شده است؛ پورسانت مسئول خرید مستقل از کد محاسبه می‌شود.'], 410);
    }
}
