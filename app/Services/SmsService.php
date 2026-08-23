<?php

namespace App\Services;

use App\Helpers\Helper;
use Exception;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Generate a 6-digit verification code
     */
    public function generateVerificationCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Hash verification code for storage
     */
    public function hashVerificationCode(string $code): string
    {
        return password_hash($code, PASSWORD_DEFAULT);
    }

    /**
     * Send SMS verification code
     */
    public function sendVerificationCode(string $phone, string $code, ?string $hashApp = ''): bool
    {
        Log::info('Sending verification SMS', [
            'phone' => $phone,
            'code_length' => strlen($code),
            'timestamp' => now()->toDateTimeString()
        ]);

        $result = $this->sendSmsCode($phone, $code, 'verification', $hashApp);

        Log::info('Verification SMS send result', [
            'phone' => $phone,
            'success' => $result,
            'timestamp' => now()->toDateTimeString()
        ]);

        return $result;
    }
    public function sendUserCode(string $phone, string $code): bool
    {
        Log::info('Sending User code SMS', [
            'phone' => $phone,
            'code_length' => strlen($code),
            'timestamp' => now()->toDateTimeString()
        ]);

        $result = $this->sendSmsCode($phone, $code, 'user_code');

        Log::info('UserCode SMS send result', [
            'phone' => $phone,
            'success' => $result,
            'timestamp' => now()->toDateTimeString()
        ]);

        return $result;
    }

    /**
     * Send SMS password reset code
     */
    public function sendPasswordResetCode(string $phone, string $code, ?string $hashApp = ''): bool
    {
        return $this->sendSmsCode($phone, $code, 'forgot_password', $hashApp);
    }

    /**
     * Send SMS code with specified template
     */
    private function sendSmsCode(string $phone, string $code, string $type = 'verification', ?string $hashApp = ''): bool
    {
        try {
            // Get template ID and parameters based on type
            $templateId = match ($type) {
                'forgot_password' => config('smsir.forgot_password_template_id', 100000),
                'secure_password' => config('smsir.secure_password_template_id', 100000),
                'user_code' => config('smsir.user_code_template_id', 100000),
                default => config('smsir.verification_template_id', 100000)
            };

            // Set parameters based on type
            if ($type == 'secure_password') {
                $names = ['SecurePassword'];
                $values = [$code]; // $code contains the password in this case
            } else if ($type == 'user_code') {
                $names = ['code'];
                $values = [$code];
            } else {
                $names = ['VerificationCode', 'hashApp'];
                $values = [$code, $hashApp];
            }

            $result = Helper::send_sms($phone, $templateId, $names, $values);

            if ($result == true) {
                Log::info("SMS {$type} code sent successfully", [
                    'phone' => $phone,
                    'type' => $type,
                    'code' => $code // Remove this in production for security
                ]);
                return true;
            }

            Log::error("Failed to send SMS {$type} code", [
                'phone' => $phone,
                'type' => $type,
                'error' => $result
            ]);

            return false;

        } catch (Exception $e) {
            Log::error("SMS {$type} service error", [
                'phone' => $phone,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send secure password via SMS
     */
    public function sendSecurePassword(string $phone, string $password): bool
    {
        return $this->sendSmsCode($phone, $password, 'secure_password');
    }

    /**
     * Verify if code matches the stored hash
     */
    public function verifyCode(string $code, string $hashedCode): bool
    {
        return password_verify($code, $hashedCode);
    }

    /**
     * ارسال پیامک با استفاده از قالب SMS.ir (متد عمومی)
     */
    private function sendSmsWithTemplate(string $phone, int $templateId, array $parameters): bool
    {
        try {
            $apiKey = config('smsir.api-key');

            if (!$apiKey || !$templateId) {
                Log::error('کانفیگ SMS.ir یافت نشد', [
                    'phone' => $phone,
                    'template_id' => $templateId,
                    'api_key_exists' => !empty($apiKey)
                ]);
                return false;
            }

            $curl = curl_init();

            $postData = [
                "mobile" => $phone,
                "templateId" => $templateId,
                "parameters" => $parameters
            ];

            Log::info('ارسال پیامک', [
                'phone' => $phone,
                'template_id' => $templateId,
                'parameters' => $parameters
            ]);

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.sms.ir/v1/send/verify",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "X-API-KEY: " . $apiKey
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error("خطای cURL در ارسال پیامک", [
                    'error' => $err,
                    'phone' => $phone,
                    'template_id' => $templateId
                ]);
                return false;
            }

            $result = json_decode($response, true);

            Log::info('پاسخ SMS.ir', [
                'http_code' => $httpCode,
                'response' => $result,
                'phone' => $phone,
                'template_id' => $templateId
            ]);

            return $httpCode === 200 || $httpCode === 201;

        } catch (\Exception $e) {
            Log::error('خطا در ارسال پیامک: ' . $e->getMessage(), [
                'phone' => $phone,
                'template_id' => $templateId,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * ارسال پیامک لغو سفارش توسط ادمین به تکنسین
     * Template ID: 482506
     * متن: همکار محترم، سفارش به شناسه ی #ID# توسط مدیریت لغو شد.
     */
    public function sendOrderCancelledByAdminToTechnician(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.order_cancelled_by_admin_to_technician_template_id');

        if (!$templateId) {
            Log::error('Template ID برای لغو سفارش توسط ادمین به تکنسین یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک لغو سفارش توسط ادمین به کاربر
     * Template ID: 227977
     * متن: کاربر گرامی لوپ سفارش شما به شناسه ی #ID# توسط مدیریت لوپ لغو گردید.
     */
    public function sendOrderCancelledByAdminToUser(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.order_cancelled_by_admin_to_user_template_id');

        if (!$templateId) {
            Log::error('Template ID برای لغو سفارش توسط ادمین به کاربر یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک لغو سفارش توسط تکنسین به کاربر
     * Template ID: 684622
     * متن: کاربر گرامی لوپ سفارش شما به شناسه ی #ID# توسط تکنسین لوپ لغو گردید. می توانید با مراجعه به جزئیات سفارش علت لغو را مشاهده کنید.
     */
    public function sendOrderCancelledByTechnicianToUser(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.order_cancelled_by_technician_to_user_template_id');

        if (!$templateId) {
            Log::error('Template ID برای لغو سفارش توسط تکنسین به کاربر یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }
    public function sendOrderDoneInPlaceByTechnicianToAdmin(string $phone, int $orderId, string $technicianName): bool
    {
        $templateId = config('smsir.order_done_in_place_by_technician_to_admin_template_id');

        if (!$templateId) {
            Log::error('Template ID برای انجام سفارش در محل کاربر توسط تکنسین به ادمین یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ],
            [
                "name" => "NAME",
                "value" => (string) $technicianName
            ]
        ]);
    }

    /**
     * ارسال پیامک لغو سفارش توسط تکنسین به ادمین
     * Template ID: 435317
     * متن: مدیریت محترم لوپ سفارش به شناسه ی #ID# توسط تکنسین لغو گردید. با مراجعه به پنل مدیریت می توانید علت لغو را مشاهده فرمایید.
     */
    public function sendOrderCancelledByTechnicianToAdmin(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.order_cancelled_by_technician_to_admin_template_id');

        if (!$templateId) {
            Log::error('Template ID برای لغو سفارش توسط تکنسین به ادمین یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک درخواست فوری تکنسین به مدیر
     * Template ID: 333169
     * متن: مدیریت محترم لوپ توسط تکنسین سفارش به شناسه #ID# درخواست فوری ثبت گردیده است.
     */
    public function sendUrgentRequestByTechnicianToAdmin(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.urgent_request_by_technician_to_admin_template_id');

        if (!$templateId) {
            Log::error('Template ID برای درخواست فوری تکنسین به مدیر یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک تغییر زمان/توضیحات توسط تکنسین به کاربر
     * Template ID: 575053
     * متن: کاربر گرامی لوپ سفارش شما به شناسه ی #ID# توسط تکنسین لوپ بررسی شده توضیحاتی ثبت شده است. لطفا پس از بررسی آن را تایید یا رد فرمایید.
     */
    public function sendTimeChangeOrDescriptionByTechnicianToUser(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.time_change_or_description_by_technician_to_user_template_id');

        if (!$templateId) {
            Log::error('Template ID برای تغییر زمان/توضیحات تکنسین به کاربر یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک اختصاص سفارش توسط ادمین به تکنسین
     * Template ID: 797207
     * متن: همکار گرامی سفارش به شناسه ی #ID# توسط مدیریت لوپ به شما اختصاص داده شد. لطفا آن را بررسی نموده و توضیحات خود را برای کاربر ثبت نمایید.
     */
    public function sendOrderAssignedByAdminToTechnician(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.order_assigned_by_admin_to_technician_template_id');

        if (!$templateId) {
            Log::error('Template ID برای اختصاص سفارش توسط ادمین به تکنسین یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک تایید Order Report توسط کاربر به تکنسین
     * Template ID: 807351
     * متن: همکار گرامی کاربر مربوط به سفارش به شناسه ی #ID# اطلاعات ثبت شده توسط شما در رابطه با محصول خود را تایید کرده است.
     */
    public function sendOrderReportApprovedByUserToTechnician(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.order_report_approved_by_user_to_technician_template_id');

        if (!$templateId) {
            Log::error('Template ID برای تایید Order Report توسط کاربر به تکنسین یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک اعزام به لوپ به کاربر
     * Template ID: 693742
     * متن: کاربر گرامی لوپ محصول شما مربوط به سفارش به شناسه ی #ID# به لوپ اعزام گردید.
     */
    public function sendProductSentToLoopToUser(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.product_sent_to_loop_to_user_template_id');

        if (!$templateId) {
            Log::error('Template ID برای اعزام به لوپ به کاربر یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک تایید Order Report توسط کاربر به خودش
     * Template ID: 426460
     * متن: کاربر گرامی لوپ اطلاعات ثبت شده توسط تکنسین لوپ در رابطه با محصول شما به شناسه سفارش #ID#، به واسطه ی شما تایید گردید.
     */
    public function sendOrderReportApprovedByUserToSelf(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.order_report_approved_by_user_to_self_template_id');

        if (!$templateId) {
            Log::error('Template ID برای تایید Order Report توسط کاربر به خودش یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک حرکت تکنسین به سمت محل سفارش
     * Template ID: 607931
     * متن: کاربر عزیز لوپ تکنسین لوپ به سمت محل سفارش با شناسه ی #ID# حرکت کرده است.
     */
    public function sendTechnicianSetOffToUser(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.technician_set_off_to_user_template_id');

        if (!$templateId) {
            Log::error('Template ID برای حرکت تکنسین به محل سفارش یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک رسیدن تکنسین به محل سفارش
     * Template ID: 395978
     * متن: کاربر عزیز لوپ تکنسین لوپ به محل سفارش با شناسه ی #ID# رسیده است.
     */
    public function sendTechnicianArrivedToUser(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.technician_arrived_to_user_template_id');

        if (!$templateId) {
            Log::error('Template ID برای رسیدن تکنسین به محل سفارش یافت نشد');
            return false;
        }

        return $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);
    }

    /**
     * ارسال پیامک خوش‌آمدگویی به سازمان بعد از تایید شماره
     * Template ID: 537149
     * متن: سازمان محترم #NAME# ثبت نام شما با کد سازمانی #CODE# با موفقیت انجام شد.
     * از این پس با این کد سازمانی می توانید به اپلیکیشن لوپ وارد شوید.
     */
    public function sendOrganizationWelcome(string $phone, string $organizationName, string $organizationCode): bool
    {
        $templateId = config('smsir.organization_welcome_template_id');

        if (!$templateId) {
            Log::error('Template ID برای خوش‌آمدگویی سازمان یافت نشد');
            return false;
        }

        Log::info('ارسال پیامک خوش‌آمدگویی به سازمان', [
            'phone' => $phone,
            'organization_name' => $organizationName,
            'organization_code' => $organizationCode,
            'template_id' => $templateId
        ]);

        $result = $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "NAME",
                "value" => $organizationName
            ],
            [
                "name" => "CODE",
                "value" => $organizationCode
            ]
        ]);

        if ($result) {
            Log::info('پیامک خوش‌آمدگویی سازمان با موفقیت ارسال شد', [
                'phone' => $phone,
                'organization_code' => $organizationCode
            ]);
        } else {
            Log::error('خطا در ارسال پیامک خوش‌آمدگویی سازمان', [
                'phone' => $phone,
                'organization_code' => $organizationCode
            ]);
        }

        return $result;
    }

    /**
     * ارسال پیامک ثبت وضعیت محصول توسط تکنسین به کاربر
     * Template ID: 335842
     */
    public function sendProductStatusSubmittedByTechnicianToUser(string $phone, int $orderId): bool
    {
        $templateId = config('smsir.product_status_submitted_by_technician_to_user_template_id');

        if (!$templateId) {
            Log::error('Template ID برای ثبت وضعیت محصول توسط تکنسین یافت نشد');
            return false;
        }

        Log::info('ارسال پیامک ثبت وضعیت محصول به کاربر', [
            'phone' => $phone,
            'order_id' => $orderId,
            'template_id' => $templateId
        ]);

        $result = $this->sendSmsWithTemplate($phone, $templateId, [
            [
                "name" => "ID",
                "value" => (string) $orderId
            ]
        ]);

        if ($result) {
            Log::info('پیامک ثبت وضعیت محصول با موفقیت ارسال شد', [
                'phone' => $phone,
                'order_id' => $orderId
            ]);
        } else {
            Log::error('خطا در ارسال پیامک ثبت وضعیت محصول', [
                'phone' => $phone,
                'order_id' => $orderId
            ]);
        }

        return $result;
    }
}