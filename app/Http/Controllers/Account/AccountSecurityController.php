<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountActivity;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Services\Security\AccountSecurityLogger;
use App\Services\Security\AccountSessionService;
use App\Services\Security\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * امنیت حساب اپ کاربر و سازمانی/شرکتی (بخش ۷ نیازمندی‌ها):
 * خلاصه، دستگاه‌ها، نشست‌ها، فعالیت‌های اخیر و هشدارهای امنیتی.
 */
class AccountSecurityController extends Controller
{
    public function __construct(
        private readonly AccountSessionService $sessions,
        private readonly AccountSecurityLogger $logger,
    ) {
    }

    /** GET /api/account/security */
    public function summary(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->ok([
            'two_factor' => $twoFactor->summary($user),
            'mobile' => ['number' => $user->phone, 'verified' => $user->phone_verified_at !== null],
            'active_devices_count' => $this->sessions->devices($user, $this->currentTokenId($request))->count(),
            'unread_alerts_count' => $this->alertsQuery($user)->whereNull('read_at')->count(),
            'sessions_supported' => true,
        ]);
    }

    // ----------------------------------------------------------------- دستگاه‌ها

    /** POST /api/account/devices/register — اپ بعد از هر ورود بی‌صدا صدا می‌زند. */
    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate(['device_id' => 'required|string|max:100'] + AccountSessionService::deviceRules());

        $token = $request->user()->currentAccessToken();
        if (!$token instanceof PersonalAccessToken) {
            return $this->fail('نشست معتبری برای ثبت دستگاه پیدا نشد.', 'NO_SESSION', 422);
        }

        $this->sessions->attachDevice($token, $data, $request);
        $this->sessions->replaceOtherTokensOfDevice($request->user(), $token);
        $this->logger->rememberDevice($request->user(), $data['device_id'], $request);

