<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class TechnicianOrderReportRelationManager extends RelationManager
{
    protected static string $relationship = 'technician_order_report';
    protected static ?string $title = 'وضعیت محصول';
    protected static ?string $modelLabel = 'وضعیت محصول';
    protected static ?string $pluralModelLabel = 'وضعیت محصول';

    // کنترل دسترسی
    public function canView(Model $record): bool
    {
        return auth('admin')->check();
    }

    public function canCreate(): bool
    {
        return auth('admin')->check();
    }

    public function canEdit(Model $record): bool
    {
        return auth('admin')->check();
    }

    public function canDelete(Model $record): bool
    {
        return auth('admin')->check();
    }

    public function canDeleteAny(): bool
    {
        return auth('admin')->check();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات تحویل دهنده')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نام و نام خانوادگی تحویل دهنده')
                            ->required(),

                        Forms\Components\TextInput::make('melicode')
                            ->label('کد ملی تحویل دهنده')
                            ->required(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('اطلاعات محصول')
                    ->schema([
                        Forms\Components\TextInput::make('product_name')
                            ->label('نام محصول تحویل داده شده')
                            ->required(),

                        Forms\Components\TextInput::make('product_brand')
                            ->label('برند محصول تحویل داده شده')
                            ->required(),
                        Forms\Components\TextInput::make('product_model')
                            ->label('مدل محصول تحویل داده شده')
                            ->required(),
                        Forms\Components\TextInput::make('product_color')
                            ->label('رنگ محصول تحویل داده شده')
                            ->required(),
                        Forms\Components\TextInput::make('product_serial_number')
                            ->label('شماره سریال محصول تحویل داده شده'),
                        Forms\Components\TextInput::make('asset_label_code')
                            ->label('کد لیبل اموال محصول'),
                        Forms\Components\Textarea::make('accessories')
                            ->label('لوازم همراه'),
                        // Forms\Components\TextInput::make('max_price')
                        //     ->numeric()
                        //     ->suffix('تومان')
                        //     ->label('بیشترین مبلغ'),
                        // Forms\Components\TextInput::make('min_price')
                        //     ->numeric()
                        //     ->suffix('تومان')
                        //     ->label('کمترین مبلغ'),
                        Forms\Components\TextInput::make('product_password')
                            ->label('رمز عبور دستگاه'),
                        Forms\Components\Textarea::make('user_reported_issues')
                            ->label('ایرادات گزارش شده توسط کاربر'),
                        Forms\Components\Textarea::make('technician_reported_issues')
                            ->label('ایرادات گزارش شده توسط تکنسین'),
                        Forms\Components\Textarea::make('technician_observed_issues')
                            ->label('ایرادات ظاهری مشاهده شده توسط تکنسین'),
                        Forms\Components\Textarea::make('user_requested_services')
                            ->label('توضیحات / خدمات درخواستی کاربر'),

                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام و نام خانوادگی تحویل دهنده')
                    ->searchable(),

                Tables\Columns\TextColumn::make('melicode')
                    ->label('کد ملی تحویل دهنده')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('نام محصول'),
                Tables\Columns\TextColumn::make('user_confirmed_at')
                    ->color(fn($record) => !empty($record->user_confirmed_at) ? 'success' : 'warning')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'تایید شده در ' . \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i:s') : 'تأیید نشده')
                    ->label('وضعیت'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('ثبت جزئيات محصول')
                    ->hidden(function () {
                        $hasReport = $this->getOwnerRecord()
                            ->technician_order_report()
                            ->exists();
                        return $hasReport && $this->getOwnerRecord()->technician_id;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        // اضافه کردن order_id به داده‌ها
                        $data['technician_id'] = $this->getOwnerRecord()->technician_id;
                        return $data;
                    })
                    ->createAnother(false)
                ,
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('ویرایش'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب شده‌ها'),
                ]),
            ])
            ->emptyStateHeading('اطلاعاتی ثبت نشده')
            ->emptyStateDescription('هنوز اطلاعات محصول و تحویل دهنده ثبت نشده است.')
            ->emptyStateIcon('heroicon-o-star')
            ->defaultSort('created_at', 'desc');
    }
}
