<?php

namespace App\Filament\Resources;

use App\Exceptions\AccountSecurityException;
use App\Filament\Resources\AccountDeletionRequestResource\Pages;
use App\Models\AccountDeletionRequest;
use App\Services\Security\AccountDeletionService;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * صف درخواست‌های حذف حساب. حساب‌های سازمانی/شرکتی فقط با تأیید همین‌جا حذف (ناشناس‌سازی) می‌شوند؛
 * حساب‌های عادی خودکار بعد از ۱۴ روز اجرا می‌شوند و این‌جا فقط برای پیگیری دیده می‌شوند.
 */
class AccountDeletionRequestResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = AccountDeletionRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'درخواست‌های حذف حساب';

    protected static ?string $modelLabel = 'درخواست حذف حساب';

    protected static ?string $pluralModelLabel = 'درخواست‌های حذف حساب';

    protected static function getViewPermission(): string
    {
        return 'view-account-deletion-requests';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-account-deletion-requests';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('شناسه')->sortable(),
                Tables\Columns\TextColumn::make('name_snapshot')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('phone_snapshot')->label('موبایل')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.organization.organization_name')->label('سازمان/شرکت')->default('-'),
                Tables\Columns\TextColumn::make('account_kind')
                    ->label('نوع حساب')
                    ->formatStateUsing(fn ($state) => $state === AccountDeletionRequest::KIND_ORGANIZATION ? 'سازمانی/شرکتی' : 'عادی')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn ($state) => AccountDeletionRequest::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'done' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reason')->label('دلیل')->limit(40)->tooltip(fn ($record) => $record->reason)->default('-'),
                Tables\Columns\TextColumn::make('requested_at')->label('تاریخ درخواست')->jalaliDateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')->label('تاریخ اجرای حذف')->jalaliDateTime('Y/m/d H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('executed_at')->label('تاریخ انجام')->jalaliDateTime('Y/m/d H:i')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('reviewer.name')->label('بررسی‌کننده')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('review_note')->label('توضیح بررسی')->limit(30)->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(AccountDeletionRequest::STATUSES),
                Tables\Filters\SelectFilter::make('account_kind')->label('نوع حساب')->options([
                    AccountDeletionRequest::KIND_INDIVIDUAL => 'عادی',
                    AccountDeletionRequest::KIND_ORGANIZATION => 'سازمانی/شرکتی',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('تأیید و حذف')
                    ->icon('heroicon-o-check')
                    ->color('danger')
                    ->visible(fn (AccountDeletionRequest $record) => $record->status === AccountDeletionRequest::STATUS_PENDING
                        && static::canEdit($record))
                    ->requiresConfirmation()
                    ->modalHeading('تأیید حذف حساب')
                    ->modalDescription('اطلاعات شخصی حساب ناشناس و دسترسی آن بسته می‌شود. سفارش‌ها و سوابق مالی باقی می‌مانند. این کار برگشت‌پذیر نیست.')
                    ->form([Forms\Components\Textarea::make('note')->label('توضیح (اختیاری)')->rows(2)])
                    ->action(fn (AccountDeletionRequest $record, array $data) => static::review($record, 'approve', $data['note'] ?? null)),
                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (AccountDeletionRequest $record) => $record->status === AccountDeletionRequest::STATUS_PENDING
                        && static::canEdit($record))
                    ->form([Forms\Components\Textarea::make('note')->label('دلیل رد')->required()->rows(2)])
                    ->action(fn (AccountDeletionRequest $record, array $data) => static::review($record, 'reject', $data['note'])),
            ]);
    }

    private static function review(AccountDeletionRequest $record, string $decision, ?string $note): void
    {
        $service = app(AccountDeletionService::class);
        $admin = auth('admin')->user();

        try {
            $decision === 'approve'
                ? $service->approveByAdmin($record, $admin, $note)
                : $service->rejectByAdmin($record, $admin, $note);

            Notification::make()
                ->title($decision === 'approve' ? 'حساب حذف شد.' : 'درخواست رد شد.')
                ->success()
                ->send();
        } catch (AccountSecurityException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountDeletionRequests::route('/'),
        ];
    }
}
