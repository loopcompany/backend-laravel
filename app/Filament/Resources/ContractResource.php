<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'مدیریت قراردادها';
    protected static ?string $navigationLabel = 'قراردادها';
    protected static ?string $modelLabel = 'قرارداد';
    protected static ?string $pluralModelLabel = 'قراردادها';
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = false;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات قرارداد')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان قرارداد')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('فایل PDF قرارداد')
                            ->directory('contracts')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->required()
                            ->columnSpanFull()
                            ->helperText('فقط فایل‌های PDF با حداکثر حجم 10 مگابایت مجاز است.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true)
                            ->inline(false)
                            ->helperText('آیا این قرارداد فعال باشد؟'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان قرارداد')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('creator_name')
                    ->label('ثبت کننده')
                    ->toggleable()
                    ->getStateUsing(fn(Contract $record) => $record->creator_name ?? 'نامشخص'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('دانلود')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Contract $record) => $record->pdf_url)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }



    public static function canDeleteAny(): bool
    {
        return false;
    }
}

