<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ShahkarService
{
    /**
     * استعلام تطابق شماره موبایل با کد ملی از سامانه شاهکار (زیبال)
     *
     * @return array{success: bool, matched: bool, message: string}
     */
    public function inquiry(string $mobile, string $nationalCode): array
    {
        try {
            $accessToken = config('shahkar.access_token');

            if (!$accessToken) {
                Log::error('کانفیگ شاهکار یافت نشد', [
                    'mobile' => $mobile,
                ]);
                return [
                    'success' => false,
                    'matched' => false,
                    'message' => 'سرویس احراز هویت شاهکار در دسترس نیست.',
                ];
            }

            $curl = curl_init();

            $postData = [
                'mobile' => $mobile,
                'nationalCode' => $nationalCode,
            ];

            curl_setopt_array($curl, [
                CURLOPT_URL => rtrim(config('shahkar.base_url'), '/') . '/facility/shahkarInquiry',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => (int) config('shahkar.timeout', 15),
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error('خطای cURL در استعلام شاهکار', [
                    'error' => $err,
                    'mobile' => $mobile,
                ]);
                return [
                    'success' => false,
                    'matched' => false,
                    'message' => 'خطا در ارتباط با سرویس شاهکار.',
                ];
            }

            $result = json_decode($response, true);

            Log::info('پاسخ استعلام شاهکار', [
                'http_code' => $httpCode,
                'response' => $result,
                'mobile' => $mobile,
            ]);

            if ($httpCode !== 200 || !isset($result['result']) || (int) $result['result'] !== 1) {
                return [
                    'success' => false,
                    'matched' => false,
                    'message' => $result['message'] ?? 'استعلام شاهکار با خطا مواجه شد.',
                ];
            }

            return [
                'success' => true,
                'matched' => (bool) ($result['data']['matched'] ?? false),
                'message' => $result['message'] ?? 'موفق',
            ];

        } catch (\Exception $e) {
            Log::error('خطا در استعلام شاهکار: ' . $e->getMessage(), [
                'mobile' => $mobile,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'matched' => false,
                'message' => 'خطا در استعلام شاهکار. لطفاً مجدداً تلاش کنید.',
            ];
        }
    }
}
