<?php

namespace App\Filament\Resources\FaultReportResource\Pages;

use App\Filament\Resources\FaultReportResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewFaultReport extends ViewRecord
{
    protected static string $resource = FaultReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('ویرایش'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('اطلاعات کاربر')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('نام کاربر')
                            ->default('نامشخص'),
                        Components\TextEntry::make('user.phone')
                            ->label('شماره تماس')
                            ->copyable()
                            ->default('-'),
                        Components\TextEntry::make('user.melicode')
                            ->label('کد ملی')
                            ->default('-'),
                    ])
                    ->columns(3),

                Components\Section::make('اطلاعات سفارش')
                    ->schema([
                        Components\TextEntry::make('order_id')
                            ->label('شناسه سفارش')
                            ->default('-'),
                        Components\TextEntry::make('product_name')
                            ->label('نام محصول')
                            ->columnSpanFull(),
                        Components\TextEntry::make('ordered_at')
                            ->label('تاریخ سفارش')
                            ->default('-'),
                        Components\TextEntry::make('delivered_at')
                            ->label('تاریخ تحویل')
                            ->default('-'),
                        Components\TextEntry::make('paid_price')
                            ->label('مبلغ پرداختی')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state) . ' تومان' : '-'),
                        Components\TextEntry::make('order.user_address.city')
                            ->label('شهر')
                            ->default('-'),
                        Components\TextEntry::make('order.user_address.region')
                            ->label('منطقه')
                            ->default('-'),
                        Components\TextEntry::make('order.user_address.address')
                            ->label('آدرس')
                            ->columnSpanFull()
                            ->default('-'),
                    ])
                    ->columns(3),

                Components\Section::make('اطلاعات تکنسین')
                    ->schema([
                        Components\TextEntry::make('technician_code')
                            ->label('کد تکنسین')
                            ->default('-'),
                        Components\TextEntry::make('technician_name')
                            ->label('نام تکنسین')
                            ->default('-')
                            ->color(fn ($state) => $state === 'تکنسین یافت نشد' || $state === 'کد تکنسین ثبت نشده' ? 'danger' : 'success'),
                        Components\TextEntry::make('technician_phone')
                            ->label('شماره تماس تکنسین')
                            ->copyable()
                            ->default('-'),
                    ])
                    ->columns(3),

                Components\Section::make('شرح خرابی')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('توضیحات')
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('تاریخچه')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('تاریخ ثبت گزارش')
                            ->jalaliDateTime('Y/m/d H:i'),
                        Components\TextEntry::make('updated_at')
                            ->label('آخرین بروزرسانی')
                            ->jalaliDateTime('Y/m/d H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}
