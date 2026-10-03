<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/app/version?platform=android|ios|web&current_version=2.74.3[&app=user|technician]
 * درخواست بی‌صدای اپ؛ اگر نسخه‌ای تنظیم نشده باشد update_available=false برمی‌گردد.
 */
class AppVersionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => 'required|string|in:android,ios,web',
            'current_version' => 'nullable|string|max:20',
            'app' => 'nullable|string|in:user,technician',
        ]);

        $version = AppVersion::where('app', $data['app'] ?? 'user')
            ->where('platform', $data['platform'])
            ->first();

        if (!$version) {
            return response()->json([
                'success' => true,
                'data' => [
                    'latest_version' => $data['current_version'] ?? null,
                    'min_supported_version' => null,
                    'update_available' => false,
                    'force' => false,
                    'update_url' => null,
                    'release_notes' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $version->checkFor($data['current_version'] ?? null),
        ]);
    }
}
