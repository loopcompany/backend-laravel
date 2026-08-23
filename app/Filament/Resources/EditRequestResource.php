<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditRequestResource\Pages;
use App\Models\EditRequest;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class EditRequestResource extends Resource
{
    protected static ?string $model = EditRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'درخواست ویرایش سازمان - شرکت';

    protected static ?string $modelLabel = 'درخواست ویرایش سازمان - شرکت';

    protected static ?string $pluralModelLabel = 'درخواست‌های ویرایش سازمان - شرکت';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('وضعیت درخواست')
                ->schema([
                    Forms\Components\Placeholder::make('status_label')
                        ->label('وضعیت')
                        ->content(
                            fn (?EditRequest $record): string =>
                            static::getStatusLabel($record?->status)
                        ),

                     
                ])
                ->columns(2),

            Forms\Components\Section::make('اطلاعات کاربر')
                ->description('مقدار فعلی کاربر و مقدار درخواستی در کنار یکدیگر نمایش داده شده‌اند.')
                ->schema([
                    Fieldset::make('تصویر پروفایل')
                        ->schema([
                            Forms\Components\Placeholder::make('current_profile_photo_path')
                                ->label('تصویر فعلی')
                                ->content(
                                    fn (?EditRequest $record) =>
                                    static::renderProfileImage(
                                        $record?->user?->profile_photo_path
                                    )
                                ),

                            Forms\Components\FileUpload::make('profile_image')
                                ->label('تصویر درخواستی')
                                ->disk('public')
                                ->image()
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    static::comparisonField(
                        field: 'birth_date',
                        label: 'تاریخ تولد',
                        currentValue: fn (EditRequest $record) =>
                            $record->user?->birth_date
                    ),

                    static::comparisonField(
                        field: 'email',
                        label: 'ایمیل',
                        currentValue: fn (EditRequest $record) =>
                            $record->user?->email
                    ),

                    static::comparisonField(
                        field: 'region',
                        label: 'کد منطقه',
                        currentValue: fn (EditRequest $record) =>
                            $record->user?->region
                    ),

                    static::comparisonField(
                        field: 'postal_code',
                        label: 'کد پستی',
                        currentValue: fn (EditRequest $record) =>
                            $record->user?->postal_code
                    ),
                ]),

            Forms\Components\Section::make('اطلاعات سازمان')
                ->description('بعد از تأیید، این مقادیر در جدول organizations جایگزین می‌شوند.')
                ->schema([
                    static::comparisonField(
                        field: 'organization_name',
                        label: 'نام حقوقی شرکت',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->organization_name
                    ),

                    static::comparisonField(
                        field: 'business_name',
                        label: 'نام تجاری سازمان',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->business_name
                    ),

                    static::comparisonField(
                        field: 'manager_full_name',
                        label: 'نام و نام خانوادگی مدیر',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->manager_full_name
                    ),

                    static::comparisonField(
                        field: 'agent_name',
                        label: 'نام نماینده مدیر عامل',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->agent_name
                    ),

                    static::comparisonField(
                        field: 'agent_phone',
                        label: 'شماره موبایل نماینده مدیر عامل',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->agent_phone
                    ),

                    static::comparisonField(
                        field: 'history',
                        label: 'سابقه فعالیت نماینده',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->history
                    ),

                    static::comparisonField(
                        field: 'organization_phone',
                        label: 'تلفن ثابت سازمان',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->organization_phone
                    ),

                    static::comparisonField(
                        field: 'organization_address',
                        label: 'آدرس سازمان',
                        currentValue: fn (EditRequest $record) =>
                            $record->organization?->organization_address,
                        textarea: true
                    ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization_name')
                    ->label('نام حقوقی شرکت')
                    ->searchable(),

                Tables\Columns\TextColumn::make('business_name')
                    ->label('نام تجاری')
                    ->searchable(),

                Tables\Columns\TextColumn::make('manager_full_name')
                    ->label('نام مدیر')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable(),

                Tables\Columns\TextColumn::make('agent_phone')
                    ->label('شماره نماینده')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                        static::getStatusLabel((int) $state)
                    )
                    ->color(fn ($state): string => match ((int) $state) {
                        EditRequest::STATUS_APPROVED => 'success',
                        EditRequest::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDateTime()
                    ->label('تاریخ ایجاد')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDateTime()
                    ->label('تاریخ بررسی')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        EditRequest::STATUS_PENDING => 'در انتظار بررسی',
                        EditRequest::STATUS_APPROVED => 'تأیید شده',
                        EditRequest::STATUS_REJECTED => 'رد شده',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('بررسی درخواست')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user',
                'organization',
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditRequests::route('/'),
            'edit' => Pages\EditEditRequest::route('/{record}/edit'),
        ];
    }

    private static function comparisonField(
        string $field,
        string $label,
        Closure $currentValue,
        bool $textarea = false
    ): Fieldset {
        $requestedField = $textarea
            ? Forms\Components\Textarea::make($field)->rows(3)
            : Forms\Components\TextInput::make($field);

        return Fieldset::make($label)
            ->schema([
                Forms\Components\Placeholder::make("current_{$field}")
                    ->label('مقدار فعلی')
                    ->content(function (?EditRequest $record) use ($currentValue): string {
                        if (!$record) {
                            return '—';
                        }

                        return static::formatValue(
                            $currentValue($record)
                        );
                    }),

                $requestedField
                    ->label('مقدار درخواستی')
                    ->disabled()
                    ->dehydrated(false),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    private static function formatValue(mixed $value): string
    {
        return filled($value) ? (string) $value : '—';
    }

    private static function getStatusLabel(?int $status): string
    {
        return match ($status) {
            EditRequest::STATUS_APPROVED => 'تأیید شده',
            EditRequest::STATUS_REJECTED => 'رد شده',
            default => 'در انتظار بررسی',
        };
    }

    private static function renderProfileImage(?string $path): HtmlString|string
    {
        if (blank($path)) {
            return 'تصویری ثبت نشده است.';
        }

        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : Storage::disk('public')->url($path);

        return new HtmlString(
            '<img src="' . e($url) . '"
                  alt="تصویر فعلی پروفایل"
                  class="h-32 w-32 rounded-xl object-cover border border-gray-200">'
        );
    }
}