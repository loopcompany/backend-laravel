<?php

namespace App\Services\Security;

use App\Exceptions\AccountSecurityException;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * کدهای پیامکی امنیت حساب (تأیید موبایل، دومرحله‌ای، حذف حساب).
 *
 * - اعتبار کد ۱۲۰ ثانیه، ارسال مجدد بعد از ۶۰ ثانیه.
 * - حداکثر ۵ درخواست کد در ساعت برای هر حساب و هر کاربرد (بعد از آن 429).
 * - حداکثر ۵ ورود کد اشتباه برای هر کد؛ بعد از آن کد باطل می‌شود.
 */
class SecurityCodeService
{
    public const EXPIRES_IN = 120;
    public const RESEND_IN = 60;
    public const MAX_SENDS_PER_HOUR = 5;
    public const MAX_WRONG_ATTEMPTS = 5;

    public function __construct(private readonly SmsService $sms)
    {
    }

    /**
     * @return array{expires_in: int, resend_in: int, remaining_attempts: int}
     *
     * @throws AccountSecurityException
     */
    public function send(Model $account, string $purpose, string $phone, array $context = []): array
    {
        $key = $this->key($account, $purpose);
        $limiterKey = 'security-code-sends:' . $key;

        $resendAt = Cache::get($key . ':resend-at');
        if ($resendAt && now()->timestamp < $resendAt) {
            $wait = $resendAt - now()->timestamp;
            throw new AccountSecurityException("برای ارسال مجدد کد {$wait} ثانیه صبر کنید.", 'RESEND_TOO_SOON', 429, ['resend_in' => $wait]);
        }

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);
            throw new AccountSecurityException("تعداد درخواست کد بیش از حد مجاز است. لطفاً {$minutes} دقیقه‌ی دیگر دوباره تلاش کنید.", 'TOO_MANY_REQUESTS', 429);
        }

        $code = $this->sms->generateVerificationCode();

        if (!$this->sms->sendVerificationCode($phone, $code)) {
            throw new AccountSecurityException('ارسال پیامک با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 'SMS_FAILED', 503);
        }

        RateLimiter::hit($limiterKey, 3600);

        Cache::put($key, [
            'hash' => Hash::make($code),
            'phone' => $phone,
            'context' => $context,
            'expires_at' => now()->timestamp + self::EXPIRES_IN,
            'wrong' => 0,
        ], self::EXPIRES_IN);
        // مهلت ارسال مجدد جدا نگه داشته می‌شود تا با مصرف کد از بین نرود
        Cache::put($key . ':resend-at', now()->timestamp + self::RESEND_IN, self::RESEND_IN);

        return [
            'expires_in' => self::EXPIRES_IN,
            'resend_in' => self::RESEND_IN,
            'remaining_attempts' => max(0, RateLimiter::remaining($limiterKey, self::MAX_SENDS_PER_HOUR)),
        ];
    }

    /**
     * کد را بررسی می‌کند و در صورت درستی آن را مصرف می‌کند. context ذخیره‌شده را برمی‌گرداند.
     *
     * @throws AccountSecurityException
     */
    public function verify(Model $account, string $purpose, ?string $code, array $expectedContext = []): array
    {
        $key = $this->key($account, $purpose);
        $entry = Cache::get($key);

        if (!$entry || now()->timestamp >= $entry['expires_at']) {
            Cache::forget($key);
            throw new AccountSecurityException('کد تأیید منقضی شده است. لطفاً کد جدید دریافت کنید.', 'CODE_EXPIRED', 422);
        }

        if (!is_string($code) || !Hash::check(self::normalizeDigits($code), $entry['hash'])) {
            $entry['wrong']++;

            if ($entry['wrong'] >= self::MAX_WRONG_ATTEMPTS) {
                Cache::forget($key);
                throw new AccountSecurityException('تعداد تلاش ناموفق بیش از حد مجاز است. لطفاً کد جدید دریافت کنید.', 'CODE_LOCKED', 422);
            }

            Cache::put($key, $entry, max(1, $entry['expires_at'] - now()->timestamp));
            throw new AccountSecurityException('کد تأیید نادرست است.', 'INVALID_CODE', 422);
        }

        // کد درست است ولی برای هدف دیگری (مثلاً شماره‌ی دیگر) صادر شده؛ کد مصرف نمی‌شود
        foreach ($expectedContext as $field => $value) {
            if (($entry['context'][$field] ?? null) !== $value) {
                throw new AccountSecurityException('کد تأیید برای این شماره ارسال نشده است.', 'CONTEXT_MISMATCH', 422);
            }
        }

        Cache::forget($key);

        return ['phone' => $entry['phone']] + ($entry['context'] ?? []);
    }

    public static function normalizeDigits(string $value): string
    {
        return strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    private function key(Model $account, string $purpose): string
    {
        return 'security-code:' . class_basename($account) . ':' . $account->getKey() . ':' . $purpose;
    }
}
