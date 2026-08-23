<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EducationRegisterationResource\Pages;
use App\Filament\Resources\EducationRegisterationResource\RelationManagers;
use App\Models\EducationRegisteration;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EducationRegisterationResource extends Resource
{
    // use HasFilamentPermissions;

    protected static ?string $model = EducationRegisteration::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'ثبت نام دوره های آموزشی';

    protected static ?string $modelLabel = 'ثبت نام دوره آموزشی';

    protected static ?string $pluralModelLabel = 'ثبت نام دوره های آموزشی';
    protected static bool $shouldRegisterNavigation = false;
    // protected static function getViewPermission(): string
    // {
    //     return 'view-education-registerations';
    // }

    // protected static function getEditPermission(): string
    // {
    //     return 'edit-education-registerations';
    // }

    // protected static function getDeletePermission(): string
    // {
    //     return 'delete-education-registrations';
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات کاربر')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('کاربر')
                            ->relationship('user', 'phone')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('اطلاعات تماس')
                    ->schema([
                        Forms\Components\TextInput::make('telephone')
                            ->label('تلفن ثابت')
                            ->tel()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('phone')
                            ->label('شماره موبایل')
                            ->tel()
                            ->required()
                            ->maxLength(191)
                            ->regex('/^09[0-9]{9}$/'),
                    ])->columns(2),

                Forms\Components\Section::make('آدرس و توضیحات')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('آدرس')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('شماره کاربر')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام کاربر')
                    ->searchable()
                    ->formatStateUsing(fn($record) => $record->user ? $record->user->name . ' ' . $record->user->last_name : '-'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('موبایل')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('telephone')
                    ->label('تلفن ثابت')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->label('آدرس')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->jalali()
                            ->label('از تاریخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->jalali()
                            ->label('تا تاریخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEducationRegisterations::route('/'),
            'create' => Pages\CreateEducationRegisteration::route('/create'),
            'edit' => Pages\EditEducationRegisteration::route('/{record}/edit'),
        ];
    }
}
