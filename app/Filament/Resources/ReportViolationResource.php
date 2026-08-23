<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportViolationResource\Pages;
use App\Models\ReportViolation;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportViolationResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = ReportViolation::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'گزارش تخلفات';
    protected static ?string $title = 'گزارش تخلفات';
    protected static ?string $modelLabel = 'گزارش تخلف';
    protected static ?string $pluralModelLabel = 'گزارش تخلفات';

    protected static function getViewPermission(): string
    {
        return 'view-report-violations';
    }


    protected static function getDeletePermission(): string
    {
        return 'delete-report-violations';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات گزارش')->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->label('کاربر')
                        ->required(),
                    Forms\Components\TextInput::make('subject')
                        ->label('موضوع')
                        ->nullable(),
                    Forms\Components\TextInput::make('name')
                        ->label('نام')
                        ->nullable(),
                    Forms\Components\TextInput::make('date')
                        ->label('تاریخ')
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label('مبلغ')

                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->label('توضیحات')
                        ->rows(5)
                        ->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('موضوع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->numeric()
                    ->suffix('تومان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('تاریخ'),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->jalaliDate()
                    ->label('تاریخ ثبت'),
            ])
            ->filters([
                // add filters if needed
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // relations
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportViolations::route('/'),
            'create' => Pages\CreateReportViolation::route('/create'),
            'edit' => Pages\EditReportViolation::route('/{record}/edit'),
        ];
    }
}
