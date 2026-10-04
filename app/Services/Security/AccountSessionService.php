<?php

namespace App\Services\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * نشست‌ها و دستگاه‌های یک حساب (کاربر، سازمان یا تکنسین).
 *
 * هر «نشست» یک ردیف personal_access_tokens است و هر «دستگاه» توکن‌های با device_id یکسان.
 * نشست‌های قدیمی بدون device_id هر کدام یک دستگاه جدا حساب می‌شوند.
 */
class AccountSessionService
{
    public const DEVICE_FIELDS = [
        'device_id', 'platform', 'device_type', 'device_brand',
        'device_model', 'os_name', 'os_version', 'app_version',
    ];

    /** قوانین اعتبارسنجی مشترک فیلدهای دستگاه (همه اختیاری؛ نسخه‌های قدیمی اپ آن‌ها را نمی‌فرستند). */
    public static function deviceRules(): array
    {
        return [
            'device_id' => 'nullable|string|max:100',
            'platform' => 'nullable|string|in:android,ios,web',
            'device_type' => 'nullable|string|in:phone,tablet,desktop,web,browser,tv,unknown',
            'device_brand' => 'nullable|string|max:100',
            'device_model' => 'nullable|string|max:100',
            'os_name' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:50',
        ];
    }

    /** نشست‌های فعال حساب؛ توکن‌های منقضی‌شده نمایش داده نمی‌شوند. */
    public function activeTokens(Model $account): Collection
    {
        return $account->tokens()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get();
    }

    public function deviceKey(PersonalAccessToken $token): string
    {
        return $token->device_id ?: 'session-' . $token->id;
    }

    /**
     * دستگاه‌ها: هر گروه شامل توکن‌ها، جدیدترین توکن و اینکه دستگاه فعلی است یا نه.
     *
     * @return Collection<string, array{key: string, tokens: Collection, latest: PersonalAccessToken, is_current: bool}>
     */
    public function devices(Model $account, ?int $currentTokenId): Collection
    {
        return $this->activeTokens($account)
            ->groupBy(fn (PersonalAccessToken $t) => $this->deviceKey($t))
            ->map(fn (Collection $tokens, string $key) => [
                'key' => $key,
                'tokens' => $tokens,
                'latest' => $tokens->sortByDesc(fn ($t) => $t->last_used_at ?? $t->created_at)->first(),
                'is_current' => $currentTokenId !== null && $tokens->contains('id', $currentTokenId),
            ])
            ->sortByDesc(fn ($d) => $d['latest']->last_used_at ?? $d['latest']->created_at);
    }

    /**
     * اطلاعات دستگاه را روی توکن ثبت می‌کند. ip_address و user_agent اولین بار پر می‌شوند.
     */
    public function attachDevice(PersonalAccessToken $token, array $device, Request $request): void
    {
        $data = array_filter(
            array_intersect_key($device, array_flip(self::DEVICE_FIELDS)),
            fn ($v) => $v !== null && $v !== ''
        );

        $token->forceFill($data + [
            'ip_address' => $token->ip_address ?? $request->ip(),
            'last_ip' => $request->ip(),
            'user_agent' => $token->user_agent ?? $request->userAgent(),
        ])->save();
    }

    /**
     * ورود دوباره روی همان دستگاه نشست قبلی همان دستگاه را جایگزین می‌کند
     * تا در «دستگاه‌های واردشده» یک دستگاه چند بار دیده نشود.
     */
    public function replaceOtherTokensOfDevice(Model $account, PersonalAccessToken $token): void
    {
        if (empty($token->device_id)) {
            return;
        }

        $account->tokens()
            ->where('device_id', $token->device_id)
            ->where('id', '!=', $token->id)
            ->delete();
    }

    /**
     * حذف نشست‌ها + توکن FCM همان دستگاه‌ها، تا دستگاه خارج‌شده اعلان نگیرد.
     * اگر نشست قدیمیِ تکراری با device_id دستگاه فعلی حذف شود، توکن FCM دستگاه فعلی پاک نمی‌شود.
     */
    public function revoke(Model $account, Collection $tokens, ?string $currentDeviceId): void
    {
        if ($tokens->isEmpty()) {
            return;
        }

        $deviceIds = $tokens->pluck('device_id')->filter()->unique()
            ->reject(fn ($id) => $currentDeviceId !== null && $id === $currentDeviceId);

        PersonalAccessToken::whereIn('id', $tokens->pluck('id'))->delete();

        if ($deviceIds->isNotEmpty() && method_exists($account, 'firebaseDeviceTokens')) {
            $account->firebaseDeviceTokens()->whereIn('device_id', $deviceIds->values())->delete();
        }
    }

    /** نام خوانای دستگاه، مثل «Samsung Galaxy A25». */
    public static function deviceLabel(?PersonalAccessToken $token, ?string $userAgent = null): ?string
    {
        if ($token && ($token->device_model || $token->device_brand)) {
            $brand = (string) $token->device_brand;
            $model = (string) $token->device_model;

            return trim($brand !== '' && !str_starts_with(mb_strtolower($model), mb_strtolower($brand)) ? "{$brand} {$model}" : $model) ?: null;
        }

        $userAgent ??= $token?->user_agent;
        if (!$userAgent) {
            return null;
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $device = $agent->device();
        $browser = $agent->browser();

        if ($device && $device !== 'WebKit' && $device !== '0') {
            return $device;
        }

        return $browser ?: null;
    }

    /** سیستم‌عامل خوانا، مثل «Android 14». */
    public static function osLabel(?PersonalAccessToken $token, ?string $userAgent = null): ?string
    {
        if ($token && $token->os_name) {
            return trim($token->os_name . ' ' . $token->os_version);
        }

        $userAgent ??= $token?->user_agent;
        if (!$userAgent) {
            return null;
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);
        $platform = $agent->platform();

        if (!$platform) {
            return null;
        }

        $version = $agent->version($platform);

        return trim($platform . ' ' . (is_string($version) ? str_replace('_', '.', $version) : ''));
    }
}
