<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EngagementEvent extends Model
{
    public const PAGE_VIEW = 'page_view';
    public const DOWNLOAD = 'download';

    protected $fillable = [
        'event_type',
        'user_type',
        'user_id',
        'route_name',
        'path',
        'ip_address',
        'user_agent',
        'referrer',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function scopeBetweenDates(Builder $query, $from, $until): Builder
    {
        return $query->whereBetween('created_at', [$from, $until]);
    }

    public static function recordFromRequest(string $eventType, $request, array $metadata = []): void
    {
        try {
            $user = $request->user();
            $userType = null;

            if ($user) {
                $userType = $user->account_type && $user->account_type !== 'individual'
                    ? 'organization'
                    : 'user';
            } elseif (auth('technician')->check()) {
                $user = auth('technician')->user();
                $userType = 'technician';
            }

            static::create([
                'event_type' => $eventType,
                'user_type' => $userType ?? 'guest',
                'user_id' => $user?->getKey(),
                'route_name' => $request->route()?->getName(),
                'path' => '/' . ltrim($request->path(), '/'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->headers->get('referer'),
                'metadata' => $metadata ?: null,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
