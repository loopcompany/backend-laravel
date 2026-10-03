<?php

namespace App\Services\Security;

use App\Models\AccountActivity;
use App\Models\AccountKnownDevice;
use App\Models\AccountSecuritySetting;
use App\Models\SecurityAlert;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/**
 * ثبت «فعالیت‌های اخیر حساب» و «هشدارهای امنیتی» در همان لحظه‌ی رویداد.
 * ثبت این رویدادها هرگز نباید عملیات اصلی (ورود، تغییر رمز و ...) را خراب کند.
 */
class AccountSecurityLogger
{
    /** چند ورود ناموفق در این بازه = هشدار «چند ورود ناموفق» */
    public const FAILED_LOGIN_THRESHOLD = 5;
    public const FAILED_LOGIN_WINDOW_MINUTES = 15;

    private const ALERTS = [
        'new_device_login' => [
            'title' => 'ورود از دستگاه جدید',
            'message' => 'حساب شما از یک دستگاه جدید وارد شد.',
            'severity' => 'high',
            'suggested_action' => 'اگر این ورود توسط شما نبوده، از بخش «دستگاه‌های واردشده» آن را خارج کنید و رمز عبور خود را تغییر دهید.',
        ],
        'multiple_failed_logins' => [
            'title' => 'چند ورود ناموفق',
            'message' => 'چند تلاش ناموفق برای ورود به حساب شما ثبت شده است.',
            'severity' => 'high',
            'suggested_action' => 'اگر این تلاش‌ها از طرف شما نبوده، رمز عبور خود را تغییر دهید و تأیید دومرحله‌ای را فعال کنید.',
        ],
        'password_changed' => [
            'title' => 'تغییر رمز عبور',
            'message' => 'رمز عبور حساب شما تغییر کرد.',
            'severity' => 'high',
            'suggested_action' => 'اگر این تغییر توسط شما نبوده، فوراً با پشتیبانی تماس بگیرید.',
        ],
        'mobile_changed' => [
            'title' => 'تغییر شماره موبایل',
            'message' => 'شماره موبایل حساب شما تغییر کرد.',
            'severity' => 'critical',
            'suggested_action' => 'اگر این تغییر توسط شما نبوده، فوراً با پشتیبانی تماس بگیرید.',
        ],
        'two_factor_changed' => [
            'title' => 'تغییر تأیید دومرحله‌ای',
            'message' => 'وضعیت تأیید دومرحله‌ای حساب شما تغییر کرد.',
            'severity' => 'critical',
            'suggested_action' => 'اگر این تغییر توسط شما نبوده، رمز عبور را تغییر دهید و از سایر دستگاه‌ها خارج شوید.',
        ],
        'unusual_activity' => [
            'title' => 'شناسایی فعالیت غیرعادی',
            'message' => 'فعالیت غیرعادی در حساب شما شناسایی شد.',
            'severity' => 'critical',
            'suggested_action' => 'رمز عبور خود را تغییر دهید و از سایر دستگاه‌ها خارج شوید.',
        ],
    ];

