<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrganizationRelationManager extends RelationManager
{
    protected static string $relationship = 'organization';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات سازمان')
                    ->schema([
                        Forms\Components\TextInput::make('organization_name')
                            ->label('نام سازمان')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('organization_code')
                            ->label('کد سازمان')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('organization_phone')
                            ->label('تلفن سازمان')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('organization_address')
                            ->label('آدرس سازمان')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('اطلاعات مدیر')
                    ->schema([
                        Forms\Components\TextInput::make('manager_full_name')
                            ->label('نام و نام خانوادگی مدیر')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('manager_national_code')
                            ->label('کد ملی مدیر')
                            ->maxLength(10)
                            ->minLength(10)
                            ->regex('/^[0-9]{10}$/'),
                        Forms\Components\FileUpload::make('profile_image')
                            ->label('تصویر پروفایل')
                            ->image()
                            ->directory('organizations')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('organization_name')
            ->columns([
                Tables\Columns\TextColumn::make('organization_name')
                    ->label('نام سازمان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization_code')
                    ->label('کد سازمان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization_phone')
                    ->label('تلفن')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manager_full_name')
                    ->label('نام مدیر')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('profile_image')
                    ->label('تصویر پروفایل')
                    ->circular(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->emptyStateHeading('هیچ سازمانی یافت نشد')
            ->emptyStateDescription('برای این کاربر هنوز سازمانی ثبت نشده است.');
    }
}
