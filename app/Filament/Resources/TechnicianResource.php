<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianResource\Pages;
use App\Filament\Resources\TechnicianResource\RelationManagers;
use App\Models\Technician;
use App\Models\Expertise;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
class TechnicianResource extends Resource
{
    protected static ?string $model = Technician::class;

    protected static ?string $navigationGroup = 'مدیریت کاربران';
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'تکنسین‌ها';
    protected static ?string $title = 'مدیریت تکنسین‌ها';
    protected static ?string $modelLabel = 'تکنسین';
    protected static ?string $pluralModelLabel = 'تکنسین‌ها';

    // کنترل دسترسی تکنسین‌ها
    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->can('view-technicians') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth('admin')->user()?->can('create-technicians') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth('admin')->user()?->can('edit-technicians') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->user()?->can('delete-technicians') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('موجودی کیف پول')
                    ->content(
                        fn($record) => 'موجودی کیف پول تکنسین' . number_format($record?->wallet) . ' تومان می باشد '
                    )
                    ->extraAttributes([
                        'style' => 'background-color: #dbf8d7ff; border: 1px solid #dbf8d7ff; padding: 15px; border-radius: 5px; color:green; width:100%', // رنگ پس‌زمینه و حاشیه قرمز کمرنگ
                    ])
                    ->columnSpanFull(),
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('اطلاعات شخصی')
                            ->schema([
                                Forms\Components\Section::make('اطلاعات پایه')->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->label('نام و نام خانوادگی'),
                                    Forms\Components\FileUpload::make('profile_photo_path')
                                        ->image()
                                        ->label('تصویر پروفایل'),

                                    Forms\Components\TextInput::make('referral_code')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->label('کد تکنسین'),
                                    Forms\Components\TextInput::make('other_referral_code')
                                        ->label('کد تکنسینی معرف'),
                                    Forms\Components\Select::make('technician_type')
                                        ->options([
                                            'تکنسین میدانی' => 'تکنسین میدانی',
                                            'تکنسین داخلی' => 'تکنسین داخلی'
                                        ])
                                        ->label('گروه بندی تکنسین'),

                                    Forms\Components\TextInput::make('melicode')
                                        ->label('کد ملی'),

                                    Forms\Components\TextInput::make('phone')
                                        ->required()
                                        ->disabledOn('edit')
                                        ->unique(ignoreRecord: true)
                                        ->label('شماره تلفن'),

                                    Forms\Components\TextInput::make('birth_date')
                                        ->label('تاریخ تولد')
                                        ->placeholder('1370/01/01'),

                                    Forms\Components\TextInput::make('father_name')
                                        ->required()
                                        ->label('نام پدر'),

                                    Forms\Components\TextInput::make('issued_from')
                                        ->required()
                                        ->label('صادره از'),

                                    Forms\Components\TextInput::make('serial_number')
                                        ->required()

                                        ->label('شماره شناسنامه'),

                                    Forms\Components\Select::make('marital_status')
                                        ->required()
                                        ->options([
                                            'مجرد' => 'مجرد',
                                            'متأهل' => 'متأهل',
                                        ])
                                        ->label('وضعیت تاهل'),

                                    Forms\Components\Select::make('military_status')
                                        ->required()
                                        ->options([
                                            'معاف' => 'معاف',
                                            'در حال خدمت' => 'در حال خدمت',
                                            'پایان خدمت' => 'پایان خدمت',
                                        ])
                                        ->label('وضعیت نظام وظیفه'),

                                    Forms\Components\TextInput::make('education_status')
                                        ->required()
                                        ->label('وضعیت تحصیلات'),
                                    Forms\Components\TextInput::make('education_field')
                                        ->required()
                                        ->label('رشته تحصیلی'),


                                    Forms\Components\TextInput::make('certificate_expiry_date')

                                        ->label('تاریخ اعتبار گواهینامه')
                                        ->placeholder('1402/01/01'),
                                    Forms\Components\TextInput::make('certificate_number')

                                        ->label('شماره گواهینامه'),
                                    Forms\Components\TextInput::make('certificate_issue_date')
                                        ->label('تاریخ صدور گواهینامه')
                                        ->placeholder('1402/01/01'),




                                    Forms\Components\TextInput::make('region')
                                        ->required()
                                        ->label('منطقه'),

                                    Forms\Components\TextInput::make('city')
                                        ->required()
                                        ->label('شهر'),
                                ])
                                    ->columns(2)
                                    ->collapsible(),

