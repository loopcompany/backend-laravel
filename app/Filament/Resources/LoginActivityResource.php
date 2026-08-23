<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginActivityResource\Pages;
use App\Models\LoginActivity;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoginActivityResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = LoginActivity::class;

    protected static ?string $navigationGroup = 'گزارشات و آمار';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'فعالیت‌های ورود/خروج';
    protected static ?string $modelLabel = 'فعالیت ورود/خروج';
    protected static ?string $pluralModelLabel = 'فعالیت‌های ورود/خروج';

    protected static function getViewPermission(): string
    {
        return 'view-login-activities';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-login-activities';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-login-activities';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-login-activities';
    }

    public static function canCreate(): bool
    {
        return false; // فقط مشاهده
    }

    public static function canEdit($record): bool
    {
        return false; // فقط مشاهده
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_type')
                    ->label('نوع کاربر')
                    ->options([
                        'user' => 'کاربر',
                        'technician' => 'تکنسین',
                        'organization' => 'سازمان',
                    ])
                    ->disabled(),
                Forms\Components\TextInput::make('user_id')
                    ->label('شناسه کاربر')
                    ->disabled(),
                Forms\Components\Select::make('action')
                    ->label('نوع عملیات')
                    ->options([
                        'login' => 'ورود',
                        'logout' => 'خروج',
                        'logout_all' => 'خروج از همه دستگاه‌ها',
                    ])
                    ->disabled(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('آدرس IP')
                    ->disabled(),
                Forms\Components\Textarea::make('user_agent')
                    ->label('اطلاعات مرورگر')
                    ->disabled(),
                Forms\Components\TextInput::make('device_info')
                    ->label('نوع دستگاه')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->jalali()
                    ->label('تاریخ و زمان')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('user_type')
                    ->label('نوع کاربر')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'user' => 'کاربر',
                        'technician' => 'تکنسین',
                        'organization' => 'سازمان',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'user',
                        'success' => 'technician',
                        'warning' => 'organization',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('شناسه کاربر')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('action')
                    ->label('عملیات')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'login' => 'ورود',
                        'logout' => 'خروج',
                        'logout_all' => 'خروج از همه',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'login',
                        'danger' => 'logout',
                        'warning' => 'logout_all',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('آدرس IP')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('آدرس IP کپی شد'),
                Tables\Columns\TextColumn::make('device_info')
                    ->label('دستگاه')
                    ->default('نامشخص')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'موبایل' => 'success',
                        'تبلت' => 'warning',
                        'دسکتاپ' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ و زمان')
                    ->jalaliDateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->since()
                    ->tooltip(fn($record) => $record->created_at->format('Y-m-d H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع کاربر')
                    ->options([
                        'user' => 'کاربر',
                        'technician' => 'تکنسین',
                        'organization' => 'سازمان',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('action')
                    ->label('نوع عملیات')
                    ->options([
                        'login' => 'ورود',
                        'logout' => 'خروج',
                        'logout_all' => 'خروج از همه دستگاه‌ها',
                    ])
                    ->multiple(),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'از تاریخ: ' . $data['created_from'];
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'تا تاریخ: ' . $data['created_until'];
                        }
                        return $indicators;
                    }),
                Tables\Filters\Filter::make('today')
                    ->label('امروز')
                    ->query(fn(Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle(),
                Tables\Filters\Filter::make('this_week')
                    ->label('این هفته')
                    ->query(fn(Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب‌شده‌ها')
                        ->visible(fn() => auth('admin')->user()?->can('delete-login-activities') ?? false)
                        ->requiresConfirmation()
                        ->modalHeading('حذف فعالیت‌های ورود/خروج')
                        ->modalDescription('آیا از حذف فعالیت‌های انتخاب‌شده اطمینان دارید؟')
                        ->modalSubmitActionLabel('بله، حذف شود')
                        ->modalCancelActionLabel('انصراف'),
                ]),
            ])
            ->emptyStateHeading('فعالیتی ثبت نشده است')
            ->emptyStateDescription('هنوز هیچ فعالیت ورود یا خروجی ثبت نشده است.')
            ->emptyStateIcon('heroicon-o-arrow-right-on-rectangle')
            ->poll('30s'); // رفرش خودکار هر 30 ثانیه
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginActivities::route('/'),
            'view' => Pages\ViewLoginActivity::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
