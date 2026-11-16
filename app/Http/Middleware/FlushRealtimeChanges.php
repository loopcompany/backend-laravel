<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Models\Technician;
use App\Models\User;
use App\Services\RealtimePublisher;
use Closure;
use Illuminate\Http\Request;

class FlushRealtimeChanges
{
    public function handle(Request $request, Closure $next)
    {
        $publisher = app(RealtimePublisher::class);
        $publisher->begin();
        try {
            $response = $next($request);
            // Cover query-builder writes (e.g. mark-read and payment flags) that bypass Eloquent events.
            $readAction = preg_match('/^(get|fetch|show|index|list|validate|check|count)/i', $request->route()?->getActionMethod() ?? '')
                || preg_match('#/(detail|extra-services|summary|messages|unread-count|validate-token)$#', $request->path());
            if (!$request->isMethodSafe() && !$readAction && $response->getStatusCode() < 400 && !$request->is('api/realtime/*', 'realtime/*')) {
                $actor = $request->user();
                $topic = match (true) {
                    str_contains($request->path(), 'mark-read') => 'chat-counts',
                    str_contains($request->path(), 'chat') => 'chat',
                    str_contains($request->path(), 'ticket') => 'support',
                    str_contains($request->path(), 'wallet'), str_contains($request->path(), 'payment') => 'wallet',
                    str_contains($request->path(), 'order') => 'orders',
                    default => null,
                };
                if ($topic && ($actor instanceof User || $actor instanceof Technician)) {
                    $publisher->changed(($actor instanceof User ? 'users.' : 'technicians.') . $actor->id, [$topic]);
                }
                // Read-only detail APIs use POST in this project. Do not invalidate them.
                if ($topic === 'orders' && !str_ends_with($request->path(), '/detail')
                    && !str_contains($request->path(), 'extra-services') && !str_contains($request->path(), 'summary')) {
                    $id = $request->route('orderId') ?? $request->input('orderId') ?? $request->input('order_id');
                    $order = is_numeric($id) ? Order::find($id) : null;
                    if ($order) {
                        $publisher->changed('users.' . $order->user_id, ['orders']);
                        if ($order->technician_id) $publisher->changed('technicians.' . $order->technician_id, ['orders']);
                        $publisher->changed('admin.orders', ['orders']);
                    }
                }
            }
            return $response;
        } finally {
            $publisher->flush();
        }
    }
}