                                Forms\Components\Section::make('اطلاعات تماس')->schema([
                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->label('ایمیل'),

                                    Forms\Components\TextInput::make('telephone')
                                        ->required()
                                        ->label('تلفن ثابت'),

                                    Forms\Components\TextInput::make('mobile')
                                        ->label('موبایل'),
                                    Forms\Components\TextInput::make('home_postal_code')
                                        ->required()
                                        ->label('کد پستی منزل'),
                                    Forms\Components\Textarea::make('home_address')
                                        ->required()
                                        ->label('آدرس منزل')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),
                        Forms\Components\Tabs\Tab::make('مشخصات وسیله نقلیه')->schema([
                            Forms\Components\Select::make('vehicle_type')
                                ->options([
                                    'موتور سیکلت' => 'موتور سیکلت',
                                    'خودرو' => 'خودرو',
                                    'دوچرخه' => 'دوچرخه',
                                    'پیاده' => 'پیاده',
                                ])
                                ->required()
                                ->default(null)
                                ->reactive()
                                ->label('نوع وسیله نقلیه'),
                            Forms\Components\TextInput::make('car_model')
                                ->maxLength(191)
                                ->label('مدل')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),
                            Forms\Components\TextInput::make('car_color')
                                ->maxLength(191)
                                ->label('رنگ')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),
                            Forms\Components\TextInput::make('car_year')
                                ->maxLength(191)
                                ->label('سال ساخت')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),
                            Forms\Components\TextInput::make('car_fuel_type')
                                ->maxLength(191)
                                ->label('نوع سوخت')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),
                            Forms\Components\TextInput::make('car_vin')
                                ->maxLength(191)
                                ->label('شماره شناسه وسیله (VIN)')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),
                            Forms\Components\TextInput::make('car_insurance_code')
                                ->maxLength(191)
                                ->label('کد یکتای بیمه شخص ثالث')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),
                            Forms\Components\TextInput::make('car_insurance_expiry_date')
                                ->maxLength(191)
                                ->label('تاریخ انقضاء بیمه شخص ثالث ')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),

                            Forms\Components\TextInput::make('car_plate')
                                ->maxLength(191)
                                ->label('پلاک خودرو')
                                ->helperText('نمونه درست: 12ب345ایران67 - برای پلاک های دارای الف، حرف "ا" را وارد کنید - نمونه درست موتور: 123-45678')
                                ->visible(fn(Get $get) => ($get('vehicle_type') === 'موتور سیکلت' || $get('vehicle_type') === 'خودرو')),

                        ]),
                        Forms\Components\Tabs\Tab::make('اطلاعات مالی')->schema([
                            Forms\Components\TextInput::make('commission')
                                ->numeric()
                                ->label('سهم تکنسین از هر سفارش به درصد'),
                            Forms\Components\TextInput::make('bank_card_number')
                                ->maxLength(191)
                                ->label('شماره کارت'),
                            Forms\Components\TextInput::make('bank_name')
                                ->maxLength(191)
                                ->label('نام بانک'),
                            Forms\Components\TextInput::make('bank_shaba_number')
                                ->maxLength(191)
                                ->label('شماره شبا'),
                        ]),
                        Forms\Components\Tabs\Tab::make('تخصص و مهارت')
                            ->schema([
                                Forms\Components\Section::make('تخصص‌ها')->schema([
                                    Forms\Components\Select::make('expertises')
                                        ->relationship('expertises', 'title')
                                        ->multiple()
                                        ->preload()
                                        ->label('تخصص‌ها')
                                        ->columnSpanFull(),
                                ])
                                    ->collapsible(),

                                Forms\Components\Section::make('مهارت‌ها و توانایی‌ها')->schema([
                                    Forms\Components\Textarea::make('software_skill')
                                        ->required()
                                        ->label('مهارت‌های نرم‌افزاری')
                                        ->rows(3),

                                    Forms\Components\Textarea::make('hardware_skill')
                                        ->required()
                                        ->label('مهارت‌های سخت‌افزاری')
                                        ->rows(3),

                                    Forms\Components\Textarea::make('software_weakness')
                                        ->required()
                                        ->label('نقاط ضعف نرم‌افزاری')
                                        ->rows(3),

                                    Forms\Components\Textarea::make('hardware_weakness')
                                        ->required()
                                        ->label('نقاط ضعف سخت‌افزاری')
                                        ->rows(3),

                                    Forms\Components\Textarea::make('idea')
                                        ->required()
                                        ->label('ایده و خلاقیت')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),

                        Forms\Components\Tabs\Tab::make('وضعیت و تایید')
                            ->schema([
                                Forms\Components\Section::make('وضعیت تایید')->schema([
                                    Forms\Components\Select::make('approval_status')
                                        ->options([
                                            Technician::APPROVAL_PENDING => 'در انتظار تایید',
                                            Technician::APPROVAL_APPROVED => 'تایید شده',
                                            Technician::APPROVAL_REJECTED => 'رد شده',
                                        ])
                                        ->required()
                                        ->default(Technician::APPROVAL_PENDING)
                                        ->reactive()
                                        ->label('وضعیت تایید'),

                                    Forms\Components\Textarea::make('rejection_reason')
                                        ->label('علت رد')
                                        ->placeholder('در صورت رد کردن تکنسین، دلیل را وارد کنید...')
                                        ->required(fn(Forms\Get $get): bool => $get('approval_status') === Technician::APPROVAL_REJECTED)
                                        ->visible(fn(Forms\Get $get): bool => $get('approval_status') === Technician::APPROVAL_REJECTED)
                                        ->rows(4)
                                        ->columnSpanFull(),

                                    Forms\Components\Toggle::make('has_access')
                                        ->label('دسترسی سیستم')
                                        ->reactive()
                                        ->helperText('آیا تکنسین دسترسی به سیستم داشته باشد؟')
                                        ->default(true),
                                    Forms\Components\Select::make('limit_access_reason')
                                        ->label('علت بستن دسترسی')
                                        ->required(fn(Forms\Get $get): bool => $get('has_access') == false)
                                        ->visible(fn(Forms\Get $get): bool => $get('has_access') == false)
                                        ->options([
                                            'امتیازات و نظرات منفی زیادی نسبت به قبل دارید.' => 'امتیازات و نظرات منفی زیادی نسبت به قبل دارید.',
                                            'موارد منفی انضباطی زیادی دارید.' => 'موارد منفی انضباطی زیادی دارید.',
                                            'مهارت کمتری دارید و می بایست تحت آموزش لوپ باشد.' => 'مهارت کمتری دارید و می بایست تحت آموزش لوپ باشد.'
                                        ])
                                        ->columnSpanFull(),
                                ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('نام'),
                Tables\Columns\TextColumn::make('referral_code')
                    ->searchable()
                    ->label('کد تکنسین'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('تلفن'),

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
                Tables\Columns\BadgeColumn::make('at_work')
                    ->colors([
                        'success' => '1',
                        'danger' => '0',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '0' => 'خاموش',
                        '1' => 'روشن',
                        default => $state,
                    })
                    ->label('وضعیت حساب'),
                Tables\Columns\BadgeColumn::make('approval_status')
                    ->colors([
                        'warning' => Technician::APPROVAL_PENDING,
                        'success' => Technician::APPROVAL_APPROVED,
                        'danger' => Technician::APPROVAL_REJECTED,
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        Technician::APPROVAL_PENDING => 'در انتظار',
                        Technician::APPROVAL_APPROVED => 'تایید شده',
                        Technician::APPROVAL_REJECTED => 'رد شده',
                        default => $state,
                    })
                    ->label('وضعیت تایید'),

                Tables\Columns\IconColumn::make('has_access')
                    ->boolean()
                    ->label('دسترسی'),

                Tables\Columns\TextColumn::make('expertises_count')
                    ->counts('expertises')
                    ->label('تعداد تخصص'),
                Tables\Columns\TextColumn::make('technician_type')
                    ->searchable()
                    ->label('گروه بندی تکنسین'),

                Tables\Columns\TextColumn::make('phone_verified_at')
                    ->jalaliDate()
                    ->label('تایید تلفن')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approved_at')
                    ->jalaliDateTime()
                    ->label('زمان تایید')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ثبت'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->options([
                        Technician::APPROVAL_PENDING => 'در انتظار تایید',
                        Technician::APPROVAL_APPROVED => 'تایید شده',
                        Technician::APPROVAL_REJECTED => 'رد شده',
                    ])
                    ->label('وضعیت تایید'),

                Tables\Filters\TernaryFilter::make('has_access')
                    ->label('دسترسی سیستم'),

                Tables\Filters\SelectFilter::make('technician_type')
                    ->label('گروه بندی تکنسین')
                    ->options([
                        'تکنسین میدانی' => 'تکنسین میدانی',
                        'تکنسین داخلی' => 'تکنسین داخلی'
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('تایید')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(Technician $record): bool => $record->isPending() || $record->isRejected())
                    ->requiresConfirmation()
                    ->modalHeading('تایید تکنسین')
                    ->modalDescription('آیا از تایید این تکنسین اطمینان دارید؟')
                    ->action(function (Technician $record) {
                        $record->approve(Auth::id());
                        Notification::make()
                            ->success()
                            ->title('تکنسین با موفقیت تایید شد')
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(Technician $record): bool => !$record->isRejected())
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('علت رد')
                            ->placeholder('دلیل رد کردن تکنسین را وارد کنید...')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Technician $record, array $data) {
                        $record->reject($data['rejection_reason'], Auth::id());
                        Notification::make()
                            ->success()
                            ->title('تکنسین رد شد')
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('تایید انتخاب شده‌ها')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تایید تکنسین‌ها انتخاب شده')
                        ->modalDescription('آیا از تایید تکنسین‌ها انتخاب شده اطمینان دارید؟')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $approved = 0;
                            foreach ($records as $record) {
                                if ($record->isPending() || $record->isRejected()) {
                                    $record->approve(Auth::id());
                                    $approved++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title("$approved تکنسین تایید شد")
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SettlementsRelationManager::class,
            RelationManagers\AdminReportViolationsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTechnicians::route('/'),
            'create' => Pages\CreateTechnician::route('/create'),
            'edit' => Pages\EditTechnician::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}