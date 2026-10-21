<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Filament\Resources\OrderResource\Workflow\OrderWorkflowPanel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderGalleriesRelationManager extends RelationManager
{
    protected static string $relationship = 'order_galleries';
    protected static ?string $title = 'تصاویر سفارش';

    protected static ?string $modelLabel = 'تصاویر سفارش';

    protected static ?string $pluralModelLabel = 'تصاویر سفارش';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // همان دیسک و پوشه‌ای که اپ کاربر هنگام ثبت سفارش در آن آپلود می‌کند
                Forms\Components\FileUpload::make('image_path')
                    ->required()
                    ->label('تصویر')
                    ->disk('public')
                    ->directory('order')
                    ->visibility('public')
                    ->downloadable()
                    ->openable()
                    ->image(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                ,
                // نشانی با دامنه‌ی همین درخواست ساخته می‌شود تا به APP_URL وابسته نباشد؛ کلیک = اندازه‌ی کامل
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('تصویر')
                    ->getStateUsing(fn ($record) => filled($record->image_path) ? OrderWorkflowPanel::publicFileUrl($record->image_path) : null)
                    ->height(90)
                    ->url(fn ($record) => filled($record->image_path) ? OrderWorkflowPanel::publicFileUrl($record->image_path) : null, shouldOpenInNewTab: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
