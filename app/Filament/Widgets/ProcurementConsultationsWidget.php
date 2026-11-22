<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProcurementConsultationResource;
use App\Models\ProcurementConsultation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Pending consultation requests, refreshed by the authorized realtime channel. */
class ProcurementConsultationsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ProcurementConsultationResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('درخواست‌های مشاوره تأمین کالا / محصول')
            ->query(ProcurementConsultation::query()->with('user')
                ->where('status', 'pending')->when(!static::canView(), fn ($query) => $query->whereRaw('1 = 0')))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('نام کاربر'),
                Tables\Columns\TextColumn::make('user.last_name')->label('نام خانوادگی'),
                Tables\Columns\TextColumn::make('user.code')->label('کد کاربری'),
                Tables\Columns\TextColumn::make('created_at')->label('زمان درخواست')->jalaliDateTime(),
                Tables\Columns\TextColumn::make('title')->label('کالا / محصول'),
                Tables\Columns\TextColumn::make('budget_rial')->label('محدوده هزینه (ریال)')->numeric(),
                Tables\Columns\TextColumn::make('user.phone')->label('موبایل'),
                Tables\Columns\TextColumn::make('address')->label('آدرس')->wrap()->limit(80),
                Tables\Columns\TextColumn::make('description')->label('توضیحات')->wrap()->limit(100),
            ])
            ->headerActions([
                Tables\Actions\Action::make('all')->label('همه درخواست‌ها')
                    ->url(fn () => ProcurementConsultationResource::getUrl()),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }
}
