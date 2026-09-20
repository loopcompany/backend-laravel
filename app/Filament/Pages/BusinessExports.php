<?php

namespace App\Filament\Pages;

use App\Services\BusinessExportService;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;

class BusinessExports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'گزارش‌های بیزنسی';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'خروجی‌های Excel';
    protected static string $view = 'filament.pages.business-exports';

    public string $report = 'orders';
    public ?string $from = null;
    public ?string $until = null;

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
        return 'خروجی‌های بیزنسی';
    }

    public function getReportOptionsProperty(): array
    {
        return BusinessExportService::reports();
    }

    public function generate()
    {
        $this->validate([
            'report' => ['required', Rule::in(array_keys(BusinessExportService::reports()))],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return app(BusinessExportService::class)->download($this->report, $this->from, $this->until);
    }
}
