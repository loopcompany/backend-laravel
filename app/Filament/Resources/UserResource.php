<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Region;
use App\Models\User;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Morilog\Jalali\Jalalian;

class UserResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'کاربران';

    protected static ?string $modelLabel = 'کاربر';

    protected static ?string $pluralModelLabel = 'کاربران';

    protected static function getViewPermission(): string
    {
        return 'view-user';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-user';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-user';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-user';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات شخصی')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(191)
                            ->label('نام')
                            ->default('کاربر'),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(191)
                            ->label('نام خانوادگی')
                            ->default('لوپ'),
                        Forms\Components\TextInput::make('code')
                            ->label('کدکاربری')
                            ->required(fn(Get $get) => $get('is_special'))
                            ->maxLength(191)
                            ->disabled(fn(Get $get) => !$get('is_special'))
                            ->dehydrated(),
                        Forms\Components\Toggle::make('is_special')
                            ->label('اکانت ویژه')
                            ->reactive()
                            ->default(false),
                        Forms\Components\TextInput::make('melicode')
                            ->label('کد ملی')
                            ->maxLength(10)
                            ->minLength(10),
                        DatePicker::make('birth_date')
                            ->jalali()
                            ->label('تاریخ تولد')
                            ->formatStateUsing(function ($state) {
                                if (!$state) {
                                    return null;
                                }

                                return Jalalian::fromFormat('Y-m-d', $state)
                                    ->toCarbon()
                                    ->format('Y-m-d');
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (!$state) {
                                    return null;
                                }

                                return Jalalian::fromDateTime($state)
                                    ->format('Y-m-d');
                            })
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('اطلاعات تماس')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->label('شماره تلفن')
                            ->regex('/^09[0-9]{9}$/'),
                        Forms\Components\TextInput::make('mobile_number')
                            ->tel()
                            ->maxLength(191)
                            ->label('شماره موبایل'),
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(191)
                            ->label('شماره تلفن ثابت'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(191)
                            ->label('ایمیل'),
                    ])->columns(2),

                Forms\Components\Section::make('آدرس و موقعیت')
                    ->schema([
                        Forms\Components\TextInput::make('city')
                            ->maxLength(191)
                            ->label('شهر'),
                        Forms\Components\Select::make('region_id')
                            ->options(function () {
                                return Region::pluck('title', 'id'); // ['id' => 'title']
                            })
                            ->required()
                            ->label('منطقه'),
                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(10)
                            ->label('کد پستی'),
                        Forms\Components\Textarea::make('home_address')
                            ->maxLength(500)
                            ->label('آدرس منزل')
                            ->rows(2),
                        Forms\Components\Textarea::make('work_address')
                            ->maxLength(500)
                            ->label('آدرس محل کار')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('اطلاعات مالی')
                    ->schema([
                        Forms\Components\TextInput::make('card_number')
                            ->maxLength(16)
                            ->label('شماره کارت'),
                        Forms\Components\TextInput::make('sheba_number')
                            ->maxLength(24)
                            ->label('شماره شبا')
                            ->prefix('IR'),
                        Forms\Components\TextInput::make('wallet')
                            ->numeric()
                            ->label('موجودی کیف پول')
                            ->default(0)
                            ->suffix('تومان'),
                    ])->columns(2),

                Forms\Components\Section::make('کدهای معرف')
                    ->schema([
                        Forms\Components\TextInput::make('referral_code')
                            ->maxLength(191)
                            ->label('کد معرف (خودکار تولید می‌شود)')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('other_referral_code')
                            ->maxLength(191)
                            ->label('ثبت نام شده با معرف:'),
                    ])->columns(2),

                Forms\Components\Section::make('تنظیمات سیستمی')
                    ->schema([
                        Forms\Components\Select::make('account_type')
                            ->label('نوع حساب کاربری')
                            ->options([
                                'individual' => 'فردی',
                                'g_organization' => "سازمانی دولتی",
                                's_g_organization' => "سازمانی نیمه دولتی",
                                'company' => "شرکت خصوصی",
                                // 'organization' => "سازمانی",
                            ])
                            ->default('individual')
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->maxLength(191)
                            ->label('رمز عبور (اختیاری - خودکار تولید می‌شود)')
                            ->confirmed()
                            ->hiddenOn('edit')
                            ->helperText('در صورت خالی گذاشتن، رمز عبور امن به صورت خودکار تولید و به کاربر ارسال می‌شود.'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->maxLength(191)
                            ->label('تکرار رمز عبور')
                            ->hiddenOn('edit'),
                        Forms\Components\Toggle::make('has_access')
                            ->label('دسترسی فعال')
                            ->helperText('اگر فعال نباشد یعنی کاربر بلاک شده است')
                            ->default(true),

                        Forms\Components\FileUpload::make('profile_photo_path')
                            ->label('عکس پروفایل')
                            ->image()
                            ->directory('profile-photos'),
                    ])->columns(2),

                Forms\Components\Section::make('اطلاعات سازمان')
                    ->schema([
                        Forms\Components\TextInput::make('organization.organization_name')
                            ->label('نام حقوقی شرکت')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('organization.business_name')
                            ->label('نام تجاری')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('organization.organization_code')
                            ->label('کد سازمان')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('organization.organization_phone')
                            ->label('تلفن سازمان')
                            ->tel()
                            ->maxLength(191),
                        Forms\Components\Textarea::make('organization.organization_address')
                            ->label('آدرس سازمان')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('organization.manager_full_name')
                            ->label('نام و نام خانوادگی مدیر')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('organization.agent_name')
                            ->label('نام و نام خانوادگی نماینده مدیر عامل')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('organization.agent_phone')
                            ->label('شماره موبایل نماینده مدیرعامل')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('organization.history')
                            ->label('سابقه فعالیت نماینده سال/ماه')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('organization.manager_national_code')
                            ->label('شناسه ملی')
                            ->maxLength(10)
                            ->minLength(10),
                        Forms\Components\FileUpload::make('organization.profile_image')
                            ->label('تصویر پروفایل سازمان')
                            ->image()
                            ->directory('organizations')
                            ->columnSpanFull(),

                        Forms\Components\Section::make('وضعیت تایید')
                            ->schema([
                                Forms\Components\Select::make('organization.profile_status')
                                    ->label('وضعیت پروفایل')
                                    ->options([
                                        'pending' => 'در انتظار تایید',
                                        'approved' => 'تایید شده',
                                        'rejected' => 'رد شده',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state === 'approved') {
                                            $set('organization.profile_approved_at', now());
                                            $set('organization.profile_rejection_reason', null);
                                        } else {
                                            $set('organization.profile_approved_at', null);
                                        }
                                    }),


                                Forms\Components\Textarea::make('organization.profile_rejection_reason')
                                    ->label('دلیل رد پروفایل')
                                    ->rows(2)
                                    ->visible(fn(Forms\Get $get) => $get('organization.profile_status') === 'rejected')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('organization.contract_status')
                                    ->label('وضعیت قرارداد')
                                    ->options([
                                        'not_uploaded' => 'آپلود نشده',
                                        'pending' => 'در انتظار تایید',
                                        'approved' => 'تایید شده',
                                        'rejected' => 'رد شده',
                                    ])
                                    ->default('not_uploaded')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state === 'approved') {
                                            $set('organization.contract_approved_at', now());
                                            $set('organization.contract_rejection_reason', null);
                                        } else {
                                            $set('organization.contract_approved_at', null);
                                        }
                                    }),


                                Forms\Components\Textarea::make('organization.contract_rejection_reason')
                                    ->label('دلیل رد قرارداد')
                                    ->rows(2)
                                    ->visible(fn(Forms\Get $get) => $get('organization.contract_status') === 'rejected')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsed(false),
                    ])
                    ->columns(2)
                    ->visible(fn(Forms\Get $get): bool => $get('account_type') !== 'individual')
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('کدکاربری')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('نام خانوادگی')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\BadgeColumn::make('is_online')
                    ->colors([
                        'success' => '1',
                        'danger' => '0',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '0' => 'آفلاین',
                        '1' => 'آنلاین',
                        default => $state,
                    })
                    ->label('وضعیت آنلاین بودن'),
                Tables\Columns\BadgeColumn::make('account_type')
                    ->label('نوع حساب')
                    ->colors([
                        'secondary' => 'individual',
                        'success' => 'g_organization',
                        'primary' => 's_g_organization',
                        'warning' => 'company',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'individual' => 'فردی',
                        'g_organization' => "سازمانی دولتی",
                        's_g_organization' => "سازمانی نیمه دولتی",
                        'company' => "شرکت خصوصی",
                        // 'organization' => "سازمانی",
                        default => $state,
                    })
                    ->sortable(),

                // وضعیت پروفایل سازمان
                Tables\Columns\BadgeColumn::make('organization.profile_status')
                    ->label('وضعیت پروفایل')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'pending' => 'در انتظار تایید',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                        default => '-',
                    })
                    ->visible(fn($record) => $record && $record->account_type !== 'individual')
                    ->sortable(),

                // وضعیت قرارداد سازمان
                Tables\Columns\BadgeColumn::make('organization.contract_status')
                    ->label('وضعیت قرارداد')
                    ->colors([
                        'gray' => 'not_uploaded',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'not_uploaded' => 'آپلود نشده',
                        'pending' => 'در انتظار تایید',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                        default => '-',
                    })
                    ->visible(fn($record) => $record && $record->account_type !== 'individual')
                    ->sortable(),

                Tables\Columns\IconColumn::make('phone_verified_at')
                    ->label('تایید تلفن')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => !is_null($record->phone_verified_at)),
                Tables\Columns\IconColumn::make('is_special')
                    ->label('اکانت ویژه')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-user')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('melicode')
                    ->label('کد ملی')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city')
                    ->label('شهر')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wallet')
                    ->label('کیف پول')
                    ->numeric()
                    ->sortable()
                    ->suffix(' تومان')
                    ->color('success'),
                Tables\Columns\IconColumn::make('has_access')
                    ->label('دسترسی')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاریخ ویرایش')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('دسته‌بندی')
                    ->multiple()
                    ->options(User::categories())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->inCategories($data['values'] ?? []);
                    }),

                Tables\Filters\TernaryFilter::make('is_special')
                    ->label('اکانت ویژه')
                    ->trueLabel('ویژه')
                    ->falseLabel('عادی'),

                Tables\Filters\SelectFilter::make('account_type')
                    ->label('نوع حساب')
                    ->multiple()
                    ->options([
                        'individual' => 'فردی',
                        // 'organization' => 'سازمان',
                        'company' => 'شرکت',
                        'g_organization' => 'سازمانی دولتی',
                        's_g_organization' => 'سازمانی نیمه دولتی',
                    ]),

                Tables\Filters\SelectFilter::make('organization.profile_status')
                    ->label('وضعیت پروفایل سازمان')
                    ->options([
                        'pending' => 'در انتظار تایید',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('organization', function (Builder $q) use ($data) {
                            $q->where('profile_status', $data['value']);
                        });
                    }),

                Tables\Filters\SelectFilter::make('organization.contract_status')
                    ->label('وضعیت قرارداد سازمان')
                    ->options([
                        'not_uploaded' => 'آپلود نشده',
                        'pending' => 'در انتظار تایید',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('organization', function (Builder $q) use ($data) {
                            $q->where('contract_status', $data['value']);
                        });
                    }),

                Tables\Filters\TernaryFilter::make('phone_verified_at')
                    ->label('تایید تلفن')
                    ->nullable(),
                Tables\Filters\TernaryFilter::make('has_access')
                    ->label('وضعیت دسترسی')
                    ->boolean(),
                Tables\Filters\SelectFilter::make('city')
                    ->label('شهر')
                    ->options(function () {
                        return User::whereNotNull('city')
                            ->distinct()
                            ->pluck('city', 'city')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('منطقه')
                    ->relationship('userRegion', 'title')
                    ->searchable()
                    ->preload(),

            ])
            ->actions([
                // Actions مربوط به سازمان
                // Tables\Actions\Action::make('approve_profile')
                //     ->label('تایید پروفایل')
                //     ->icon('heroicon-o-check-circle')
                //     ->color('success')
                //     ->visible(
                //         fn($record) =>
                //         $record->account_type !== 'individual' &&
                //         $record->organization &&
                //         $record->organization->profile_status === 'pending'
                //     )
                //     ->requiresConfirmation()
                //     ->modalHeading('تایید پروفایل سازمان')
                //     ->modalDescription('آیا از تایید پروفایل این سازمان اطمینان دارید؟')
                //     ->modalSubmitActionLabel('تایید')
                //     ->action(function ($record) {
                //         $record->organization->update([
                //             'profile_status' => 'approved',
                //             'profile_approved_at' => now(),
                //             'profile_rejection_reason' => null,
                //         ]);

                //         \Filament\Notifications\Notification::make()
                //             ->title('پروفایل سازمان تایید شد')
                //             ->success()
                //             ->send();
                //     }),

                // Tables\Actions\Action::make('reject_profile')
                //     ->label('رد پروفایل')
                //     ->icon('heroicon-o-x-circle')
                //     ->color('danger')
                //     ->visible(
                //         fn($record) =>
                //         $record->account_type !== 'individual' &&
                //         $record->organization &&
                //         $record->organization->profile_status !== 'rejected'
                //     )
                //     ->form([
                //         Forms\Components\Textarea::make('profile_rejection_reason')
                //             ->label('دلیل رد')
                //             ->required()
                //             ->rows(3)
                //             ->placeholder('لطفا دلیل رد پروفایل را وارد کنید...')
                //     ])
                //     ->modalHeading('رد پروفایل سازمان')
                //     ->modalSubmitActionLabel('رد پروفایل')
                //     ->action(function ($record, array $data) {
                //         $record->organization->update([
                //             'profile_status' => 'rejected',
                //             'profile_approved_at' => null,
                //             'profile_rejection_reason' => $data['profile_rejection_reason'],
                //         ]);

                //         \Filament\Notifications\Notification::make()
                //             ->title('پروفایل سازمان رد شد')
                //             ->danger()
                //             ->send();
                //     }),

                // Tables\Actions\Action::make('approve_contract')
                //     ->label('تایید قرارداد')
                //     ->icon('heroicon-o-document-check')
                //     ->color('success')
                //     ->visible(
                //         fn($record) =>
                //         $record->account_type !== 'individual' &&
                //         $record->organization &&
                //         $record->organization->contract_status === 'pending'
                //     )
                //     ->requiresConfirmation()
                //     ->modalHeading('تایید قرارداد سازمان')
                //     ->modalDescription('آیا از تایید قرارداد این سازمان اطمینان دارید؟')
                //     ->modalSubmitActionLabel('تایید')
                //     ->action(function ($record) {
                //         $record->organization->update([
                //             'contract_status' => 'approved',
                //             'contract_approved_at' => now(),
                //             'contract_rejection_reason' => null,
                //         ]);

                //         // بروزرسانی آخرین قرارداد در جدول organization_contracts
                //         $latestContract = \App\Models\OrganizationContract::latestForOrganization($record->organization->id)->first();
                //         if ($latestContract) {
                //             $latestContract->update([
                //                 'status' => 'approved',
                //                 'reviewed_at' => now(),
                //                 'reviewed_by' => auth('admin')->id(),
                //             ]);
                //         }

                //         \Filament\Notifications\Notification::make()
                //             ->title('قرارداد سازمان تایید شد')
                //             ->success()
                //             ->send();
                //     }),

                Tables\Actions\Action::make('reject_contract')
                    ->label('رد قرارداد')
                    ->icon('heroicon-o-document-minus')
                    ->color('danger')
                    ->visible(
                        fn($record) =>
                        $record->account_type !== 'individual' &&
                        $record->organization &&
                        $record->organization->contract_status === 'pending'
                    )
                    ->form([
                        Forms\Components\Textarea::make('contract_rejection_reason')
                            ->label('دلیل رد')
                            ->required()
                            ->rows(3)
                            ->placeholder('لطفا دلیل رد قرارداد را وارد کنید...')
                    ])
                    ->modalHeading('رد قرارداد سازمان')
                    ->modalSubmitActionLabel('رد قرارداد')
                    ->action(function ($record, array $data) {
                        $record->organization->update([
                            'contract_status' => 'rejected',
                            'contract_approved_at' => null,
                            'contract_rejection_reason' => $data['contract_rejection_reason'],
                        ]);

                        // بروزرسانی آخرین قرارداد در جدول organization_contracts
                        $latestContract = \App\Models\OrganizationContract::latestForOrganization($record->organization->id)->first();
                        if ($latestContract) {
                            $latestContract->update([
                                'status' => 'rejected',
                                'rejection_reason' => $data['contract_rejection_reason'],
                                'reviewed_at' => now(),
                                'reviewed_by' => auth('admin')->id(),
                            ]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('قرارداد سازمان رد شد')
                            ->danger()
                            ->send();
                    }),

                // Actions معمولی
                Tables\Actions\ViewAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        // بارگذاری اطلاعات سازمان برای نمایش
                        if ($record->organization) {
                            $data['organization'] = [
                                'organization_name' => $record->organization->organization_name,
                                'organization_code' => $record->organization->organization_code,
                                'organization_phone' => $record->organization->organization_phone,
                                'organization_address' => $record->organization->organization_address,
                                'manager_full_name' => $record->organization->manager_full_name,
                                'agent_name' => $record->organization->agent_name,
                                'agent_phone' => $record->organization->agent_phone,
                                'history' => $record->organization->history,
                                'manager_national_code' => $record->organization->manager_national_code,
                                'profile_image' => $record->organization->profile_image,
                                'profile_status' => $record->organization->profile_status,
                                'profile_approved_at' => $record->organization->profile_approved_at,
                                'profile_rejection_reason' => $record->organization->profile_rejection_reason,
                                'contract_status' => $record->organization->contract_status,
                                'contract_approved_at' => $record->organization->contract_approved_at,
                                'contract_rejection_reason' => $record->organization->contract_rejection_reason,
                            ];
                        }
                        return $data;
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->by_who === 'admin'),
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
            RelationManagers\AddressesRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