    public function activity(
        Model $account,
        string $type,
        ?Request $request = null,
        string $result = 'success',
        array $meta = [],
        ?PersonalAccessToken $token = null,
    ): ?AccountActivity {
        try {
            $token ??= $this->currentToken($account);
            $userAgent = $request?->userAgent() ?? $token?->user_agent;

            return AccountActivity::create([
                'account_type' => $account->getMorphClass(),
                'account_id' => $account->getKey(),
                'type' => $type,
                'title' => AccountActivity::TYPES[$type] ?? $type,
                'result' => $result,
                'device_id' => $token?->device_id,
                'device' => AccountSessionService::deviceLabel($token, $userAgent),
                'os' => AccountSessionService::osLabel($token, $userAgent),
                'ip' => $request?->ip() ?? $token?->last_ip,
                'user_agent' => $userAgent,
                'meta' => $meta ?: null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('account activity log failed', ['type' => $type, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * هشدار امنیتی ثبت می‌کند و در صورت فعال بودن اعلان‌ها (یا critical بودن) push می‌فرستد.
     */
    public function alert(Model $account, string $type, ?Request $request = null, array $overrides = []): ?SecurityAlert
    {
        try {
            $definition = array_merge(self::ALERTS[$type] ?? [
                'title' => $type,
                'message' => $type,
                'severity' => 'medium',
                'suggested_action' => null,
            ], $overrides);

            $token = $this->currentToken($account);
            $userAgent = $request?->userAgent() ?? $token?->user_agent;

            $alert = SecurityAlert::create([
                'account_type' => $account->getMorphClass(),
                'account_id' => $account->getKey(),
                'type' => $type,
                'title' => $definition['title'],
                'message' => $definition['message'],
                'severity' => $definition['severity'],
                'suggested_action' => $definition['suggested_action'],
                'device' => AccountSessionService::deviceLabel($token, $userAgent),
                'ip' => $request?->ip() ?? $token?->last_ip,
            ]);

            if ($alert->severity === 'critical' || $this->notificationsEnabled($account)) {
                $this->push($account, $alert);
            }

            return $alert;
        } catch (Throwable $e) {
            Log::warning('security alert failed', ['type' => $type, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** ورود ناموفق: رویداد + در صورت تکرار، هشدار (هر بازه فقط یک بار). */
    public function failedLogin(Model $account, Request $request, string $reason): void
    {
        $this->activity($account, 'login_failed', $request, 'failed', ['reason' => $reason]);

        $since = now()->subMinutes(self::FAILED_LOGIN_WINDOW_MINUTES);
        $failures = AccountActivity::where('account_type', $account->getMorphClass())
            ->where('account_id', $account->getKey())
            ->where('type', 'login_failed')
            ->where('created_at', '>=', $since)
            ->count();

        $alreadyAlerted = SecurityAlert::where('account_type', $account->getMorphClass())
            ->where('account_id', $account->getKey())
            ->where('type', 'multiple_failed_logins')
            ->where('created_at', '>=', $since)
            ->exists();

        if ($failures >= self::FAILED_LOGIN_THRESHOLD && !$alreadyAlerted) {
            $this->alert($account, 'multiple_failed_logins', $request);
        }
    }

    /**
     * دستگاه را در فهرست دستگاه‌های شناخته‌شده‌ی حساب ثبت می‌کند.
     * اگر حساب قبلاً دستگاه دیگری داشته و این دستگاه تازه است، هشدار «ورود از دستگاه جدید» می‌دهد.
     */
    public function rememberDevice(Model $account, ?string $deviceId, ?Request $request = null): void
    {
        if (!$deviceId) {
            return;
        }

        try {
            $scope = ['account_type' => $account->getMorphClass(), 'account_id' => $account->getKey()];
            $known = AccountKnownDevice::where($scope)->where('device_id', $deviceId)->first();

            if ($known) {
                $known->update(['last_seen_at' => now()]);

                return;
            }

            $hadOtherDevices = AccountKnownDevice::where($scope)->exists();
            AccountKnownDevice::create($scope + ['device_id' => $deviceId, 'first_seen_at' => now(), 'last_seen_at' => now()]);

            if ($hadOtherDevices) {
                $this->alert($account, 'new_device_login', $request);
            }
        } catch (Throwable $e) {
            Log::warning('remember device failed', ['error' => $e->getMessage()]);
        }
    }

    public function settings(Model $account): AccountSecuritySetting
    {
        return AccountSecuritySetting::firstOrCreate([
            'account_type' => $account->getMorphClass(),
            'account_id' => $account->getKey(),
        ]);
    }

    private function notificationsEnabled(Model $account): bool
    {
        $setting = AccountSecuritySetting::where('account_type', $account->getMorphClass())
            ->where('account_id', $account->getKey())
            ->first();

        return $setting === null || $setting->security_alerts_enabled;
    }

    private function push(Model $account, SecurityAlert $alert): void
    {
        if (!$account instanceof Authenticatable) {
            return;
        }

        // ارسال بعد از پاسخ، تا درخواست کاربر منتظر FCM نماند
        dispatch(function () use ($account, $alert) {
            try {
                app(FirebaseNotificationService::class)->sendToUser($account, $alert->title, $alert->message, [
                    'type' => 'security_alert',
                    'alert_id' => $alert->id,
                    'alert_type' => $alert->type,
                    'severity' => $alert->severity,
                ]);
            } catch (Throwable $e) {
                Log::warning('security alert push failed', ['alert_id' => $alert->id, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    private function currentToken(Model $account): ?PersonalAccessToken
    {
        if (!method_exists($account, 'currentAccessToken')) {
            return null;
        }

        $token = $account->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }
}
