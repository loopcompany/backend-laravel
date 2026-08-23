<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class DeliveryReportRelationManager extends RelationManager
{
    protected static string $relationship = 'delivery_reports';
    protected static ?string $title = 'گزارش تحویل محصول';
    protected static ?string $modelLabel = 'گزارش تحویل محصول';
    protected static ?string $pluralModelLabel = 'گزارش تحویل محصول';

    // کنترل دسترسی
    public function canView(Model $record): bool
    {
        return auth('admin')->check();
    }

    // public function canCreate(): bool
    // {
    //     return auth('admin')->check();
    // }

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

                Forms\Components\TextInput::make('name')
                    ->label('نام و نام خانوادگی گیرنده')
                    ->required(),

                Forms\Components\TextInput::make('melicode')
                    ->label('کد ملی گیرنده')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('product_info')
                    ->label('اطلاعات محصول')
                    ->required(),

                Forms\Components\TextInput::make('accessories')
                    ->label('متعلقات همراه'),
                Forms\Components\TextInput::make('label_code')
                    ->label('کد برچسب محصول'),
                Forms\Components\TextInput::make('appearance_defect')
                    ->label('عیوب ظاهری'),
                Forms\Components\TextInput::make('user_description')
                    ->label('توضیحات کاربر'),
                Forms\Components\TextInput::make('technical_description')
                    ->label('توضیحات تکنسین')

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام و نام خانوادگی گیرنده')
                    ->searchable(),

                Tables\Columns\TextColumn::make('melicode')
                    ->label('کد ملی گیرنده')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_verified_at')
                    ->color(fn($record) => !empty($record->user_verified_at) ? 'success' : 'warning')
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
                // Tables\Actions\CreateAction::make()
                //     ->label('ثبت گزارش تحویل')
                //     ->hidden(function () {
                //         $hasReport = $this->getOwnerRecord()
                //             ->delivery_reports()
                //             ->exists();
                //         return $hasReport && $this->getOwnerRecord()->technician_id;
                //     })
                //     ->mutateFormDataUsing(function (array $data): array {
                //         // اضافه کردن order_id به داده‌ها
                //         $data['technician_id'] = $this->getOwnerRecord()->technician_id;
                //         $data['user_verified_at'] = Carbon::now();
                //         return $data;
                //     })
                //     ->createAnother(false)
                // ,
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
