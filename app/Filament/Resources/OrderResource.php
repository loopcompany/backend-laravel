<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Category;
use App\Models\Technician;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolist\Infolist;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    // use HasFilamentPermissions;
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'سفارشات';

    protected static ?string $modelLabel = 'سفارش';

    protected static ?string $pluralModelLabel = 'سفارشات';

    protected static ?int $navigationSort = 2;
    protected static function getCreatePermission(): string
    {
        return 'create-orders';
    }
    protected static function getViewPermission(): string
    {
        return 'view-orders';
    }
    protected static function getEditPermission(): string
    {
        return 'edit-orders';
    }
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('اطلاعات اصلی سفارش')
                ->schema([

                    Forms\Components\Placeholder::make('user_code')
                        ->label('کد کاربری')
                        ->content(fn(?Order $record) => $record?->user?->code ?? '-'),

                    Forms\Components\Placeholder::make('user_name')
                        ->label('نام کاربر')
                        ->content(fn(?Order $record) => $record?->user?->name ?? '-'),

                    Forms\Components\Placeholder::make('user_last_name')
                        ->label('نام خانوادگی کاربر')
                        ->content(fn(?Order $record) => $record?->user?->last_name ?? '-'),

                    Forms\Components\Placeholder::make('user_melicode')
                        ->label('کد ملی')
                        ->content(fn(?Order $record) => $record?->user?->melicode ?? '-'),

                    Forms\Components\Placeholder::make('user_phone')
                        ->label('شماره همراه')
                        ->content(fn(?Order $record) => $record?->user?->phone ?? '-'),

                    Forms\Components\Placeholder::make('emergency_help')
                        ->label('درخواست کمک اضطراری')
                        ->content(fn(?Order $record) => $record?->emergency_help ?? '-'),

                    Forms\Components\Placeholder::make('user_account_type')
                        ->label('نوع حساب کاربری')
                        ->content(fn(?Order $record) => match ($record?->user?->account_type) {
                            'individual' => 'کاربر عادی',
                            'company' => 'شرکت خصوصی',
                            // 'organization' => 'کاربر سازمانی',
                            'g_organization' => 'سازمانی دولتی',
                            's_g_organization' => 'سازمانی نیمه دولتی',
                            default => '-'
                        }),

                    Forms\Components\Select::make('category_id')
                        ->label('دسته‌بندی')
                        ->options(Category::pluck('title', 'id'))
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Select::make('technician_id')
                        ->label('تکنسین')
                        ->relationship(
                            'technician',
                            'name',
                            fn($query) => $query->where('approval_status', 'approved')->where('at_work', '1')->where('has_access', 1)
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('فقط تکنسین‌ها تایید شده و دارای دسترسی و با وضعیت روشن نمایش داده می‌شوند')
                        ->disabled(fn(?Order $record) => $record && in_array($record->status, [3, 4, 5]))
                        ->dehydrated(fn(?Order $record) => !($record && in_array($record->status, [3, 4, 5]))),

                    Forms\Components\Select::make('status')
                        ->label('وضعیت سفارش')
                        ->options([
                            0 => 'در انتظار',
                            1 => 'در حال انجام',
                            2 => 'انجام شده',
                            3 => 'لغو شده توسط کاربر',
                            4 => 'لغو شده توسط تکنسین',
                            5 => 'لغو شده توسط ادمین',
                            6 => 'منقضی شده',
                        ])
                        ->required()
                        ->default(0),

                    Forms\Components\Select::make('payment_status')
                        ->label('وضعیت پرداخت')
                        ->options([
                            0 => 'پرداخت نشده',
                            1 => 'پرداخت شده',
                        ])
                        ->required()
                        ->default(0),

                    Forms\Components\Select::make('is_technician_verified')
                        ->label('احراز هویت تکنسین')
                        ->options([
                            '0' => 'در انتظار',
                            '1' => 'تایید شده',
                            '2' => 'رد شده',
                        ])
                        ->default('0'),

                ])->columns(2),


            Forms\Components\Section::make('مبالغ')
                ->schema([

                    Forms\Components\TextInput::make('pakar_price')
                        ->label('مبلغ پایه لوپ')
                        ->disabledOn('edit')
                        ->numeric()
                        ->suffix('تومان'),

                    Forms\Components\TextInput::make('technician_price')
                        ->label('مبلغ نهایی لوپ بدون تخفیف و خدمات اضافی')
                        ->numeric()
                        ->helperText('این فیلد تنها زمانی که کالا به لوپ اعزام شده باشد یا سفارش در محل کاربر انجام شده باشد قابل ویرایش است')
                        ->disabled(fn(?Order $record) => is_null($record?->send_to_loop) && is_null($record?->done_in_place))
                        ->suffix('تومان')
                        ->nullable(),

                    Forms\Components\TextInput::make('extra_price')
                        ->label('مبلغ قطعات و خدمات اضافی')
                        ->numeric()
                        ->suffix('تومان')
                        ->disabled()
                        ->nullable(),

                    Forms\Components\TextInput::make('discount_price')
                        ->label('مبلغ تخفیف')
                        ->numeric()
                        ->suffix('تومان')
                        ->default(0),

                ])->columns(2),


            Forms\Components\Section::make('زمان‌بندی')
                ->schema([

                    Forms\Components\DatePicker::make('date')
                        ->label('تاریخ سرویس')
                        ->jalali()
                        ->required(),

                    Forms\Components\TextInput::make('time')
                        ->label('ساعت سرویس')
                        ->required(),

                    Forms\Components\DateTimePicker::make('set_off_at')
                        ->label('زمان حرکت تکنسین')
                        ->jalali()
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('arrived_at')
                        ->label('زمان رسیدن تکنسین')
                        ->jalali()
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('send_to_loop')
                        ->label('زمان اعزام محصول به لوپ')
                        ->jalali()
                        ->nullable(),

                    Forms\Components\Select::make('shipment_status')
                        ->label('وضعیت اعزام محصول به محل کاربر')
                        ->options([
                            'تکنسین لوپ' => 'تکنسین لوپ',
                            'پیک کاربر' => 'پیک کاربر',
                            'نماینده کاربر' => 'نماینده کاربر',
                            'کاربر' => 'کاربر',
                            'پیک لوپ' => 'پیک لوپ'
                        ]),

                    Forms\Components\Textarea::make('shipment_status_descriptions')
                        ->label('توضیحات لوپ در مورد وضعیت اعزام محصول به محل کاربر'),

                    Forms\Components\TextInput::make('started_at')
                        ->label('زمان شروع کار')
                        ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i:s') : 'ثبت نشده')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('finished_at')
                        ->label('زمان پایان کار')
                        ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i:s') : 'ثبت نشده')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('done_in_place')
                        ->label('زمان انجام سفارش در محل')
                        ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i:s') : 'ثبت نشده')
                        ->disabled()
                        ->dehydrated(false),

                ])->columns(2),


            Forms\Components\Section::make('توضیحات')
                ->schema([

                    Forms\Components\Textarea::make('des')
                        ->label('توضیحات کاربر')
                        ->rows(3)
                        ->disabled(),

                    Forms\Components\Textarea::make('technician_des')
                        ->label('توضیحات سفارش پس از بررسی')
                        ->rows(3)
                        ->nullable(),
                    Forms\Components\Textarea::make('technician_in_place_description')
                        ->label('توضیحات تکنسین پس از انجام سفارش در محل کاربر')
                        ->rows(3)
                        ->disabledOn('edit')
                        ->nullable(),
                    Forms\Components\Textarea::make('admin_in_place_description')
                        ->label('توضیحات مدیریت پس از انجام سفارش در محل کاربر')
                        ->rows(3)
                        ->maxLength(191)
                        ->nullable(),
                    Forms\Components\Textarea::make('user_in_place_description')
                        ->label('توضیحات کاربر پس از انجام سفارش در محل کاربر')
                        ->rows(3)
                        ->disabledOn('edit')
                        ->nullable(),

                    Forms\Components\FileUpload::make('image_path')
                        ->label('تصویر سفارش')
                        ->image()
                        ->directory('orders')
                        ->visibility('public')
                        ->disabled(),

                ]),

            Forms\Components\Section::make('تعداد تکنسین‌ها')
                ->schema([

                    Forms\Components\TextInput::make('female_count')
                        ->label('تعداد تکنسین خانم')
                        ->numeric()
                        ->default(0)
                        ->disabled(),

                    Forms\Components\TextInput::make('male_count')
                        ->label('تعداد تکنسین آقا')
                        ->numeric()
                        ->default(0)
                        ->disabled(),

                ])->columns(3),


            Forms\Components\Section::make('اطلاعات لوپ')
                ->schema([

                    Forms\Components\Placeholder::make('send_to_loop_status')
                        ->label('وضعیت ارسال به لوپ')
                        ->content(
                            fn(?Order $record) =>
                            $record?->send_to_loop
                            ? '✅ ارسال شده در ' . \Morilog\Jalali\Jalalian::forge($record->send_to_loop)->format('Y/m/d H:i')
                            : '❌ ارسال نشده'
                        )
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('duration')
                        ->label('مدت زمان انجام سفارش (روز کاری)')
                        ->numeric()
                        ->disabled(
                            fn(?Order $record) =>
                            !$record || ( $record->prepayment_payment_status == 1)
                        )
                        ->suffix('روز کاری')
                        ->nullable(),

                    Forms\Components\TextInput::make('loop_cost_estimate')
                        ->label('هزینه تقریبی لوپ')
                        ->numeric()
                        ->disabled(
                            fn(?Order $record) =>
                            !$record || ( $record->prepayment_payment_status == 1)
                        )
                        ->suffix('تومان')
                        ->nullable(),

                    Forms\Components\TextInput::make('prepayment')
                        ->label('درصد پیش پرداخت')
                        ->numeric()
                        ->suffix('%')
                        ->disabled(
                            fn(?Order $record) =>
                            !$record || ( $record->prepayment_payment_status == 1)
                        )
                        ->nullable(),

                    Forms\Components\Textarea::make('loop_description')
                        ->label('توضیحات لوپ')
                        ->rows(3)
                        ->maxLength(191)
                        ->nullable()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('prepayment_payment_status')
                        ->label('پیش پرداخت')
                        ->options([
                            0 => 'پرداخت نشده',
                            1 => 'پرداخت شده',
                        ])
                        ->required()
                        ->disabled(
                            fn(?Order $record) =>
                            !$record ||
                           
                            is_null($record->loop_cost_estimate) ||
                            is_null($record->prepayment) ||
                            $record->prepayment == 0
                        )
                        ->default(0),

                    Forms\Components\Placeholder::make('user_accept_date_view')
                        ->label('زمان تایید اطلاعات توسط کاربر')
                        ->content(
                            fn(?Order $record) =>
                            $record?->user_accept_date
                            ? \Morilog\Jalali\Jalalian::forge($record->user_accept_date)->format('Y/m/d H:i')
                            : '-'
                        )
                        ->visible(fn(?Order $record) => $record?->user_accept_date),

                    Forms\Components\Placeholder::make('user_cancellation_date')
                        ->label('زمان درخواست بازگشت محصول توسط کاربر')
                        ->content(
                            fn(?Order $record) =>
                            $record?->user_cancellation_date
                            ? \Morilog\Jalali\Jalalian::forge($record->user_cancellation_date)->format('Y/m/d H:i')
                            : '-'
                        )
                        ->visible(fn(?Order $record) => $record?->user_cancellation_date),

                    Forms\Components\Placeholder::make('user_cancellation_reason')
                        ->label('توضیحات کاربر')
                        ->content(fn(?Order $record) => $record?->user_cancellation_reason ?? '-')
                        ->visible(fn(?Order $record) => filled($record?->user_cancellation_reason)),

                    Forms\Components\Textarea::make('technician_opinion')
                        ->label('نظر تکنسین')
                        ->disabled()
                        ->columnSpanFull(),

                ])
                ->columns(2),

        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شماره سفارش / کدپیگیری')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.code')
                    ->label('کد کاربر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('user.is_special')
                    ->label('اکانت ویژه')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-user')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام مشتری')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.melicode')
                    ->label('شماره ملی مشتری')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('شماره تلفن همراه ثبت شده کاربر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.title')
                    ->label('دسته‌بندی')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('technician.name')
                    ->label('تکنسین')
                    ->searchable()
                    ->sortable()
                    ->default('تخصیص داده نشده')
                    ->color(fn($state) => $state ? 'success' : 'gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '0' => 'در انتظار',
                        '1' => 'در حال انجام',
                        '2' => 'انجام شده',
                        '3' => 'لغو - کاربر',
                        '4' => 'لغو - تکنسین',
                        '5' => 'لغو - ادمین',
                        '6' => 'منقضی شده',
                        default => 'نامشخص',
                    })
                    ->colors([
                        'warning' => '0',
                        'primary' => '1',
                        'success' => '2',
                        'danger' => ['3', '4', '5', '6'],
                    ]),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('پرداخت')
                    ->formatStateUsing(fn(string $state): string => $state === '1' ? 'پرداخت شده' : 'پرداخت نشده')
                    ->colors([
                        'success' => '1',
                        'danger' => '0',
                    ]),

                Tables\Columns\TextColumn::make('pakar_price')
                    ->label('مبلغ پایه')
                    ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                    ->sortable(),



                Tables\Columns\TextColumn::make('date')
                    ->jalaliDate()
                    ->label('تاریخ سرویس')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        0 => 'در انتظار',
                        1 => 'در حال انجام',
                        2 => 'انجام شده',
                        3 => 'لغو شده توسط کاربر',
                        4 => 'لغو شده توسط تکنسین',
                        5 => 'لغو شده توسط ادمین',
                        6 => 'منقضی شده',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->options([
                        0 => 'پرداخت نشده',
                        1 => 'پرداخت شده',
                    ]),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('دسته‌بندی')
                    ->relationship('category', 'title')
                    ->searchable()
                    ->preload(),


                Tables\Filters\Filter::make('has_technician')
                    ->label('دارای تکنسین')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('technician_id')),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('از تاریخ')
                            ->jalali(),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('تا تاریخ')
                            ->jalali(),
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
                Tables\Actions\Action::make('download_invoice')
                    ->label('دانلود رسید')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn(Order $record): string => route('web.reciept', ['id' => $record->id]))
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('assign_technician')
                    ->label('اختصاص تکنسین')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('technician_id')
                            ->label('انتخاب تکنسین')
                            ->options(Technician::where('approval_status', 'approved')->where('at_work', '1')->where('has_access', 1)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('فقط تکنسین‌ها تأیید شده نمایش داده می‌شوند'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'technician_id' => $data['technician_id'],
                            'status' => $record->status == 0 ? 1 : $record->status,
                        ]);

                        // ارسال SMS به تکنسین
                        $technician = \App\Models\Technician::find($data['technician_id']);
                        if ($technician && $technician->phone) {
                            $smsService = app(\App\Services\SmsService::class);
                            $smsSent = $smsService->sendOrderAssignedByAdminToTechnician($technician->phone, $record->id);

                            if ($smsSent) {
                                \Illuminate\Support\Facades\Log::info('SMS اختصاص سفارش به تکنسین ارسال شد', [
                                    'order_id' => $record->id,
                                    'technician_id' => $technician->id,
                                    'technician_phone' => $technician->phone
                                ]);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('تکنسین با موفقیت اختصاص یافت')
                            ->body('پیامک اطلاع‌رسانی به تکنسین ارسال شد')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(Order $record): bool =>
                        $record->technician_id == null &&
                        !in_array($record->status, [3, 4, 5]) // سفارشات لغو شده نمی‌توانند تکنسین اختصاص بگیرند
                    ),

                Tables\Actions\Action::make('mark_as_returned')
                    ->label('محصول عودت داده شد')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('تایید عودت محصول')
                    ->modalDescription('آیا مطمئن هستید که محصول با موفقیت عودت داده شده است؟')
                    ->modalSubmitActionLabel('بله، عودت داده شد')
                    ->modalCancelActionLabel('انصراف')
                    ->action(function (Order $record): void {
                        $record->update([
                            'returned_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('محصول با موفقیت عودت داده شد')
                            ->body('زمان عودت محصول ثبت گردید.')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(Order $record): bool =>
                        $record->user_cancellation_date != null &&
                        $record->returned_at == null
                    ),

                Tables\Actions\Action::make('start_work')
                    ->label('شروع کار')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('شروع کار')
                    ->modalDescription('آیا مطمئن هستید که می‌خواهید زمان شروع کار را ثبت کنید؟')
                    ->modalSubmitActionLabel('بله، شروع کن')
                    ->modalCancelActionLabel('انصراف')
                    ->action(function (Order $record): void {
                        $record->update([
                            'started_at' => now(),
                            'status' => 1, // در حال انجام
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('زمان شروع کار ثبت شد')
                            ->body('وضعیت سفارش به "در حال انجام" تغییر یافت.')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(Order $record): bool =>
                        $record->started_at == null &&
                        $record->technician_id != null &&
                        !in_array($record->status, [2, 3, 4, 5, 6]) // فقط برای سفارشات فعال
                    ),

                Tables\Actions\Action::make('complete_work')
                    ->label('اتمام کار')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('اتمام کار')
                    ->modalDescription('آیا مطمئن هستید که می‌خواهید کار را به اتمام برسانید؟ وضعیت سفارش به "انجام شده" تغییر خواهد یافت.')
                    ->modalSubmitActionLabel('بله، اتمام یافت')
                    ->modalCancelActionLabel('انصراف')
                    ->action(function (Order $record): void {
                        try {
                            $record->update([
                                'finished_at' => now(),
                                'status' => 2, // انجام شده
                            ]);

                            // پردازش پرداخت به تکنسین
                            $orderService = app(\App\Services\OrderService::class);
                            $paymentResult = $orderService->processTechnicianPayment($record);

                            if ($paymentResult['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('کار با موفقیت به اتمام رسید')
                                    ->body('وضعیت سفارش به "انجام شده" تغییر یافت و سهم تکنسین واریز شد.')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('کار به اتمام رسید اما پرداخت به تکنسین انجام نشد')
                                    ->body($paymentResult['message'])
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('خطا در اتمام کار')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->after(fn() => redirect()->back())
                    ->visible(
                        fn(Order $record): bool =>
                        $record->started_at != null &&
                        $record->finished_at == null &&
                        !in_array($record->status, [2, 3, 4, 5, 6]) // فقط برای سفارشات فعال
                    ),
            ])
            ->bulkActions([

            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('اطلاعات سفارش')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('شماره سفارش'),

                        Infolists\Components\TextEntry::make('category.title')
                            ->label('دسته‌بندی'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('وضعیت سفارش')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                '0' => 'بررسی / جایگزین / پیش رسید',
                                '1' => 'در حال انجام',
                                '2' => 'انجام شده',
                                '3' => 'لغو شده توسط کاربر',
                                '4' => 'لغو شده توسط تکنسین',
                                '5' => 'لغو شده توسط ادمین',
                                '6' => 'منقضی شده',
                                default => 'نامشخص',
                            })
                            ->color(fn(string $state): string => match ($state) {
                                '0' => 'warning',
                                '1' => 'primary',
                                '2' => 'success',
                                default => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('وضعیت پرداخت')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => $state === '1' ? 'پرداخت شده' : 'پرداخت نشده')
                            ->color(fn(string $state): string => $state === '1' ? 'success' : 'danger'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('اطلاعات کاربر')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.code')
                            ->label('کد کاربری'),

                        Infolists\Components\TextEntry::make('user.account_type')
                            ->label('نوع حساب کاربری')
                            ->badge()
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'individual' => 'کاربر عادی',
                                'company' => 'شرکت خصوصی',
                                // 'organization' => 'کاربر سازمانی',
                                'g_organization' => 'سازمانی دولتی',
                                's_g_organization' => 'سازمانی نیمه دولتی',
                                default => 'نامشخص',
                            })
                            ->color(fn(?string $state): string => match ($state) {
                                'individual' => 'info',
                                'organization' => 'success',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('نام کاربر'),
                        Infolists\Components\TextEntry::make('user.last_name')
                            ->label('نام خانوادگی کاربر'),

                        Infolists\Components\TextEntry::make('user.melicode')
                            ->label('کد ملی'),

                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('شماره همراه'),

                        Infolists\Components\TextEntry::make('user_address.number')
                            ->label('پلاک') ,
                        Infolists\Components\TextEntry::make('user_address.unit')
                            ->label('واحد') ,
                        Infolists\Components\TextEntry::make('user_address.floor')
                            ->label('طبقه') ,
                        Infolists\Components\TextEntry::make('user_address.address')
                            ->label('آدرس')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('user_address.city')
                            ->label('شهر'),

                        Infolists\Components\TextEntry::make('user_address.region')
                            ->label('منطقه'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('اطلاعات تکنسین')
                    ->schema([
                        Infolists\Components\TextEntry::make('technician.name')
                            ->label('نام تکنسین')
                            ->default('تخصیص داده نشده'),

                        Infolists\Components\TextEntry::make('technician.phone')
                            ->label('شماره تلفن'),

                        Infolists\Components\TextEntry::make('is_technician_verified')
                            ->label('احراز هویت تکنسین')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                '0' => 'در انتظار',
                                '1' => 'تایید شده',
                                '2' => 'رد شده',
                                default => 'نامشخص',
                            })
                            ->color(fn(string $state): string => match ($state) {
                                '1' => 'success',
                                '2' => 'danger',
                                default => 'warning',
                            }),
                    ])
                    ->columns(2)
                    ->visible(fn(Order $record) => $record->technician_id != null),

                Infolists\Components\Section::make('مبالغ')
                    ->schema([
                        Infolists\Components\TextEntry::make('pakar_price')
                            ->label('مبلغ پایه لوپ')
                            ->formatStateUsing(fn($state) => number_format($state) . ' تومان'),

                        Infolists\Components\TextEntry::make('technician_price')
                            ->label('مبلغ نهایی لوپ بدون تخفیف و خدمات اضافی')
                            ->formatStateUsing(fn($state) => $state ? number_format($state) . ' تومان' : '-'),
                        Infolists\Components\TextEntry::make('prepayment')
                            ->label('درصد پیش پرداخت')
                            ->formatStateUsing(fn($state) => $state ? $state . ' درصد' : '-'),

                        Infolists\Components\TextEntry::make('extra_price')
                            ->label('مبلغ خدمات اضافی')
                            ->formatStateUsing(fn($state) => $state ? number_format($state) . ' تومان' : '-'),

                        // Infolists\Components\TextEntry::make('discount_price')
                        //     ->label('مبلغ تخفیف')
                        //     ->formatStateUsing(fn($state) => number_format($state) . ' تومان'),

                        Infolists\Components\TextEntry::make('discount_percent')
                            ->label('درصد تخفیف')
                            ->state(function (Order $record) {
                                if (!$record->relationLoaded('discountUse')) {
                                    $record->load('discountUse.discount_code.club');
                                }

                                $discountUse = $record->discountUse;

                                if (!$discountUse || !$discountUse->discount_code) {
                                    return '-';
                                }

                                return $discountUse->discount_code->discount_percent . '%';
                            })
                            ->visible(fn(Order $record) => $record->discountUse && $record->discountUse->discount_code)
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('discount_code')
                            ->label('کد تخفیف')
                            ->state(function (Order $record) {
                                if (!$record->relationLoaded('discountUse')) {
                                    $record->load('discountUse.discount_code');
                                }

                                return $record->discountUse?->discount_code?->code ?? '-';
                            })
                            ->visible(fn(Order $record) => $record->discountUse && $record->discountUse->discount_code)
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('total_price')
                            ->label('مبلغ کل (قبل از تخفیف)')
                            ->state(function (Order $record) {
                                $total = ($record->technician_price ?? 0)
                                    + ($record->extra_price ?? 0);
                                return number_format($total) . ' تومان';
                            })
                            ->weight('bold')
                            ->size('lg')
                            ->color('info'),

                        Infolists\Components\TextEntry::make('final_price')
                            ->label('مبلغ نهایی (با تخفیف)')
                            ->state(function (Order $record) {
                                if (!$record->relationLoaded('discountUse')) {
                                    $record->load('discountUse.discount_code.club');
                                }

                                $discountFromUse = 0;
                                $discountUse = $record->discountUse;

                                if ($discountUse && $discountUse->discount_code) {
                                    $basePrice = $record->technician_price ?? $record->pakar_price ?? 0;
                                    $totalPrice = $basePrice + ($record->extra_price ?? 0);
                                    $discountAmount = ($totalPrice * $discountUse->discount_code->discount_percent) / 100;

                                    if (
                                        $discountUse->discount_code->club &&
                                        $discountUse->discount_code->club->max_price &&
                                        $discountAmount > $discountUse->discount_code->club->max_price
                                    ) {
                                        $discountAmount = $discountUse->discount_code->club->max_price;
                                    }

                                    $discountFromUse = $discountAmount;
                                }

                                $discount = max($discountFromUse, $record->discount_price ?? 0);
                                $total = ($record->technician_price ?? 0) + ($record->extra_price ?? 0) - $discount;

                                return number_format(max(0, $total)) . ' تومان';
                            })
                            ->visible(fn(Order $record) => $record->discountUse && $record->discountUse->discount_code)
                            ->weight('bold')
                            ->size('lg')
                            ->color('success'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('جزئیات سفارش')
                    ->schema([
                        Infolists\Components\ViewEntry::make('order_details')
                            ->label('')
                            ->view('filament.infolists.order-details')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('موقعیت جغرافیایی')
                    ->schema([
                        Infolists\Components\ViewEntry::make('location_map')
                            ->label('')
                            ->view('filament.infolists.location-map')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(Order $record) => $record->user_address?->latitude && $record->user_address?->longitude)
                    ->collapsible(),

                Infolists\Components\Section::make('زمان‌بندی')
                    ->schema([
                        Infolists\Components\TextEntry::make('date')
                            ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d') : '-')
                            ->label('تاریخ سرویس'),

                        Infolists\Components\TextEntry::make('time')
                            ->label('ساعت سرویس'),

                        Infolists\Components\TextEntry::make('set_off_at')
                            ->label('زمان حرکت تکنسین')
                            ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i') : '-')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('arrived_at')
                            ->label('زمان رسیدن')
                            ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i') : '-')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('started_at')
                            ->label('زمان شروع')
                            ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i') : '-')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('finished_at')
                            ->label('زمان پایان')
                            ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i') : '-')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('توضیحات')
                    ->schema([
                        Infolists\Components\TextEntry::make('des')
                            ->label('توضیحات کاربر')
                            ->default('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('technician_in_place_description')
                            ->label('توضیحات تکنسین پس از انجام سفارش در محل کاربر')
                            ->default('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('admin_in_place_description')
                            ->label('توضیحات مدیریت پس از انجام سفارش در محل کاربر')
                            ->default('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('user_in_place_description')
                            ->label('توضیحات کاربر پس از انجام سفارش در محل کاربر')
                            ->default('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('technician_des')
                            ->label('توضیحات سفارش پس از بررسی')
                            ->default('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('تصویر سفارش')
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_path')
                            ->label('')
                            ->size(400),
                    ])
                    ->visible(fn(Order $record) => $record->image_path != null)
                    ->collapsible(),

                Infolists\Components\Section::make('سایر اطلاعات')
                    ->schema([


                        Infolists\Components\TextEntry::make('emergency_help')
                            ->label('درخواست کمک اضطراری')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state)
                            ->color(fn($record) => $record->emergency_help_at ? 'danger' : 'gray')
                            ->weight(fn($record) => $record->emergency_help_at ? 'bold' : 'normal')
                            ->size(fn($record) => $record->emergency_help_at ? 'lg' : 'md'),


                        Infolists\Components\TextEntry::make('female_count')
                            ->label(' تکنسین درخواستی ')
                            ->badge()
                            ->formatStateUsing(fn($record) => $record->female_count > 0 ? 'خانم' : 'آقا'),




                        Infolists\Components\TextEntry::make('created_at')
                            ->label('زمان ثبت')
                            ->formatStateUsing(fn($state) => $state ? \Morilog\Jalali\Jalalian::forge($state)->format('Y/m/d H:i') : '-'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderGalleriesRelationManager::class,
            RelationManagers\ExtraServicesRelationManager::class,
            RelationManagers\TechnicianReviewsRelationManager::class,
            RelationManagers\TechnicianOrderReportRelationManager::class,
            RelationManagers\DeliveryReportRelationManager::class,
            RelationManagers\UserChatsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrderCustom::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'details.field',
            'details.fieldDetail',
            'user_address',
        ]);
    }


}
