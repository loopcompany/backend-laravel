<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralCodeResource\Pages;
use App\Models\ReferralCode;
use App\Models\User;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralCodeResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = ReferralCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'کدهای معرف';

    protected static ?string $modelLabel = 'کد معرف';

    protected static ?string $pluralModelLabel = 'کدهای معرف';

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
        return $form->schema([
            Forms\Components\Section::make('ساخت کد معرف')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('کاربر')
                        ->relationship('user', 'name')
                        ->getOptionLabelFromRecordUsing(function (User $record): string {
                            $fullName = trim($record->name . ' ' . $record->last_name);
                            $identity = $record->phone ?: ($record->code ?: $record->email);

                            return $identity ? "{$fullName} - {$identity}" : $fullName;
                        })
                        ->getSearchResultsUsing(function (string $search): array {
                            return User::query()
                                ->where(function ($query) use ($search): void {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhere('code', 'like', "%{$search}%")
                                        ->orWhere('referral_code', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function (User $user): array {
                                    $fullName = trim($user->name . ' ' . $user->last_name);
                                    $identity = $user->phone ?: ($user->code ?: $user->email);

                                    return [$user->getKey() => $identity ? "{$fullName} - {$identity}" : $fullName];
                                })
                                ->all();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $user = User::find($value);

                            if (!$user) {
                                return null;
                            }

                            $fullName = trim($user->name . ' ' . $user->last_name);
                            $identity = $user->phone ?: ($user->code ?: $user->email);

                            return $identity ? "{$fullName} - {$identity}" : $fullName;
                        })
                        ->searchable()
                        ->required()
                        ->helperText('جستجو با نام، شماره تلفن، کد کاربری یا کد معرف قبلی'),

                    Forms\Components\TextInput::make('code')
                        ->label('کد معرف')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit')
                        ->helperText('کد به‌صورت خودکار و خوانا تولید می‌شود.'),

                    Forms\Components\Select::make('status')
                        ->label('وضعیت')
                        ->options(ReferralCode::statuses())
                        ->default(ReferralCode::STATUS_ACTIVE)
                        ->required()
                        ->native(false)
                        ->helperText('وضعیت فقط توسط ادمین تغییر می‌کند.'),

                    Forms\Components\TextInput::make('discount_percent')
                        ->label('درصد تخفیف')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->required()
                        ->suffix('%')
                        ->helperText('این درصد از مبلغ نهایی سفارش کسر می‌شود.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('کد معرف')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('کد معرف کپی شد'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام کاربر')
                    ->formatStateUsing(fn ($state, ReferralCode $record): string => trim($state . ' ' . $record->user?->last_name))
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('شماره تلفن')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.code')
                    ->label('کد کاربری')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('تخفیف')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('وضعیت')
                    ->options(ReferralCode::statuses()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(ReferralCode::statuses()),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralCodes::route('/'),
            'create' => Pages\CreateReferralCode::route('/create'),
            'edit' => Pages\EditReferralCode::route('/{record}/edit'),
        ];
    }
}
