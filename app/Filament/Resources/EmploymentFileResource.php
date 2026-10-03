<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmploymentFileResource\Pages;
use App\Filament\Resources\EmploymentFileResource\RelationManagers;
use App\Models\EmploymentFile;
use App\Support\Careers\CareerOptions as O;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * فرم تشکیل پرونده استخدامی (پرسنل / تکنسین / مدیر).
 *
 * طبق پیشنهاد سند، پرونده دو قسمت دارد: اطلاعات عمومی (برای مدیر مستقیم) و اطلاعات محرمانه‌ی
 * مالی و بانکی که رمزنگاری‌شده ذخیره و فقط با مجوز «view-employment-confidential» دیده می‌شود.
 */
class EmploymentFileResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = EmploymentFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'همکاری و استخدام';

    protected static ?string $navigationLabel = 'پرونده‌های استخدامی';

    protected static ?string $modelLabel = 'پرونده استخدامی';

    protected static ?string $pluralModelLabel = 'پرونده‌های استخدامی';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static function getViewPermission(): string
    {
        return 'view-employment-files';
    }

    protected static function getCreatePermission(): string
    {
        return 'edit-employment-files';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-employment-files';
    }

    protected static function getDeletePermission(): string
    {
        return 'edit-employment-files';
    }

    public static function canSeeConfidential(): bool
    {
        return (bool) auth('admin')->user()?->can('view-employment-confidential');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('employment_tabs')
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    static::identityTab(),
                    static::educationTab(),
                    static::employmentTab(),
                    static::technicianTab(),
                    static::financialTab(),
                    static::documentsTab(),
                    static::accessTab(),
                    static::onboardingTab(),
                    static::approvalTab(),
                    static::systemTab(),
                ]),
        ]);
    }

    /** ۱. اطلاعات هویتی */
    private static function identityTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('اطلاعات هویتی')
            ->icon('heroicon-o-user')
            ->schema([
                Forms\Components\Select::make('person_type')->label('نوع')->options(O::PERSON_TYPES)->required()->default('staff'),
                Forms\Components\TextInput::make('personnel_code')->label('شناسه پرسنل / تکنسین / مدیر')->maxLength(30)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('full_name')->label('نام و نام خانوادگی')->required()->maxLength(191),
                Forms\Components\TextInput::make('general.identity.father_name')->label('نام پدر'),
                Forms\Components\TextInput::make('national_code')->label('کد ملی')->regex('/^[0-9]{10}$/'),
                static::date('general.identity.birth_date', 'تاریخ تولد'),
                Forms\Components\TextInput::make('general.identity.id_number')->label('شماره شناسنامه')
                    ->helperText('در صورت نبود، کد ملی را مجدد وارد کنید.'),
                Forms\Components\TextInput::make('general.identity.issue_place')->label('محل صدور'),
                Forms\Components\Select::make('general.identity.marital_status')->label('وضعیت تأهل')->options(O::MARITAL),
                Forms\Components\TextInput::make('general.identity.dependents')->label('تعداد افراد تحت تکفل')->numeric()->minValue(0),
                Forms\Components\TextInput::make('mobile')->label('شماره موبایل')->regex('/^09[0-9]{9}$/'),
                Forms\Components\TextInput::make('general.identity.emergency_phone')->label('شماره تماس اضطراری'),
                Forms\Components\TextInput::make('general.identity.emergency_contact')->label('نام و نسبت فرد تماس اضطراری'),
                Forms\Components\TextInput::make('general.identity.city')->label('شهر محل سکونت'),
                Forms\Components\Textarea::make('general.identity.address')->label('نشانی محل سکونت')->rows(2)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۲. اطلاعات تحصیلی و تخصصی */
    private static function educationTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('تحصیلی و تخصصی')
            ->icon('heroicon-o-academic-cap')
            ->schema([
                Forms\Components\Select::make('general.education.level')->label('آخرین مدرک تحصیلی')->options(O::EDUCATION),
                Forms\Components\TextInput::make('general.education.field')->label('رشته تحصیلی'),
                Forms\Components\TextInput::make('general.education.institution')->label('نام مرکز آموزشی'),
                Forms\Components\Textarea::make('general.education.main_skills')->label('مهارت‌ها و تخصص‌های اصلی')->rows(2),
                Forms\Components\Textarea::make('general.education.certificates')->label('گواهینامه‌ها و مدارک تخصصی')->rows(2),
                Forms\Components\Textarea::make('general.education.related_experience')->label('سوابق کاری مرتبط')->rows(2),
                Forms\Components\Textarea::make('general.education.previous_companies')->label('سابقه کار در شرکت‌های قبلی')->rows(2),
                Forms\Components\FileUpload::make('attachments.final_resume')->label('رزومه نهایی')
                    ->disk('local')->directory('employment-files')->maxSize(10240)->downloadable(),
            ])
            ->columns(2);
    }

    /** ۳. اطلاعات استخدامی و سازمانی */
    private static function employmentTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('استخدامی و سازمانی')
            ->icon('heroicon-o-building-office')
            ->schema([
                Forms\Components\TextInput::make('general.employment.job_title')->label('عنوان شغلی'),
                Forms\Components\TextInput::make('general.employment.unit')->label('واحد سازمانی'),
                Forms\Components\TextInput::make('general.employment.position')->label('سمت سازمانی'),
                Forms\Components\TextInput::make('general.employment.direct_manager')->label('مدیر مستقیم'),
                Forms\Components\Select::make('general.employment.work_location')->label('محل خدمت')->options(O::WORK_LOCATIONS),
                Forms\Components\Select::make('general.employment.cooperation_type')->label('نوع همکاری')->options(O::EMPLOYMENT_COOPERATION_TYPES),
                Forms\Components\Select::make('general.employment.contract_type')->label('نوع قرارداد')->options(O::CONTRACT_TYPES),
                static::date('general.employment.start_date', 'تاریخ شروع همکاری'),
                static::date('general.employment.end_date', 'تاریخ پایان قرارداد (در صورت وجود)'),
                Forms\Components\TextInput::make('general.employment.probation')->label('دوره آزمایشی (در صورت توافق)'),
                Forms\Components\TextInput::make('general.employment.working_hours')->label('ساعات کاری'),
                Forms\Components\TextInput::make('general.employment.shift')->label('شیفت کاری'),
                Forms\Components\Select::make('employment_status')->label('وضعیت استخدام')->options(O::EMPLOYMENT_STATUS)->required()->default('awaiting_start'),
            ])
            ->columns(3);
    }

    /** ۴. اطلاعات اختصاصی تکنسین‌ها */
    private static function technicianTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('اختصاصی تکنسین')
            ->icon('heroicon-o-wrench-screwdriver')
            ->visible(fn (Forms\Get $get) => $get('person_type') === 'technician')
            ->schema([
                Forms\Components\Select::make('general.technician.kind')->label('نوع تکنسین')->options(O::TECHNICIAN_KIND)->live(),
                Forms\Components\CheckboxList::make('general.technician.main_specialties')->label('تخصص اصلی')->options(O::MAIN_SPECIALTIES)->columns(3)->columnSpan(2),
                Forms\Components\Textarea::make('general.technician.sub_specialties')->label('تخصص‌های فرعی')->rows(2),
                Forms\Components\Select::make('general.technician.hardware_level')->label('سطح مهارت سخت‌افزار')->options(O::SKILL_TIERS),
                Forms\Components\Select::make('general.technician.software_level')->label('سطح مهارت نرم‌افزار')->options(O::SKILL_TIERS),
                Forms\Components\TextInput::make('general.technician.activity_area')->label('محدوده فعالیت')
                    ->visible(fn (Forms\Get $get) => $get('general.technician.kind') === 'field'),
                Forms\Components\Select::make('general.technician.vehicle')->label('وسیله نقلیه')->options(O::VEHICLES_SIMPLE)
                    ->visible(fn (Forms\Get $get) => $get('general.technician.kind') === 'field'),
                Forms\Components\TextInput::make('general.technician.license_type')->label('نوع گواهینامه رانندگی'),
                Forms\Components\Textarea::make('general.technician.tools_on_loan')->label('ابزار و تجهیزات تحویلی به صورت امانت')->rows(2)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۵. اطلاعات مالی و حقوقی — بخش محرمانه */
    private static function financialTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('مالی و حقوقی (محرمانه)')
            ->icon('heroicon-o-lock-closed')
            ->visible(fn () => static::canSeeConfidential())
            ->schema([
                Forms\Components\Placeholder::make('confidential_notice')
                    ->hiddenLabel()
                    ->content('اطلاعات این بخش رمزنگاری‌شده نگهداری می‌شود و فقط برای منابع انسانی و افراد مجاز قابل مشاهده است.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('confidential.financial.base_salary')->label('حقوق پایه توافق‌شده'),
                Forms\Components\TextInput::make('confidential.financial.benefits')->label('مزایای توافق‌شده'),
                Forms\Components\TextInput::make('confidential.financial.mission_allowance')->label('حق مأموریت'),
                Forms\Components\TextInput::make('confidential.financial.overtime')->label('اضافه‌کاری'),
                Forms\Components\TextInput::make('confidential.financial.sheba')->label('شماره شبا')->regex('/^(IR)?[0-9]{24}$/i'),
                Forms\Components\TextInput::make('confidential.financial.bank_name')->label('نام بانک'),
                Forms\Components\TextInput::make('confidential.financial.account_holder')->label('نام صاحب حساب'),
                Forms\Components\Select::make('confidential.financial.payment_type')->label('نوع پرداخت حقوق')->options(O::SALARY_PAYMENT_TYPES),
                static::date('confidential.financial.payment_start_date', 'تاریخ شروع پرداخت حقوق'),
                Forms\Components\Textarea::make('confidential.financial.agreements')->label('اطلاعات توافق‌نامه و توافق‌های مالی')->rows(3)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۶. مدارک و مستندات */
    private static function documentsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('مدارک و مستندات')
            ->icon('heroicon-o-document-duplicate')
            ->schema([
                Forms\Components\CheckboxList::make('general.documents.received')->label('مدارک دریافت‌شده')->options(O::EMPLOYMENT_DOCUMENTS)->columns(3)->columnSpanFull(),
                Forms\Components\Select::make('general.documents.status')->label('وضعیت مدارک')->options(O::DOCUMENTS_STATUS),
                Forms\Components\FileUpload::make('attachments.documents')->label('فایل مدارک')
                    ->multiple()->disk('local')->directory('employment-files')->maxSize(10240)->downloadable()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /** ۷. دسترسی‌ها و تجهیزات */
    private static function accessTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('دسترسی‌ها و تجهیزات')
            ->icon('heroicon-o-key')
            ->schema([
                Forms\Components\TextInput::make('general.access.automation_username')->label('نام کاربری اتوماسیون'),
                Forms\Components\TextInput::make('general.access.role')->label('نقش کاربری'),
                Forms\Components\TextInput::make('general.access.level')->label('سطح دسترسی'),
                Forms\Components\TextInput::make('general.access.org_email')->label('ایمیل سازمانی')->email(),
                Forms\Components\TextInput::make('general.access.app_account')->label('حساب کاربری سایت یا اپلیکیشن داخلی'),
                Forms\Components\CheckboxList::make('general.access.equipment')->label('تجهیزات تحویلی')->options(O::EQUIPMENT)->columns(4)->columnSpanFull(),
                Forms\Components\TextInput::make('general.access.asset_numbers')->label('شماره اموال تجهیزات'),
                static::date('general.access.delivery_date', 'تاریخ تحویل'),
                Forms\Components\TextInput::make('general.access.delivery_responsible')->label('مسئول تحویل تجهیزات'),
                Forms\Components\Select::make('general.access.delivery_status')->label('وضعیت تحویل')->options(O::DELIVERY_STATUS),
            ])
            ->columns(3);
    }

    /** ۸. آموزش و شروع به کار */
    private static function onboardingTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('آموزش و شروع به کار')
            ->icon('heroicon-o-rocket-launch')
            ->schema([
                Forms\Components\CheckboxList::make('general.onboarding.steps')->label('مراحل انجام‌شده')->options(O::ONBOARDING_STEPS)->columns(2)->columnSpanFull(),
                Forms\Components\TextInput::make('general.onboarding.trainer')->label('مسئول آموزش'),
                static::date('general.onboarding.start_date', 'تاریخ شروع آموزش'),
                Forms\Components\Select::make('general.onboarding.status')->label('وضعیت آموزش')->options(O::TRAINING_STATUS),
            ])
            ->columns(3);
    }

    /** ۹. تأیید و تکمیل پرونده */
    private static function approvalTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('تأیید و تکمیل')
            ->icon('heroicon-o-check-badge')
            ->schema([
                Forms\Components\TextInput::make('general.approval.file_creator')->label('مسئول تشکیل پرونده'),
                Forms\Components\TextInput::make('general.approval.hr_manager')->label('مسئول منابع انسانی'),
                Forms\Components\TextInput::make('general.approval.unit_manager')->label('مدیر واحد مربوطه'),
                Forms\Components\TextInput::make('general.approval.final_approver')->label('تأییدکننده نهایی'),
                static::date('general.approval.completed_date', 'تاریخ تکمیل پرونده'),
                Forms\Components\Select::make('file_status')->label('وضعیت نهایی پرونده')->options(O::FILE_STATUS)->required()->default('draft'),
                Forms\Components\TextInput::make('general.approval.employee_code')->label('کد تایید کارمند'),
                Forms\Components\TextInput::make('general.approval.hr_code')->label('کد تایید مسئول منابع انسانی'),
                Forms\Components\TextInput::make('general.approval.manager_code')->label('کد تایید مدیر واحد'),
                Forms\Components\DateTimePicker::make('general.approval.datetime')->label('تاریخ و ساعت')->jalali()->seconds(false),
                Forms\Components\Textarea::make('general.approval.notes')->label('توضیحات')->rows(2)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۱۰. ثبت سیستمی (فقط نمایش؛ تاریخچه‌ی تغییرات در جدول پایین صفحه است) */
    private static function systemTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('ثبت سیستمی')
            ->icon('heroicon-o-clock')
            ->visibleOn(['edit', 'view'])
            ->schema([
                Forms\Components\Placeholder::make('sys_code')->label('شناسه پرسنل / تکنسین / مدیر')->content(fn (?EmploymentFile $record) => $record?->personnel_code ?: '-'),
                Forms\Components\Placeholder::make('sys_created')->label('تاریخ ایجاد پرونده')->content(fn (?EmploymentFile $record) => $record?->created_at ? \Morilog\Jalali\Jalalian::fromCarbon($record->created_at)->format('Y/m/d H:i') : '-'),
                Forms\Components\Placeholder::make('sys_creator')->label('ایجادکننده پرونده')->content(fn (?EmploymentFile $record) => $record?->creator?->name ?? '-'),
                Forms\Components\Placeholder::make('sys_editor')->label('آخرین ویرایش توسط')->content(fn (?EmploymentFile $record) => $record?->editor?->name ?? '-'),
                Forms\Components\Placeholder::make('sys_updated')->label('تاریخ آخرین ویرایش')->content(fn (?EmploymentFile $record) => $record?->updated_at ? \Morilog\Jalali\Jalalian::fromCarbon($record->updated_at)->format('Y/m/d H:i') : '-'),
                Forms\Components\Placeholder::make('sys_request')->label('درخواست همکاری مبدأ')->content(fn (?EmploymentFile $record) => $record?->cooperationRequest?->tracking_code ?? '-'),
            ])
            ->columns(3);
    }

    private static function date(string $name, string $label): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make($name)->label($label)->jalali();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('personnel_code')->label('شناسه پرسنلی')->searchable()->placeholder('-'),
                Tables\Columns\TextColumn::make('full_name')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('person_type')->label('نوع')->formatStateUsing(fn ($state) => O::PERSON_TYPES[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('mobile')->label('موبایل')->searchable()->placeholder('-'),
                Tables\Columns\TextColumn::make('general.employment.unit')->label('واحد')->placeholder('-'),
                Tables\Columns\TextColumn::make('employment_status')->label('وضعیت استخدام')->formatStateUsing(fn ($state) => O::EMPLOYMENT_STATUS[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('file_status')->label('وضعیت پرونده')->formatStateUsing(fn ($state) => O::FILE_STATUS[$state] ?? $state)->badge()
                    ->color(fn ($state) => $state === 'completed' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('updated_at')->label('آخرین تغییر')->jalaliDateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('person_type')->label('نوع')->options(O::PERSON_TYPES),
                Tables\Filters\SelectFilter::make('employment_status')->label('وضعیت استخدام')->options(O::EMPLOYMENT_STATUS),
                Tables\Filters\SelectFilter::make('file_status')->label('وضعیت پرونده')->options(O::FILE_STATUS),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([\Illuminate\Database\Eloquent\SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmploymentFiles::route('/'),
            'create' => Pages\CreateEmploymentFile::route('/create'),
            'view' => Pages\ViewEmploymentFile::route('/{record}'),
            'edit' => Pages\EditEmploymentFile::route('/{record}/edit'),
        ];
    }
}
