<?php

namespace App\Filament\Pages;

use App\Services\BusinessExportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;

class BusinessExports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'گزارش‌های بیزنسی';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'خروجی‌های Excel';
    protected static string $view = 'filament.pages.business-exports';

    public string $report = 'orders';
    public ?array $dateRange = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('from')->label('از تاریخ')->jalali(),
                DatePicker::make('until')->label('تا تاریخ')->jalali(),
            ])
            ->columns(2)
            ->statePath('dateRange');
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
            'dateRange.from' => ['nullable', 'date'],
            'dateRange.until' => ['nullable', 'date', 'after_or_equal:dateRange.from'],
        ]);

        $dates = $this->form->getState();

        return app(BusinessExportService::class)->download($this->report, $dates['from'] ?? null, $dates['until'] ?? null);
    }
}
