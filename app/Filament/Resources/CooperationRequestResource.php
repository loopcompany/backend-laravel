<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CooperationRequestResource\Pages;
use App\Filament\Resources\CooperationRequestResource\RelationManagers;
use App\Models\CooperationRequest;
use App\Support\Careers\CareerOptions as O;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * درخواست‌های همکاری با لوپ + فرم مدیریت گزینش و مصاحبه.
 * (طبق تصمیم فعلی در همین پنل؛ با راه‌اندازی پنل اتوماسیون به آن منتقل می‌شود.)
 */
class CooperationRequestResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = CooperationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'همکاری و استخدام';

    protected static ?string $navigationLabel = 'درخواست‌های همکاری';

    protected static ?string $modelLabel = 'درخواست همکاری';

    protected static ?string $pluralModelLabel = 'درخواست‌های همکاری';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'tracking_code';

    protected static function getViewPermission(): string
    {
        return 'view-cooperation-requests';
    }

    protected static function getCreatePermission(): string
    {
        return 'edit-cooperation-requests';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-cooperation-requests';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-cooperation-requests';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CooperationRequest::where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['tracking_code', 'full_name', 'mobile', 'national_code'];
    }

    // ================================================================= فرم

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('وضعیت پرونده‌ی متقاضی')
                ->schema([
                    Forms\Components\Placeholder::make('tracking')
                        ->label('کد متقاضی')
                        ->content(fn (?CooperationRequest $record) => $record?->tracking_code ?? 'پس از ذخیره صادر می‌شود')
                        ->visibleOn('edit'),
                    Forms\Components\Select::make('status')
                        ->label('وضعیت نهایی پرونده')
                        ->options(O::STATUSES)
                        ->default('new')
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('source')
                        ->label('منبع درخواست')
                        ->options(O::SOURCES)
                        ->default('in_person')
                        ->required(),
                    Forms\Components\Textarea::make('status_note')
                        ->label('توضیح تغییر وضعیت (در تاریخچه ثبت می‌شود)')
                        ->rows(2)
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Tabs::make('recruitment_tabs')
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    static::applicantTab(),
                    static::screeningTab(),
                    static::contactTab(),
                    static::interviewsTab(),
                    static::testTab(),
                    static::decisionTab(),
                    static::offerTab(),
                    static::hiringTab(),
                    static::finalApprovalTab(),
                ]),
        ]);
    }

    /** ۱. اطلاعات متقاضی (برای درخواست‌های حضوری/تلفنی که ادمین ثبت می‌کند هم استفاده می‌شود). */
    private static function applicantTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('اطلاعات متقاضی')
            ->icon('heroicon-o-user')
            ->schema([
                Forms\Components\TextInput::make('full_name')->label('نام و نام خانوادگی')->required()->maxLength(191),
                Forms\Components\TextInput::make('mobile')->label('شماره موبایل')->required()->regex('/^09[0-9]{9}$/'),
                Forms\Components\TextInput::make('national_code')->label('کد ملی')->required()->regex('/^[0-9]{10}$/'),
                Forms\Components\TextInput::make('city')->label('شهر محل سکونت')->required()->maxLength(100),
                Forms\Components\TextInput::make('district')->label('منطقه')->maxLength(100),
                Forms\Components\TextInput::make('age')->label('سن')->numeric()->minValue(16)->maxValue(70)->required(),
                Forms\Components\Select::make('gender')->label('جنسیت')->options(O::GENDERS)->required()->live(),
                Forms\Components\Select::make('marital_status')->label('وضعیت تأهل')->options(O::MARITAL)->required(),
                Forms\Components\Select::make('military_status')->label('وضعیت نظام وظیفه')->options(O::MILITARY)
                    ->visible(fn (Forms\Get $get) => $get('gender') === 'male')
                    ->required(fn (Forms\Get $get) => $get('gender') === 'male'),
                Forms\Components\Select::make('job_title')->label('موقعیت شغلی درخواستی')->options(O::JOB_TITLES)->required()->live(),
                Forms\Components\TextInput::make('job_title_other')->label('عنوان شغلی (سایر)')
                    ->visible(fn (Forms\Get $get) => $get('job_title') === 'other'),
                Forms\Components\Select::make('cooperation_type')->label('نوع همکاری')->options(O::COOPERATION_TYPES)->required(),
                Forms\Components\Select::make('education_level')->label('آخرین مدرک تحصیلی')->options(O::EDUCATION)->required(),
                Forms\Components\TextInput::make('field_of_study')->label('رشته تحصیلی'),
                Forms\Components\Select::make('work_experience')->label('سابقه کاری')->options(O::WORK_EXPERIENCE)->required(),
                Forms\Components\Select::make('related_experience')->label('سابقه کار مرتبط')->options(O::RELATED_EXPERIENCE)->required(),
                Forms\Components\Select::make('start_availability')->label('زمان آمادگی برای شروع')->options(O::START_AVAILABILITY)->required(),
                Forms\Components\Select::make('salary_type')->label('حقوق درخواستی')->options(O::SALARY_TYPES)->required(),
                Forms\Components\TextInput::make('salary_amount')->label('مبلغ حقوق درخواستی (تومان)')->numeric(),
                Forms\Components\Select::make('overtime')->label('امکان اضافه‌کاری')->options(O::YES_NO_COORDINATION)->required(),
                Forms\Components\Select::make('shift_work')->label('امکان کار در شیفت')->options(O::YES_NO_COORDINATION)->required(),
                Forms\Components\Textarea::make('extra_skills')->label('توضیحات یا تخصص‌های دیگر')->rows(2)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۲. بررسی اولیه رزومه */
    private static function screeningTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('بررسی رزومه')
            ->icon('heroicon-o-document-magnifying-glass')
            ->schema([
                Forms\Components\TextInput::make('recruitment.screening.reviewer')->label('مسئول بررسی'),
                static::jalaliDate('recruitment.screening.date', 'تاریخ بررسی'),
                Forms\Components\TextInput::make('recruitment.screening.education_status')->label('وضعیت تحصیلات'),
                Forms\Components\Select::make('recruitment.screening.insurance')->label('وضعیت بیمه')->options(O::INSURANCE_REVIEW),
                Forms\Components\Select::make('recruitment.screening.experience')->label('سابقه کاری')->options(O::EXPERIENCE_REVIEW),
                Forms\Components\Select::make('recruitment.screening.skills')->label('مهارت‌های اعلام‌شده')->options(O::SKILLS_REVIEW),
                Forms\Components\Select::make('recruitment.screening.result')->label('وضعیت بررسی رزومه')->options(O::RESUME_REVIEW_RESULT),
                Forms\Components\Textarea::make('recruitment.screening.notes')->label('توضیحات بررسی‌کننده')->rows(3)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۳. تماس و دعوت به مصاحبه */
    private static function contactTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('تماس و دعوت')
            ->icon('heroicon-o-phone')
            ->schema([
                Forms\Components\TextInput::make('recruitment.contact.responsible')->label('مسئول تماس'),
                static::jalaliDate('recruitment.contact.date', 'تاریخ تماس'),
                Forms\Components\Select::make('recruitment.contact.result')->label('نتیجه تماس')->options(O::CONTACT_RESULT),
                Forms\Components\Select::make('recruitment.contact.method')->label('روش ارتباط')->options(O::CONTACT_METHOD),
                static::jalaliDate('recruitment.contact.interview_date', 'تاریخ مصاحبه'),
                Forms\Components\TimePicker::make('recruitment.contact.interview_time')->label('ساعت مصاحبه')->seconds(false),
                Forms\Components\Select::make('recruitment.contact.interview_place')->label('محل مصاحبه')->options(O::INTERVIEW_PLACE),
                Forms\Components\Select::make('recruitment.contact.invitation_status')->label('وضعیت دعوت')->options(O::INVITATION_STATUS),
            ])
            ->columns(4);
    }

    /** ۴. اطلاعات مصاحبه (چند مرحله) + ارزیابی */
    private static function interviewsTab(): Forms\Components\Tabs\Tab
    {
        $evaluation = collect(O::EVALUATION_CRITERIA)->map(
            fn ($label, $key) => Forms\Components\Radio::make("evaluation.{$key}")
                ->label($label)
                ->options(O::EVALUATION_LEVELS)
                ->inline()
                ->inlineLabel(false)
        )->values()->all();

        return Forms\Components\Tabs\Tab::make('مصاحبه')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                Forms\Components\Repeater::make('recruitment.interviews')
                    ->label('مراحل مصاحبه')
                    ->addActionLabel('افزودن مرحله‌ی مصاحبه')
                    ->collapsible()
                    ->itemLabel(fn (array $state) => (O::INTERVIEW_STAGE[$state['stage'] ?? ''] ?? 'مصاحبه') . (!empty($state['interviewer']) ? ' — ' . $state['interviewer'] : ''))
                    ->schema([
                        Forms\Components\Select::make('stage')->label('مرحله')->options(O::INTERVIEW_STAGE)->required(),
                        Forms\Components\TextInput::make('interviewer')->label('نام مصاحبه‌کننده'),
                        Forms\Components\TextInput::make('interviewer_position')->label('سمت مصاحبه‌کننده'),
                        static::jalaliDateTime('datetime', 'تاریخ و ساعت'),
                        Forms\Components\Select::make('type')->label('نوع مصاحبه')->options(O::INTERVIEW_TYPE),
                        Forms\Components\Fieldset::make('ارزیابی مصاحبه')->schema($evaluation)->columns(1),
                        Forms\Components\Textarea::make('strengths')->label('نقاط قوت')->rows(2),
                        Forms\Components\Textarea::make('concerns')->label('موارد نیازمند بررسی بیشتر')->rows(2),
                        Forms\Components\Textarea::make('notes')->label('توضیحات مصاحبه‌کننده')->rows(2)->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(0),
            ]);
    }

    /** ۵. آزمون تخصصی */
    private static function testTab(): Forms\Components\Tabs\Tab
    {
        $scoreFields = fn (array $items, string $prefix) => collect($items)->map(
            fn ($label, $key) => Forms\Components\TextInput::make("recruitment.test.{$prefix}.{$key}")->label($label)
        )->values()->all();

        return Forms\Components\Tabs\Tab::make('آزمون تخصصی')
            ->icon('heroicon-o-academic-cap')
            ->schema([
                Forms\Components\Radio::make('recruitment.test.required')->label('آیا آزمون تخصصی نیاز است؟')->options(O::YES_NO)->inline()->live(),
                Forms\Components\Grid::make(3)
                    ->visible(fn (Forms\Get $get) => $get('recruitment.test.required') === 'yes')
                    ->schema([
                        Forms\Components\Select::make('recruitment.test.type')->label('نوع آزمون')->options(O::TEST_TYPES),
                        static::jalaliDate('recruitment.test.date', 'تاریخ آزمون'),
                        Forms\Components\TextInput::make('recruitment.test.responsible')->label('مسئول آزمون'),
                        Forms\Components\Select::make('recruitment.test.result')->label('نتیجه')->options(O::TEST_RESULT),
                        Forms\Components\TextInput::make('recruitment.test.score')->label('امتیاز آزمون'),
                        Forms\Components\Textarea::make('recruitment.test.notes')->label('شرح نتیجه و توضیحات')->rows(2)->columnSpanFull(),
                        Forms\Components\Fieldset::make('آزمون ویژه تکنسین‌ها')->schema($scoreFields(O::TECHNICIAN_TEST_ITEMS, 'technician'))->columns(3)->columnSpanFull(),
                        Forms\Components\Fieldset::make('آزمون ویژه تکنسین‌های میدانی')->schema($scoreFields(O::FIELD_TECHNICIAN_TEST_ITEMS, 'field'))->columns(3)->columnSpanFull(),
                        Forms\Components\Fieldset::make('آزمون ویژه کارکنان اداری')->schema($scoreFields(O::OFFICE_TEST_ITEMS, 'office'))->columns(3)->columnSpanFull(),
                    ]),
            ]);
    }

    /** ۶. بررسی نهایی و تصمیم‌گیری */
    private static function decisionTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('تصمیم نهایی')
            ->icon('heroicon-o-scale')
            ->schema([
                Forms\Components\Select::make('recruitment.decision.result')->label('نتیجه مصاحبه')->options(O::INTERVIEW_DECISION),
                Forms\Components\TextInput::make('recruitment.decision.suggested_job')->label('موقعیت شغلی پیشنهادی'),
                Forms\Components\TextInput::make('recruitment.decision.suggested_unit')->label('واحد سازمانی پیشنهادی'),
                Forms\Components\TextInput::make('recruitment.decision.suggested_position')->label('سمت پیشنهادی'),
                Forms\Components\TextInput::make('recruitment.decision.suggested_manager')->label('مدیر مستقیم پیشنهادی'),
                Forms\Components\Select::make('recruitment.decision.cooperation_type')->label('نوع همکاری پیشنهادی')->options(O::COOPERATION_TYPES),
                Forms\Components\TextInput::make('recruitment.decision.suggested_salary')->label('حقوق پیشنهادی (تومان)'),
                static::jalaliDate('recruitment.decision.suggested_start_date', 'تاریخ پیشنهادی شروع همکاری'),
                Forms\Components\TextInput::make('recruitment.decision.decider')->label('نام مسئول تصمیم‌گیرنده'),
                static::jalaliDate('recruitment.decision.date', 'تاریخ تصمیم'),
                Forms\Components\Textarea::make('recruitment.decision.reasons')->label('توضیحات و دلایل تصمیم')->rows(3)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۷. پیشنهاد همکاری */
    private static function offerTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('پیشنهاد همکاری')
            ->icon('heroicon-o-paper-airplane')
            ->schema([
                Forms\Components\Radio::make('recruitment.offer.sent')->label('آیا پیشنهاد همکاری ارسال شده است؟')->options(O::YES_NO)->inline(),
                static::jalaliDate('recruitment.offer.sent_date', 'تاریخ ارسال پیشنهاد'),
                Forms\Components\Select::make('recruitment.offer.method')->label('روش اطلاع‌رسانی')->options(O::NOTIFY_METHOD),
                Forms\Components\Select::make('recruitment.offer.response')->label('وضعیت پاسخ متقاضی')->options(O::OFFER_RESPONSE),
                static::jalaliDate('recruitment.offer.deadline', 'مهلت پاسخ'),
                Forms\Components\Textarea::make('recruitment.offer.notes')->label('توضیحات')->rows(2)->columnSpanFull(),
            ])
            ->columns(3);
    }

    /** ۸. تشکیل پرونده استخدامی (خلاصه؛ پرونده‌ی کامل در بخش «پرونده‌های استخدامی») */
    private static function hiringTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('تشکیل پرونده')
            ->icon('heroicon-o-folder-plus')
            ->schema([
                Forms\Components\TextInput::make('recruitment.hiring.employee_code')->label('کد کارمندی'),
                static::jalaliDate('recruitment.hiring.start_date', 'تاریخ شروع همکاری'),
                Forms\Components\TextInput::make('recruitment.hiring.unit')->label('واحد سازمانی'),
                Forms\Components\TextInput::make('recruitment.hiring.position')->label('سمت'),
                Forms\Components\TextInput::make('recruitment.hiring.direct_manager')->label('مدیر مستقیم'),
                Forms\Components\TextInput::make('recruitment.hiring.contract_type')->label('نوع قرارداد'),
                Forms\Components\Select::make('recruitment.hiring.file_status')->label('وضعیت تشکیل پرونده')->options(O::HIRING_FILE_STATUS),
            ])
            ->columns(3);
    }

    /** ۱۱. تأیید نهایی */
    private static function finalApprovalTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('تأیید نهایی')
            ->icon('heroicon-o-check-badge')
            ->schema([
                Forms\Components\TextInput::make('recruitment.final.hr_manager')->label('نام مسئول منابع انسانی'),
                Forms\Components\TextInput::make('recruitment.final.unit_manager')->label('نام مدیر واحد مربوطه'),
                Forms\Components\TextInput::make('recruitment.final.approver')->label('نام تأییدکننده نهایی'),
                static::jalaliDate('recruitment.final.date', 'تاریخ تأیید'),
                Forms\Components\Select::make('recruitment.final.result')->label('نتیجه نهایی')->options(O::FINAL_RESULT),
            ])
            ->columns(3);
    }

    private static function jalaliDate(string $name, string $label): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make($name)->label($label)->jalali();
    }

    private static function jalaliDateTime(string $name, string $label): Forms\Components\DateTimePicker
    {
        return Forms\Components\DateTimePicker::make($name)->label($label)->jalali()->seconds(false);
    }

    // ================================================================= جدول

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')->label('کد پیگیری')->searchable()->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('full_name')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('mobile')->label('موبایل')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('national_code')->label('کد ملی')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('job_title')->label('موقعیت شغلی')
                    ->formatStateUsing(fn ($state, CooperationRequest $record) => $record->jobTitleLabel()),
                Tables\Columns\TextColumn::make('gender')->label('جنسیت')->formatStateUsing(fn ($state) => O::GENDERS[$state] ?? $state),
                Tables\Columns\TextColumn::make('city')->label('شهر')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(fn ($state) => O::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'new' => 'warning',
                        'accepted', 'hired' => 'success',
                        'rejected', 'withdrawn' => 'danger',
                        'resume_bank' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('shahkar_status')->label('شاهکار')
                    ->formatStateUsing(fn ($state) => ['verified' => 'تطابق دارد', 'unverified' => 'استعلام نشده', 'mismatch' => 'عدم تطابق'][$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => $state === 'verified' ? 'success' : 'warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')->label('منبع')->formatStateUsing(fn ($state) => O::SOURCES[$state] ?? $state)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(O::STATUSES)->multiple(),
                Tables\Filters\SelectFilter::make('job_title')->label('موقعیت شغلی')->options(O::JOB_TITLES)->multiple(),
                Tables\Filters\SelectFilter::make('gender')->label('جنسیت')->options(O::GENDERS),
                Tables\Filters\SelectFilter::make('source')->label('منبع')->options(O::SOURCES),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->label('گزینش'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ================================================================= نمایش

    public static function infolist(Infolist $infolist): Infolist
    {
        $label = fn (array $options) => fn ($state) => $options[$state] ?? $state;

        return $infolist->schema([
            Infolists\Components\Section::make('خلاصه')
                ->schema([
                    Infolists\Components\TextEntry::make('tracking_code')->label('کد متقاضی')->copyable(),
                    Infolists\Components\TextEntry::make('status')->label('وضعیت')->formatStateUsing($label(O::STATUSES))->badge(),
                    Infolists\Components\TextEntry::make('source')->label('منبع درخواست')->formatStateUsing($label(O::SOURCES)),
                    Infolists\Components\TextEntry::make('created_at')->label('تاریخ ثبت درخواست')->jalaliDateTime('Y/m/d H:i'),
                    Infolists\Components\TextEntry::make('shahkar_status')->label('استعلام شاهکار')
                        ->formatStateUsing(fn ($state) => ['verified' => 'کد ملی با موبایل تطابق دارد', 'unverified' => 'استعلام انجام نشد', 'mismatch' => 'عدم تطابق'][$state] ?? $state),
                    Infolists\Components\TextEntry::make('sms_sent')->label('پیامک تأیید')->formatStateUsing(fn ($state) => $state ? 'ارسال شد' : 'ارسال نشد'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('۱. اطلاعات فردی')
                ->schema([
                    Infolists\Components\TextEntry::make('full_name')->label('نام و نام خانوادگی'),
                    Infolists\Components\TextEntry::make('mobile')->label('شماره موبایل')->copyable(),
                    Infolists\Components\TextEntry::make('national_code')->label('کد ملی')->copyable(),
                    Infolists\Components\TextEntry::make('city')->label('شهر محل سکونت'),
                    Infolists\Components\TextEntry::make('district')->label('منطقه')->placeholder('-'),
                    Infolists\Components\TextEntry::make('age')->label('سن'),
                    Infolists\Components\TextEntry::make('gender')->label('جنسیت')->formatStateUsing($label(O::GENDERS)),
                    Infolists\Components\TextEntry::make('marital_status')->label('وضعیت تأهل')->formatStateUsing($label(O::MARITAL)),
                    Infolists\Components\TextEntry::make('military_status')->label('نظام وظیفه')->formatStateUsing($label(O::MILITARY))->placeholder('-'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('۲ و ۳. موقعیت شغلی، تحصیلات و سابقه')
                ->schema([
                    Infolists\Components\TextEntry::make('job_title')->label('عنوان شغلی')->formatStateUsing(fn ($state, CooperationRequest $record) => $record->jobTitleLabel()),
                    Infolists\Components\TextEntry::make('cooperation_type')->label('نوع همکاری')->formatStateUsing($label(O::COOPERATION_TYPES)),
                    Infolists\Components\TextEntry::make('education_level')->label('آخرین مدرک تحصیلی')
                        ->formatStateUsing(fn ($state, CooperationRequest $record) => $state === 'other' ? 'سایر: ' . $record->education_level_other : (O::EDUCATION[$state] ?? $state)),
                    Infolists\Components\TextEntry::make('field_of_study')->label('رشته تحصیلی')->placeholder('-'),
                    Infolists\Components\TextEntry::make('work_experience')->label('سابقه کاری')->formatStateUsing($label(O::WORK_EXPERIENCE)),
                    Infolists\Components\TextEntry::make('related_experience')->label('سابقه کار مرتبط')->formatStateUsing($label(O::RELATED_EXPERIENCE)),
                ])
                ->columns(3),

            Infolists\Components\Section::make('۴. مهارت‌های کامپیوتری و فنی')
                ->collapsible()
                ->schema([
                    Infolists\Components\TextEntry::make('skills')
                        ->hiddenLabel()
                        ->getStateUsing(fn (CooperationRequest $record) => static::skillsTable($record))
                        ->html()
                        ->columnSpanFull(),
                ]),

            Infolists\Components\Section::make('۵. تخصص‌های تکمیلی')
                ->schema([
                    Infolists\Components\TextEntry::make('interest_areas')->label('حوزه‌های موردعلاقه')
                        ->getStateUsing(fn (CooperationRequest $record) => collect($record->interest_areas ?? [])
                            ->map(fn ($a) => $a === 'other' && $record->interest_other ? 'سایر: ' . $record->interest_other : (O::INTEREST_AREAS[$a] ?? $a))
                            ->implode('، ') ?: '-'),
                    Infolists\Components\TextEntry::make('has_certificates')->label('مدارک فنی و گواهینامه')->formatStateUsing(fn ($state) => $state ? 'دارد' : 'ندارد'),
                    Infolists\Components\TextEntry::make('extra_skills')->label('توضیحات یا تخصص‌های دیگر')->placeholder('-')->columnSpanFull(),
                ])
                ->columns(2),

            Infolists\Components\Section::make('۶. اطلاعات ویژه تکنسین میدانی')
                ->visible(fn (CooperationRequest $record) => $record->isFieldTechnician())
                ->schema([
                    Infolists\Components\TextEntry::make('field_info.has_vehicle')->label('وسیله نقلیه شخصی')->formatStateUsing($label(O::YES_NO)),
                    Infolists\Components\TextEntry::make('field_info.vehicle_type')->label('نوع وسیله نقلیه')->formatStateUsing($label(O::VEHICLE_TYPES)),
                    Infolists\Components\TextEntry::make('field_info.has_license')->label('گواهینامه رانندگی')->formatStateUsing($label(O::YES_NO)),
                    Infolists\Components\TextEntry::make('field_info.license_type')->label('نوع گواهینامه')->formatStateUsing($label(O::LICENSE_TYPES))->placeholder('-'),
                    Infolists\Components\TextEntry::make('field_info.mission_range')->label('امکان مراجعه')->formatStateUsing($label(O::MISSION_RANGE)),
                    Infolists\Components\TextEntry::make('field_info.carry_equipment')->label('حمل تجهیزات')->formatStateUsing($label(O::CARRY_EQUIPMENT)),
                    Infolists\Components\TextEntry::make('field_info.onsite_experience')->label('سابقه خدمات در محل مشتری')->formatStateUsing($label(O::YES_NO)),
                ])
                ->columns(3),

            Infolists\Components\Section::make('۷. شرایط همکاری')
                ->schema([
                    Infolists\Components\TextEntry::make('start_availability')->label('زمان آمادگی برای شروع')->formatStateUsing($label(O::START_AVAILABILITY)),
                    Infolists\Components\TextEntry::make('salary_type')->label('حقوق درخواستی')
                        ->formatStateUsing(fn ($state, CooperationRequest $record) => (O::SALARY_TYPES[$state] ?? $state) . ($record->salary_amount ? ' — ' . number_format($record->salary_amount) . ' تومان' : '')),
                    Infolists\Components\TextEntry::make('overtime')->label('اضافه‌کاری')->formatStateUsing($label(O::YES_NO_COORDINATION)),
                    Infolists\Components\TextEntry::make('shift_work')->label('کار در شیفت')->formatStateUsing($label(O::YES_NO_COORDINATION)),
                ])
                ->columns(4),

            Infolists\Components\Section::make('۸. رزومه و مدارک')
                ->schema([
                    Infolists\Components\TextEntry::make('files')
                        ->hiddenLabel()
                        ->getStateUsing(fn (CooperationRequest $record) => static::filesHtml($record))
                        ->html()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function skillsTable(CooperationRequest $record): HtmlString
    {
        $skills = $record->skills ?? [];
        $html = '<div style="display:grid;gap:14px">';

        foreach (O::SKILL_GROUPS as $group) {
            $html .= '<div><strong>' . e($group['title']) . '</strong><table style="width:100%;border-collapse:collapse;margin-top:6px;font-size:13px">';
            foreach ($group['skills'] as $key => $label) {
                $level = $skills[$key] ?? 'none';
                $color = ['advanced' => '#067647', 'intermediate' => '#175cd3', 'basic' => '#b54708'][$level] ?? '#98a2b3';
                $html .= '<tr style="border-top:1px solid rgba(127,127,127,.2)"><td style="padding:4px 2px">' . e($label) . '</td>'
                    . '<td style="padding:4px 2px;width:110px;color:' . $color . ';font-weight:600">' . e(O::SKILL_LEVELS[$level] ?? $level) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        return new HtmlString($html . '</div>');
    }

    private static function filesHtml(CooperationRequest $record): HtmlString
    {
        $links = [];
        foreach (['resume' => 'رزومه', 'certificates' => 'مدارک فنی', 'portfolio' => 'نمونه‌کار'] as $type => $label) {
            if ($record->{$type . '_path'}) {
                $url = route('admin.cooperation-requests.file', ['cooperationRequest' => $record->id, 'type' => $type]);
                $links[] = '<a href="' . e($url) . '" target="_blank" style="color:#d9a940;font-weight:600">دانلود ' . $label . '</a>';
            }
        }
        if ($record->portfolio_link) {
            $links[] = '<a href="' . e($record->portfolio_link) . '" target="_blank" rel="noopener noreferrer nofollow" style="color:#d9a940;font-weight:600">لینک رزومه/نمونه‌کار</a>';
        }

        return new HtmlString($links ? implode(' &nbsp;|&nbsp; ', $links) : 'فایلی بارگذاری نشده است.');
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
            'index' => Pages\ListCooperationRequests::route('/'),
            'create' => Pages\CreateCooperationRequest::route('/create'),
            'view' => Pages\ViewCooperationRequest::route('/{record}'),
            'edit' => Pages\EditCooperationRequest::route('/{record}/edit'),
        ];
    }
}
