<?php

namespace App\Http\Controllers;

use App\Http\Requests\FirebaseDeviceTokenRequest;
use App\Models\FirebaseDeviceToken;
use Illuminate\Http\JsonResponse;

class FirebaseDeviceTokenController extends Controller
{
    public function store(FirebaseDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // A token belongs to one current app installation. If the user logs
        // out and another account logs in on that device, reassign the token.
        $deviceToken = FirebaseDeviceToken::updateOrCreate(
            ['token_hash' => hash('sha256', $data['token'])],
            [
                'token' => $data['token'],
                'tokenable_type' => $user::class,
                'tokenable_id' => $user->getKey(),
                'platform' => $data['platform'],
                'device_id' => $data['device_id'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'توکن اعلان با موفقیت ثبت شد.',
            'data' => [
                'id' => $deviceToken->id,
                'platform' => $deviceToken->platform,
            ],
        ], 200);
    }

    public function destroy(FirebaseDeviceTokenRequest $request): JsonResponse
    {
        $deleted = FirebaseDeviceToken::query()
            ->where('token_hash', hash('sha256', $request->validated('token')))
            ->where('tokenable_type', $request->user()::class)
            ->where('tokenable_id', $request->user()->getKey())
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'توکن اعلان حذف شد.',
            'data' => ['deleted' => $deleted > 0],
        ]);
    }
}
