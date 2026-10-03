<?php

namespace App\Services\Security;

use App\Exceptions\AccountSecurityException;
use App\Models\AccountSecuritySetting;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * تأیید دومرحله‌ای حساب کاربر عادی و سازمانی (پیامک یا اپ احراز هویت) + کدهای بازیابی.
 */
class TwoFactorService
{
    public const RECOVERY_CODES_COUNT = 8;
    public const CHALLENGE_TTL = 600;          // ۱۰ دقیقه برای وارد کردن کد هنگام ورود
    public const CHALLENGE_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly SecurityCodeService $codes,
        private readonly AccountSecurityLogger $logger,
    ) {
    }

    public function settings(User $user): AccountSecuritySetting
    {
        return $this->logger->settings($user);
    }

    public function isEnabled(User $user): bool
    {
        return (bool) AccountSecuritySetting::where('account_type', $user->getMorphClass())
            ->where('account_id', $user->getKey())
            ->value('two_factor_enabled');
    }

    public function summary(User $user): array
    {
        $settings = $this->settings($user);

        return [
            'enabled' => $settings->two_factor_enabled,
            'method' => $settings->two_factor_enabled ? $settings->two_factor_method : null,
            'verified_mobile' => $user->phone_verified_at ? $user->phone : null,
        ];
    }

    /**
     * purpose: enable | disable | recovery_codes ؛ method: sms | app
     */
    public function sendCode(User $user, string $purpose, string $method): array
    {
        $settings = $this->settings($user);

        if ($purpose === 'enable') {
            if ($settings->two_factor_enabled) {
                throw new AccountSecurityException('تأیید دومرحله‌ای از قبل فعال است.', 'ALREADY_ENABLED', 422);
            }

            if ($method === 'app') {
                $secret = Totp::generateSecret();
                $settings->update(['two_factor_pending_secret' => $secret]);

                return [
                    'secret' => $secret,
                    'otpauth_url' => Totp::otpauthUrl($secret, $user->phone ?: (string) $user->id),
                ];
            }

            $this->ensureVerifiedMobile($user);

            return $this->only($this->codes->send($user, '2fa-enable', $user->phone), ['expires_in', 'resend_in']);
        }

        if (!$settings->two_factor_enabled) {
            throw new AccountSecurityException('تأیید دومرحله‌ای فعال نیست.', 'NOT_ENABLED', 422);
        }

        // برای غیرفعال‌سازی و کدهای بازیابی با روش app، کد همان اپ است و پیامکی ارسال نمی‌شود
        if ($settings->two_factor_method === 'app') {
            return ['method' => 'app'];
        }

        return $this->only($this->codes->send($user, '2fa-' . $purpose, $user->phone), ['expires_in', 'resend_in']);
    }

    /** @return array{recovery_codes: string[]} */
    public function enable(User $user, string $method, string $code, ?Request $request = null): array
    {
        $settings = $this->settings($user);

        if ($settings->two_factor_enabled) {
            throw new AccountSecurityException('تأیید دومرحله‌ای از قبل فعال است.', 'ALREADY_ENABLED', 422);
        }

        if ($method === 'app') {
            $secret = $settings->two_factor_pending_secret;
            if (!$secret) {
                throw new AccountSecurityException('ابتدا کد راه‌اندازی اپ احراز هویت را دریافت کنید.', 'SETUP_REQUIRED', 422);
            }
            if (!Totp::verify($secret, SecurityCodeService::normalizeDigits($code))) {
                throw new AccountSecurityException('کد تأیید نادرست است.', 'INVALID_CODE', 422);
            }
        } else {
            $this->ensureVerifiedMobile($user);
            $this->codes->verify($user, '2fa-enable', $code);
            $secret = null;
        }

        $recoveryCodes = $this->freshRecoveryCodes();

        $settings->update([
            'two_factor_enabled' => true,
            'two_factor_method' => $method,
            'two_factor_secret' => $secret,
            'two_factor_pending_secret' => null,
            'two_factor_recovery_codes' => $this->hashCodes($recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->logger->activity($user, 'two_factor_enabled', $request, meta: ['method' => $method]);
        $this->logger->alert($user, 'two_factor_changed', $request, ['message' => 'تأیید دومرحله‌ای حساب شما فعال شد.']);

        return ['recovery_codes' => $recoveryCodes];
    }

    public function disable(User $user, string $code, ?Request $request = null): void
    {
        $settings = $this->settings($user);
        if (!$settings->two_factor_enabled) {
            throw new AccountSecurityException('تأیید دومرحله‌ای فعال نیست.', 'NOT_ENABLED', 422);
        }

        $this->verifyManagementCode($user, $settings, 'disable', $code);

        $settings->update([
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'two_factor_secret' => null,
            'two_factor_pending_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->logger->activity($user, 'two_factor_disabled', $request);
        $this->logger->alert($user, 'two_factor_changed', $request, ['message' => 'تأیید دومرحله‌ای حساب شما غیرفعال شد.']);
    }

    /** کدهای قبلی باطل می‌شوند. */
    public function regenerateRecoveryCodes(User $user, string $code, ?Request $request = null): array
    {
        $settings = $this->settings($user);
        if (!$settings->two_factor_enabled) {
            throw new AccountSecurityException('تأیید دومرحله‌ای فعال نیست.', 'NOT_ENABLED', 422);
        }

        $this->verifyManagementCode($user, $settings, 'recovery_codes', $code);

        $recoveryCodes = $this->freshRecoveryCodes();
        $settings->update(['two_factor_recovery_codes' => $this->hashCodes($recoveryCodes)]);
        $this->logger->activity($user, 'security_info_changed', $request, meta: ['change' => 'recovery_codes']);

        return ['recovery_codes' => $recoveryCodes];
    }

    // ----------------------------------------------------------------- ورود

    /**
     * مرحله‌ی دوم ورود را شروع می‌کند. kind مشخص می‌کند پاسخ نهایی با کدام قالب ورود ساخته شود.
     *
     * @return array{two_factor_token: string, method: string}
     */
    public function startChallenge(User $user, string $kind): array
    {
        $settings = $this->settings($user);
        $token = Str::random(64);

        Cache::put($this->challengeKey($token), [
            'user_id' => $user->id,
            'kind' => $kind,
            'attempts' => 0,
        ], self::CHALLENGE_TTL);

        if ($settings->two_factor_method !== 'app') {
            $this->codes->send($user, '2fa-login', $user->phone);
        }

        return ['two_factor_token' => $token, 'method' => $settings->two_factor_method ?? 'sms'];
    }

    /**
     * @return array{user: User, kind: string}
     */
    public function completeChallenge(string $challengeToken, string $code): array
    {
        $key = $this->challengeKey($challengeToken);
        $challenge = Cache::get($key);

        if (!$challenge) {
            throw new AccountSecurityException('مهلت ورود به پایان رسیده است. لطفاً دوباره وارد شوید.', 'TWO_FACTOR_EXPIRED', 422);
        }

        $user = User::find($challenge['user_id']);
        if (!$user || !$user->hasAccess()) {
            Cache::forget($key);
            throw new AccountSecurityException('ورود امکان‌پذیر نیست.', 'ACCOUNT_DISABLED', 403);
        }

        $settings = $this->settings($user);

        if (!$this->verifyLoginCode($user, $settings, $code)) {
            $challenge['attempts']++;

            if ($challenge['attempts'] >= self::CHALLENGE_MAX_ATTEMPTS) {
                Cache::forget($key);
                $this->logger->failedLogin($user, request(), 'two_factor');
                throw new AccountSecurityException('تعداد تلاش ناموفق بیش از حد مجاز است. لطفاً دوباره وارد شوید.', 'TWO_FACTOR_LOCKED', 422);
            }

            Cache::put($key, $challenge, self::CHALLENGE_TTL);
            throw new AccountSecurityException('کد تأیید نادرست است.', 'INVALID_CODE', 422);
        }

        Cache::forget($key);

        return ['user' => $user, 'kind' => $challenge['kind']];
    }

    // -----------------------------------------------------------------

    private function verifyLoginCode(User $user, AccountSecuritySetting $settings, string $code): bool
    {
        if ($this->consumeRecoveryCode($settings, $code)) {
            return true;
        }

        if ($settings->two_factor_method === 'app') {
            return $this->verifyTotpOnce($user, $settings->two_factor_secret, $code);
        }

        try {
            $this->codes->verify($user, '2fa-login', $code);

            return true;
        } catch (AccountSecurityException $e) {
            if ($e->errorCode === 'CODE_EXPIRED' || $e->errorCode === 'CODE_LOCKED') {
                throw $e;
            }

            return false;
        }
    }

    /** کد مدیریت (غیرفعال‌سازی / کدهای بازیابی): کد همان روش فعال، یا یک کد بازیابی. */
    private function verifyManagementCode(User $user, AccountSecuritySetting $settings, string $purpose, string $code): void
    {
        if ($this->consumeRecoveryCode($settings, $code)) {
            return;
        }

        if ($settings->two_factor_method === 'app') {
            if (!$this->verifyTotpOnce($user, $settings->two_factor_secret, $code)) {
                throw new AccountSecurityException('کد تأیید نادرست است.', 'INVALID_CODE', 422);
            }

            return;
        }

        $this->codes->verify($user, '2fa-' . $purpose, $code);
    }

    /** هر کد اپ فقط یک بار پذیرفته می‌شود (جلوگیری از استفاده‌ی دوباره در همان بازه‌ی ۹۰ ثانیه‌ای). */
    private function verifyTotpOnce(User $user, ?string $secret, string $code): bool
    {
        $code = SecurityCodeService::normalizeDigits($code);

        if (!$secret || !Totp::verify($secret, $code)) {
            return false;
        }

        return Cache::add('totp-used:' . $user->id . ':' . $code, true, 90);
    }

    private function consumeRecoveryCode(AccountSecuritySetting $settings, string $code): bool
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', SecurityCodeService::normalizeDigits($code)));
        if (strlen($normalized) !== 8) {
            return false;
        }

        $hashes = $settings->two_factor_recovery_codes ?? [];
        foreach ($hashes as $index => $hash) {
            if (Hash::check($normalized, $hash)) {
                unset($hashes[$index]);
                $settings->update(['two_factor_recovery_codes' => array_values($hashes)]);

                return true;
            }
        }

        return false;
    }

    /** @return string[] به شکل XXXX-XXXX */
    private function freshRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, self::RECOVERY_CODES_COUNT))->map(function () use ($alphabet) {
            $raw = '';
            for ($i = 0; $i < 8; $i++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            return substr($raw, 0, 4) . '-' . substr($raw, 4);
        })->all();
    }

    private function hashCodes(array $codes): array
    {
        return array_map(fn ($c) => Hash::make(str_replace('-', '', $c)), $codes);
    }

    private function ensureVerifiedMobile(User $user): void
    {
        if (!$user->phone || !$user->phone_verified_at) {
            throw new AccountSecurityException('ابتدا شماره موبایل خود را تأیید کنید.', 'MOBILE_NOT_VERIFIED', 422);
        }
    }

    private function challengeKey(string $token): string
    {
        return 'two-factor-challenge:' . hash('sha256', $token);
    }

    private function only(array $data, array $keys): array
    {
        return array_intersect_key($data, array_flip($keys));
    }
}
