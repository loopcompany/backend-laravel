<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\User;
use App\Services\RealtimeAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class RealtimeController extends Controller
{
    public function config(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User || $actor instanceof Technician, 403);
        $channel = ($actor instanceof Technician ? 'technicians.' : 'users.') . $actor->id;
        return response()->json($this->settings([$channel, 'catalog']))->header('Cache-Control', 'no-store');
    }

    public function adminConfig()
    {
        $admin = auth('admin')->user();
        $topics = RealtimeAccess::adminTopics($admin);
        return $this->settings(array_merge(array_map(fn ($topic) => 'admin.' . $topic, $topics), $admin ? ['admins.' . $admin->id] : []));
    }

    private function settings(array $channels): array
    {
        return [
            'enabled' => (bool) config('realtime.enabled'),
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('realtime.host'),
            'port' => config('realtime.port'),
            'scheme' => config('realtime.scheme'),
            'channels' => array_map(fn ($channel) => 'private-' . $channel, $channels),
        ];
    }

    public function authorizeChannel(Request $request)
    {
        abort_unless(config('realtime.enabled') && config('broadcasting.default') === 'reverb', 503);
        $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^[0-9]+\.[0-9]+$/D'],
            'channel_name' => ['required', 'string', 'max:160'],
        ]);
        return Broadcast::auth($request);
    }
}
