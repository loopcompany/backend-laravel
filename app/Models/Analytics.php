<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'visits',
        'unique_visits', 
        'page_views',
        'user_agents',
        'referrers'
    ];

    protected $casts = [
        'date' => 'date',
        'user_agents' => 'array',
        'referrers' => 'array'
    ];

    /**
     * ثبت بازدید جدید
     */
    public static function recordVisit(string $userAgent = null, string $referrer = null): void
    {
        $today = today();
        
        $analytics = self::firstOrCreate(
            ['date' => $today],
            [
                'visits' => 0,
                'unique_visits' => 0,
                'page_views' => 0,
                'user_agents' => [],
                'referrers' => []
            ]
        );

        $analytics->increment('visits');
        $analytics->increment('page_views');

        // ذخیره user agent
        if ($userAgent) {
            $userAgents = $analytics->user_agents ?? [];
            if (!in_array($userAgent, $userAgents)) {
                $userAgents[] = $userAgent;
                $analytics->update(['user_agents' => $userAgents]);
                $analytics->increment('unique_visits');
            }
        }

        // ذخیره referrer
        if ($referrer) {
            $referrers = $analytics->referrers ?? [];
            if (!in_array($referrer, $referrers)) {
                $referrers[] = $referrer;
                $analytics->update(['referrers' => $referrers]);
            }
        }
    }

    /**
     * دریافت آمار امروز
     */
    public static function getTodayStats(): array
    {
        $today = self::where('date', today())->first();
        
        return [
            'visits' => $today->visits ?? 0,
            'unique_visits' => $today->unique_visits ?? 0,
            'page_views' => $today->page_views ?? 0
        ];
    }

    /**
     * دریافت آمار هفت روز گذشته
     */
    public static function getLastSevenDaysStats(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $analytics = self::where('date', $date)->first();
            $data[] = $analytics->visits ?? 0;
        }
        return $data;
    }
}