        return $this->ok(null, 'دستگاه ثبت شد.');
    }

    /** GET /api/account/devices */
    public function devices(Request $request): JsonResponse
    {
        $devices = $this->sessions->devices($request->user(), $this->currentTokenId($request))
            ->map(function (array $device) {
                /** @var PersonalAccessToken $latest */
                $latest = $device['latest'];

                return [
                    'id' => $device['key'],
                    'device_id' => $latest->device_id,
                    'platform' => $latest->platform,
                    'device_type' => $latest->device_type,
                    'device_brand' => $latest->device_brand,
                    'device_model' => $latest->device_model ?? AccountSessionService::deviceLabel($latest),
                    'os_name' => $latest->os_name,
                    'os_version' => $latest->os_version,
                    'app_version' => $latest->app_version,
                    'last_ip' => $latest->last_ip ?? $latest->ip_address,
                    'approx_location' => null,
                    'last_activity_at' => $this->iso($latest->last_used_at ?? $latest->created_at),
                    'is_current' => $device['is_current'],
                    'sessions_count' => $device['tokens']->count(),
                ];
            })
            ->values();

        return $this->ok($devices);
    }

    /** DELETE /api/account/devices/{deviceKey} */
    public function logoutDevice(Request $request, string $deviceKey): JsonResponse
    {
        $tokens = $this->sessions->activeTokens($request->user())
            ->filter(fn ($t) => $this->sessions->deviceKey($t) === $deviceKey);

        if ($tokens->isEmpty()) {
            return $this->fail('دستگاه پیدا نشد.', 'DEVICE_NOT_FOUND', 404);
        }
        if ($tokens->contains('id', $this->currentTokenId($request))) {
            return $this->fail('برای خروج از دستگاه فعلی از «خروج از حساب» استفاده کنید.', 'CURRENT_DEVICE', 422);
        }

        $label = AccountSessionService::deviceLabel($tokens->first());
        $this->revoke($request, $tokens);
        $this->logger->activity($request->user(), 'device_logout', $request, meta: ['device' => $label, 'device_key' => $deviceKey]);

        return $this->ok(null, 'حساب از این دستگاه خارج شد.');
    }

    /** POST /api/account/devices/logout-others — همه‌ی توکن‌ها به‌جز توکن همین درخواست باطل می‌شوند. */
    public function logoutOthers(Request $request): JsonResponse
    {
        $request->validate(['current_device_id' => 'nullable|string|max:100']);

        $others = $this->sessions->activeTokens($request->user())->where('id', '!=', $this->currentTokenId($request));
        $devicesCount = $others->map(fn ($t) => $this->sessions->deviceKey($t))->unique()->count();

        $this->revoke($request, $others);
        $this->logger->activity($request->user(), 'logout_other_devices', $request, meta: ['revoked_devices' => $devicesCount]);

        return $this->ok([
            'revoked_sessions' => $others->count(),
            'revoked_devices' => $devicesCount,
        ], 'حساب شما از سایر دستگاه‌ها خارج شد.');
    }

    // ----------------------------------------------------------------- نشست‌ها

    /** GET /api/account/sessions */
    public function sessions(Request $request): JsonResponse
    {
        $currentId = $this->currentTokenId($request);

        $sessions = $this->sessions->activeTokens($request->user())
            ->sortByDesc(fn ($t) => $t->last_used_at ?? $t->created_at)
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'device' => AccountSessionService::deviceLabel($t),
                'os' => AccountSessionService::osLabel($t),
                'app_version' => $t->app_version,
                'ip' => $t->last_ip ?? $t->ip_address,
                'created_at' => $this->iso($t->created_at),
                'last_activity_at' => $this->iso($t->last_used_at ?? $t->created_at),
                'expires_at' => $this->iso($t->expires_at),
                'status' => $t->expires_at?->isPast() ? 'expired' : 'active',
                'is_current' => $t->id === $currentId,
            ])
            ->values();

        return $this->ok($sessions);
    }

    /** DELETE /api/account/sessions/{id} */
    public function endSession(Request $request, int $id): JsonResponse
    {
        $token = $request->user()->tokens()->whereKey($id)->first();

        if (!$token) {
            return $this->fail('نشست پیدا نشد.', 'SESSION_NOT_FOUND', 404);
        }
        if ($token->id === $this->currentTokenId($request)) {
            return $this->fail('برای پایان نشست فعلی از «خروج از حساب» استفاده کنید.', 'CURRENT_SESSION', 422);
        }

        $this->revoke($request, collect([$token]));
        $this->logger->activity($request->user(), 'session_ended', $request, meta: ['session_id' => $id]);

        return $this->ok(null, 'نشست پایان یافت.');
    }

    // ----------------------------------------------------------------- فعالیت‌ها

    /** GET /api/account/activities?page=1 — جدیدترین اول، ۲۰تایی. */
    public function activities(Request $request): JsonResponse
    {
        $user = $request->user();

        $page = AccountActivity::where('account_type', $user->getMorphClass())
            ->where('account_id', $user->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn (AccountActivity $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'created_at' => $this->iso($a->created_at),
                'device' => $a->device,
                'os' => $a->os,
                'ip' => $a->ip,
                'result' => $a->result,
                'approx_location' => $a->approx_location,
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    // ----------------------------------------------------------------- هشدارها

    /** GET /api/account/security-alerts */
    public function alerts(Request $request): JsonResponse
    {
        $alerts = $this->alertsQuery($request->user())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (SecurityAlert $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'message' => $a->message,
                'created_at' => $this->iso($a->created_at),
                'device' => $a->device,
                'ip' => $a->ip,
                'is_read' => $a->read_at !== null,
                'severity' => $a->severity,
                'suggested_action' => $a->suggested_action,
            ]);

        return $this->ok($alerts);
    }

    /** POST /api/account/security-alerts/{id}/read */
    public function readAlert(Request $request, int $id): JsonResponse
    {
        $alert = $this->alertsQuery($request->user())->whereKey($id)->first();

        if (!$alert) {
            return $this->fail('هشدار پیدا نشد.', 'ALERT_NOT_FOUND', 404);
        }

        $alert->read_at ??= now();
        $alert->save();

        return $this->ok(null);
    }

    /** POST /api/account/security-alerts/read-all */
    public function readAllAlerts(Request $request): JsonResponse
    {
        $this->alertsQuery($request->user())->whereNull('read_at')->update(['read_at' => now()]);

        return $this->ok(null);
    }

    /** GET /api/account/security-alerts/settings */
    public function alertSettings(Request $request): JsonResponse
    {
        return $this->ok([
            'notifications_enabled' => $this->logger->settings($request->user())->security_alerts_enabled,
        ]);
    }

    /** PUT /api/account/security-alerts/settings — هشدارهای critical همیشه ارسال می‌شوند. */
    public function updateAlertSettings(Request $request): JsonResponse
    {
        $data = $request->validate(['notifications_enabled' => 'required|boolean']);

        $settings = $this->logger->settings($request->user());
        $settings->update(['security_alerts_enabled' => $data['notifications_enabled']]);
        $this->logger->activity($request->user(), 'security_info_changed', $request, meta: ['security_alerts_enabled' => $data['notifications_enabled']]);

        return $this->ok(['notifications_enabled' => $settings->security_alerts_enabled]);
    }

    // -----------------------------------------------------------------

    private function alertsQuery(User $user)
    {
        return SecurityAlert::where('account_type', $user->getMorphClass())->where('account_id', $user->getKey());
    }

    private function currentTokenId(Request $request): ?int
    {
        $token = $request->user()->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token->id : null;
    }

    private function revoke(Request $request, Collection $tokens): void
    {
        $current = $request->user()->currentAccessToken();

        $this->sessions->revoke(
            $request->user(),
            $tokens,
            $current instanceof PersonalAccessToken ? $current->device_id : null
        );
    }

    private function iso($date): ?string
    {
        return $date?->copy()->setTimezone(config('app.timezone'))->toIso8601String();
    }

    private function ok(mixed $data, ?string $message = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
        ], fn ($v) => $v !== null) + ['data' => $data]);
    }

    private function fail(string $message, string $code, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'error_code' => $code], $status);
    }
}
