<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * تنظیمات مالی: ساعت پایان کار (اعتبار استعلام تامین کالا)، درصدهای پیش‌فرض پیش‌پرداخت
 * و حداقل برداشت پورسانت مأمور خرید.
 */
class FinancialSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'تنظیمات';
    protected static ?string $navigationLabel = 'تنظیمات مالی و تامین کالا';
    protected static string $view = 'filament.pages.financial-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('edit-financial-settings') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'تنظیمات مالی و تامین کالا';
    }

    public function mount(): void
    {
        $this->form->fill(collect(AppSetting::DEFAULTS)->map(fn ($v, $key) => AppSetting::get($key))->all());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تامین کالا (استعلام قیمت)')
                    ->schema([
                        Forms\Components\TimePicker::make(AppSetting::BUSINESS_END_TIME)
                            ->label('ساعت پایان کار')
                            ->seconds(false)
                            ->required()
                            ->helperText('اعتبار قیمت اعلام‌شده تا این ساعت (وقت تهران) همان روز است.'),
                        Forms\Components\TextInput::make(AppSetting::PROCUREMENT_PREPAYMENT_PERCENT)
                            ->label('درصد پیش‌پرداخت تامین کالا')
                            ->numeric()->minValue(0)->maxValue(100)->suffix('%')->required()
                            ->helperText('پیش‌فرض هر استعلام؛ برای هر سفارش جداگانه هم قابل تغییر است.'),
                    ])->columns(2),

                Forms\Components\Section::make('اعزام محصول به لوپ')
                    ->schema([
                        Forms\Components\TextInput::make(AppSetting::LOOP_PREPAYMENT_PERCENT)
                            ->label('درصد پیش‌فرض پیش‌پرداخت')
                            ->numeric()->minValue(0)->maxValue(100)->suffix('%')->required()
                            ->helperText('برای هر محصول اعزام‌شده به لوپ که درصد پیش‌پرداخت آن خالی است.'),
                    ]),

                Forms\Components\Section::make('کیف پول مأمور خرید')
                    ->schema([
                        Forms\Components\TextInput::make(AppSetting::REFERRAL_MIN_WITHDRAW_AMOUNT)
                            ->label('حداقل مبلغ برداشت به شبا')
                            ->numeric()->minValue(0)->suffix('تومان')->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (array_keys(AppSetting::DEFAULTS) as $key) {
            $value = $state[$key] ?? null;
            // TimePicker مقدار «17:00:00» برمی‌گرداند
            if ($key === AppSetting::BUSINESS_END_TIME && $value) {
                $value = substr($value, 0, 5);
            }
            AppSetting::put($key, $value);
        }

        Notification::make()->title('تنظیمات ذخیره شد')->success()->send();
    }
}
