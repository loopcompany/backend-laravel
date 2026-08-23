<?php

namespace App\Helpers;

use App\Models\DiscountCode;
use App\Models\DiscountUse;
use App\Models\GemAction;
use App\Models\GemTransaction;
use App\Models\Transaction;
use App\Models\UserTransaction;
use Cryptommer\Smsir\Smsir;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Invoice;

class Helper
{
    public static function is_valid_national_code($national_code)
    {
        // اطمینان از اینکه کد ملی 10 رقمی است
        if (strlen($national_code) != 10) {
            return false;
        }

        // تبدیل کد ملی به آرایه ارقام
        $digits = str_split($national_code);

        // محاسبه مجموع ضرب 9 رقم اول در وزن‌های مربوطه (10 تا 2)
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $digits[$i] * (10 - $i);
        }

        // محاسبه باقی‌مانده تقسیم بر ۱۱
        $remainder = $sum % 11;

        // محاسبه رقم کنترل
        if ($remainder < 2) {
            $calculated_check_digit = $remainder;
        } else {
            $calculated_check_digit = 11 - $remainder;
        }

        // مقایسه رقم کنترل محاسبه شده با رقم کنترل وارد شده
        return $calculated_check_digit == $digits[9];
    }
    public static function removeHttpLinks($text)
    {

        return preg_replace('/^https?:\/\//i', '', $text);
    }

    public static function convert($string)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $string);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }

    public static function send_sms($phone, $templateId, $names, $values)
    {
        try {
            Log::info('Starting SMS send process', [
                'phone' => $phone,
                'templateId' => $templateId,
                'names' => $names,
                'values' => $values,
                'config_api_key' => config('smsir.api_key') ? 'SET' : 'NOT_SET',
                'config_line_number' => config('smsir.line_number')
            ]);

            $send = Smsir::Send();
            $parameters = array();
            $count = count($names);
            
            for ($i = 0; $i < $count; $i++) {
                $parameters[] = new \Cryptommer\Smsir\Objects\Parameters($names[$i], $values[$i]);
            }

            Log::info('SMS parameters prepared', [
                'phone' => $phone,
                'templateId' => $templateId,
                'parameters_count' => count($parameters)
            ]);

            $res = $send->Verify($phone, $templateId, $parameters);
            
            Log::info('SMS API response received', [
                'phone' => $phone,
                'templateId' => $templateId,
                'response' => $res,
                'response_type' => gettype($res),
                'response_json' => json_encode($res)
            ]);

            // Check if response indicates success
            if (is_object($res) && isset($res->Status) && $res->Status == 1) {
                Log::info('SMS sent successfully', [
                    'phone' => $phone,
                    'templateId' => $templateId,
                    'message_id' => $res->MessageId ?? 'N/A'
                ]);
                return true;
            } elseif (is_array($res) && isset($res['Status']) && $res['Status'] == 1) {
                Log::info('SMS sent successfully', [
                    'phone' => $phone,
                    'templateId' => $templateId,
                    'message_id' => $res['MessageId'] ?? 'N/A'
                ]);
                return true;
            } else {
                Log::error('SMS sending failed', [
                    'phone' => $phone,
                    'templateId' => $templateId,
                    'response' => $res,
                    'error_message' => is_object($res) ? ($res->Message ?? 'Unknown error') : 'Invalid response format'
                ]);
                return $res;
            }

        } catch (\Throwable $th) {
            Log::error('SMS sending exception', [
                'phone' => $phone,
                'templateId' => $templateId,
                'exception_message' => $th->getMessage(),
                'exception_code' => $th->getCode(),
                'exception_file' => $th->getFile(),
                'exception_line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            return $th;
        }
    }

    public static function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace(["\xC2\xA0", "\u{200C}"], [' ', ''], $s);
        $s = str_replace(['ي', 'ك', '‌', 'ۀ', 'ؤ'], ['ی', 'ک', '', 'ه', 'و'], $s);
        $s = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $s
        );
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s);
        $s = preg_replace('/(.)\1{2,}/u', '$1', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return $s;
    }

    public static function discount_check($code, $categoryId, $user_id = null)
    {
        if (!$user_id) {
            $user_id = Auth::user()->id;
        }
        $code_check = DiscountCode::where('user_id', $user_id)->where('code', $code)->first();
        if (!$code_check) {
            return response()->json(['status' => false, 'error' => 'کد وارد شده صحیح نمی‌باشد']);
        }
        if ($code_check->count < 1) {
            return response()->json(['status' => false, 'error' => 'تعداد دفعات مجاز استفاده به پایان رسیده است.']);
        }
        if ($code_check->expiry_date < now()) {
            return response()->json(['status' => false, 'error' => 'زمان مجاز استفاده به پایان رسیده است.']);
        }
        if ((int) $code_check->club->category->id != (int) $categoryId) {
            return response()->json(['status' => false, 'error' => 'مجاز به استفاده‌ در این خدمت نیستید']);
        }


        if ($code_check) {
            $discount_code_id = ($code_check->id) ?? null;
        }
        return response()->json(['status' => true, 'error' => 'تخفیف با موفقیت اعمال شد', 'discount_code_id' => $discount_code_id]);
    }
    public static function discount_user($code, $categoryId, $orderId, $userId)
    {
        $check = Helper::discount_check($code, $categoryId);
        if ($check['status']) {
            $discount_use = new DiscountUse;
            $discount_use->order_id = $orderId;
            $discount_use->user_id = $userId;
            $discount_use->discount_code_id = $check['discount_code_id'];
            $discount_use->save();
        } else {
            return response()->json(['status' => false, 'error' => 'کد منقضی شده‌است.']);
        }
    }
    
}
