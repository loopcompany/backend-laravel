<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationContractRequestResource\Pages;
use App\Filament\Resources\OrganizationContractRequestResource\RelationManagers\GalleryRelationManager;
use App\Models\OrganizationContractRequest;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrganizationContractRequestResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = OrganizationContractRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'درخواست توافق نامه شرکت - سازمان';
    protected static ?string $modelLabel = 'درخواست قرارداد سازمان';
    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static function getViewPermission(): string
    {
        return 'view-contract-request';
    }


    protected static function getEditPermission(): string
    {
        return 'edit-contract-request';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-contract-request';
    }

    protected static ?string $pluralModelLabel = 'درخواست توافق نامه شرکت - سازمان';

    public static function form(Form $form): Form
    {
        $isEdit = $form->getOperation() === 'edit';

        return $form
            ->schema([

                // فیلد سازمان
                $isEdit
                ? Forms\Components\TextInput::make('organization_id')
                    ->label('سازمان')
                    ->disabled()
                    ->formatStateUsing(fn($state, $record) => $record->organization?->organization_name ?? '—')
                : Forms\Components\Select::make('organization_id')
                    ->label('سازمان')
                    ->relationship('organization', 'organization_name')
                    ->searchable()
                    ->required(),

                // فیلد کاربر
                $isEdit
                ? Forms\Components\TextInput::make('user_id')
                    ->label('کاربر')
                    ->disabled()
                    ->formatStateUsing(fn($state, $record) => $record->user?->name ?? '—')
                : Forms\Components\Select::make('user_id')
                    ->label('کاربر')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\Section::make('وضعیت')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('وضعیت درخواست')
                            ->options([
                                0 => 'در انتظار بررسی',
                                1 => 'درخواست تأیید شده',
                                2 => 'درخواست رد شده',
                                3 => 'قرارداد تایید شده',
                                4 => 'قرارداد رد شده',
                            ])
                            ->default(0)
                            ->required(),
                    ])
                    ->reactive()
                ,

                Forms\Components\Textarea::make('need_docs')
                    ->label('مدارک مورد نیاز')
                    ->required(fn(Forms\Get $get): bool => ($get('status') == 1 || $get('status') == 3))
                    ->visible(fn(Forms\Get $get): bool => ($get('status') == 1 || $get('status') == 3))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('information')
                    ->label('اطلاعات ارسال شده')
                    ->visible(fn(Forms\Get $get): bool => ($get('status') == 1 || $get('status') == 3))
                    ->disabledOn('edit')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('reject_reason')
                    ->label('دلیل رد')
                    ->placeholder('در صورت رد کردن درخواست سازمان، دلیل را وارد کنید...')
                    ->required(fn(Forms\Get $get): bool => ($get('status') == 2))
                    ->visible(fn(Forms\Get $get): bool => ($get('status') == 2))
                    ->maxLength(191)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('reject_contract_reason')
                    ->label('دلیل رد')
                    ->placeholder('در صورت رد کردن قرارداد اپلود شده توسط سازمان، دلیل را وارد کنید...')
                    ->required(fn(Forms\Get $get): bool => ($get('status') == 4))
                    ->visible(fn(Forms\Get $get): bool => ($get('status') == 4))
                    ->maxLength(191)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('contract_file_path')
                    ->label('فایل قرارداد خام')
                    ->visible(fn(Forms\Get $get): bool => ($get('status') == 1 || $get('status') == 3)),
                Forms\Components\TextInput::make('title')
                    ->label('عنوان قرارداد')
                    ->placeholder('عنوان قرارداد را در این قسمت وارد کنید')
                    ->visible(fn(Forms\Get $get): bool => ($get('status') == 1 || $get('status') == 3))
                    ->maxLength(191),
                Forms\Components\FileUpload::make('signed_contract_file_path')
                    ->label('فایل قرارداد امضا شده')
                    ->downloadable()
                    ->openable()
                    ->disabledOn('edit')
                    ->visible(fn(Forms\Get $get): bool => ($get('status') == 1 || $get('status') == 3)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه درخواست')
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.organization_code')
                    ->label('کد سازمانی / شرکتی')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.organization_name')
                    ->label('سازمان / شرکت')
                    ->searchable(),

                Tables\Columns\TextColumn::make('organization.manager_full_name')
                    ->label('نام و نام خانوادگی مدیر عامل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.user.phone')
                    ->label('موبایل مدیرعامل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.agent_name')
                    ->label('نام نماینده مدیر عامل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.agent_phone')
                    ->label('شماره موبایل نماینده مدیرعامل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.business_name')
                    ->label('نام تجاری')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.history')
                    ->label('سابقه فعالیت نماینده سال/ماه')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        0 => 'در انتظار',
                        1 => 'درخواست تأیید شده',
                        2 => 'درخواست رد شده',
                        3 => 'قرارداد تأیید شده',
                        4 => 'قرارداد رد شده',
                        default => 'نامشخص',
                    })
                    ->colors([
                        'warning' => ['0'],
                        'success' => ['1', '3'],
                        'danger' => ['2', '4'],
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان درخواست')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاریخ بروزرسانی')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            GalleryRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationContractRequests::route('/'),
            'create' => Pages\CreateOrganizationContractRequest::route('/create'),
            'edit' => Pages\EditOrganizationContractRequest::route('/{record}/edit'),
        ];
    }
}
