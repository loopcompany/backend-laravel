<?php

namespace App\Http\Controllers;

use App\Services\Security\AccountSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * امنیت حساب اپ تکنسین: دستگاه‌های فعال، خروج از سایر دستگاه‌ها و نشست‌ها.
 * قرارداد پاسخ‌ها طبق سند «Backend-Account-Security» است (تاریخ‌ها با toISOString).
 */
class TechnicianSecurityController extends Controller
{
    public function __construct(private readonly AccountSessionService $sessions)
    {
    }

    /** GET /api/technician/security/devices */
    public function devices(Request $request): JsonResponse
    {
        $technician = $request->user();

        $devices = $this->sessions->devices($technician, $this->currentTokenId($request))
            ->map(function (array $device) {
                /** @var PersonalAccessToken $latest */
                $latest = $device['latest'];

                return [
                    'id' => $device['key'],
                    'device_id' => $latest->device_id,
                    'platform' => $latest->platform,
                    'device_type' => $latest->device_type,
                    'device_brand' => $latest->device_brand,
                    'device_model' => $latest->device_model,
                    'os_name' => $latest->os_name,
                    'os_version' => $latest->os_version,
                    'app_version' => $latest->app_version,
                    'last_ip' => $latest->last_ip ?? $latest->ip_address,
                    'location' => null,
                    'last_active_at' => ($latest->last_used_at ?? $latest->created_at)?->toISOString(),
                    'is_current' => $device['is_current'],
                    'sessions_count' => $device['tokens']->count(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => ['devices' => $devices, 'total' => $devices->count()],
        ]);
    }

    /** DELETE /api/technician/security/devices/{deviceKey} */
    public function logoutDevice(Request $request, string $deviceKey): JsonResponse
    {
        $technician = $request->user();
        $tokens = $this->sessions->activeTokens($technician)
            ->filter(fn ($t) => $this->sessions->deviceKey($t) === $deviceKey);

        if ($tokens->isEmpty()) {
            return $this->error('دستگاه پیدا نشد.', 'DEVICE_NOT_FOUND', 404);
        }
        if ($tokens->contains('id', $this->currentTokenId($request))) {
            return $this->error('برای خروج از دستگاه فعلی از «خروج از حساب» استفاده کنید.', 'CURRENT_DEVICE', 422);
        }

        $this->revoke($request, $tokens);

        return response()->json(['success' => true, 'message' => 'حساب از این دستگاه خارج شد.']);
    }

    /** POST /api/technician/security/logout-others */
    public function logoutOthers(Request $request): JsonResponse
    {
        $technician = $request->user();
        $others = $this->sessions->activeTokens($technician)->where('id', '!=', $this->currentTokenId($request));

        $devicesCount = $others->map(fn ($t) => $this->sessions->deviceKey($t))->unique()->count();
        $this->revoke($request, $others);

        return response()->json([
            'success' => true,
            'message' => 'حساب شما از سایر دستگاه‌ها خارج شد.',
            'data' => ['revoked_sessions' => $others->count(), 'revoked_devices' => $devicesCount],
        ]);
    }

    /** GET /api/technician/security/sessions */
    public function sessions(Request $request): JsonResponse
    {
        $currentId = $this->currentTokenId($request);

        $sessions = $this->sessions->activeTokens($request->user())
            ->sortByDesc(fn ($t) => $t->last_used_at ?? $t->created_at)
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'device_id' => $t->device_id,
                'platform' => $t->platform,
                'device_type' => $t->device_type,
                'device_brand' => $t->device_brand,
                'device_model' => $t->device_model,
                'os_name' => $t->os_name,
                'os_version' => $t->os_version,
                'app_version' => $t->app_version,
                'ip_address' => $t->ip_address,
                'last_ip' => $t->last_ip,
                'created_at' => $t->created_at?->toISOString(),
                'last_used_at' => $t->last_used_at?->toISOString(),
                'expires_at' => $t->expires_at?->toISOString(),
                'status' => $t->expires_at?->isPast() ? 'expired' : 'active',
                'is_current' => $t->id === $currentId,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => ['sessions' => $sessions]]);
    }

    /** DELETE /api/technician/security/sessions/{id} */
    public function endSession(Request $request, int $id): JsonResponse
    {
        $token = $request->user()->tokens()->whereKey($id)->first();

        if (!$token) {
            return $this->error('نشست پیدا نشد.', 'SESSION_NOT_FOUND', 404);
        }
        if ($token->id === $this->currentTokenId($request)) {
            return $this->error('برای پایان نشست فعلی از «خروج از حساب» استفاده کنید.', 'CURRENT_SESSION', 422);
        }

        $this->revoke($request, collect([$token]));

        return response()->json(['success' => true, 'message' => 'نشست پایان یافت.']);
    }

    /** PUT /api/technician/security/current-device */
    public function updateCurrentDevice(Request $request): JsonResponse
    {
        $data = $request->validate(AccountSessionService::deviceRules());

        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $this->sessions->attachDevice($token, $data, $request);
        }

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------

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

    private function error(string $message, string $code, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'error_code' => $code], $status);
    }
}
