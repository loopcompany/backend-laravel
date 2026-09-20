<?php

namespace App\Filament\Pages;

use App\Models\Analytics;
use App\Models\EngagementEvent;
use App\Models\LoginActivity;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Pages\Page;

class EngagementReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'گزارشات و آمار';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'تعامل و بازدید کاربران';
    protected static string $view = 'filament.pages.engagement-report';

    public string $from;
    public string $until;

    public function mount(): void
    {
        $this->from = now()->subDays(30)->toDateString();
        $this->until = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('view-dashboard') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'گزارش تعامل و بازدید کاربران';
    }

    public function refreshReport(): void
    {
        $this->validate([
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from'],
        ]);
    }

    public function getStatsProperty(): array
    {
        [$from, $until] = $this->range();
        $events = EngagementEvent::query()->whereBetween('created_at', [$from, $until]);

        return [
            'page_views' => (clone $events)->where('event_type', EngagementEvent::PAGE_VIEW)->count(),
            'downloads' => (clone $events)->where('event_type', EngagementEvent::DOWNLOAD)->count(),
            'user_logins' => LoginActivity::query()
                ->where('user_type', 'user')
                ->where('action', 'login')
                ->whereBetween('created_at', [$from, $until])
                ->count(),
            'unique_logged_users' => (clone $events)
                ->where('event_type', EngagementEvent::PAGE_VIEW)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'public_visits' => Analytics::query()
                ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
                ->sum('visits'),
        ];
    }

    public function getDailyStatsProperty(): array
    {
        [$from, $until] = $this->range();
        $rows = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()) as $date) {
            $day = $date->toDateString();
            $rows[] = [
                'date' => $day,
                'page_views' => EngagementEvent::where('event_type', EngagementEvent::PAGE_VIEW)->whereDate('created_at', $day)->count(),
                'downloads' => EngagementEvent::where('event_type', EngagementEvent::DOWNLOAD)->whereDate('created_at', $day)->count(),
                'logins' => LoginActivity::where('user_type', 'user')->where('action', 'login')->whereDate('created_at', $day)->count(),
            ];
        }

        return array_reverse($rows);
    }

    public function getTopPagesProperty(): array
    {
        [$from, $until] = $this->range();

        return EngagementEvent::query()
            ->where('event_type', EngagementEvent::PAGE_VIEW)
            ->whereBetween('created_at', [$from, $until])
            ->selectRaw('path, COUNT(*) as total')
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => ['path' => $row->path ?: '/', 'total' => (int) $row->total])
            ->all();
    }

    public function getTopDownloadsProperty(): array
    {
        [$from, $until] = $this->range();

        return EngagementEvent::query()
            ->where('event_type', EngagementEvent::DOWNLOAD)
            ->whereBetween('created_at', [$from, $until])
            ->selectRaw('path, COUNT(*) as total')
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => ['path' => $row->path ?: '/', 'total' => (int) $row->total])
            ->all();
    }

    public function getRecentEventsProperty(): iterable
    {
        [$from, $until] = $this->range();

        return EngagementEvent::query()
            ->whereBetween('created_at', [$from, $until])
            ->latest()
            ->limit(50)
            ->get();
    }

    public function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            EngagementEvent::PAGE_VIEW => 'بازدید صفحه',
            EngagementEvent::DOWNLOAD => 'دانلود',
            default => $eventType,
        };
    }

    private function range(): array
    {
        return [
            Carbon::parse($this->from)->startOfDay(),
            Carbon::parse($this->until)->endOfDay(),
        ];
    }
}
