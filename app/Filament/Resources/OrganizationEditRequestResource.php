<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationEditRequestResource\Pages;
use App\Models\OrganizationEditRequest;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * صف «درخواست ویرایش» فیلدهای قفل حساب سازمانی/شرکتی (با نامه‌ی مهر و امضادار).
 * با تأیید، فیلدهای انتخاب‌شده برای کاربر یک بار باز می‌شوند و پس از ذخیره دوباره قفل می‌شوند.
 */
class OrganizationEditRequestResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = OrganizationEditRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'درخواست‌های ویرایش حساب سازمانی';

    protected static ?string $modelLabel = 'درخواست ویرایش حساب سازمانی';

    protected static ?string $pluralModelLabel = 'درخواست‌های ویرایش حساب سازمانی';

    protected static function getViewPermission(): string
    {
        return 'view-organization-edit-requests';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-organization-edit-requests';
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
        $count = OrganizationEditRequest::where('status', OrganizationEditRequest::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        $fieldLabels = fn ($state) => collect((array) $state)->map(fn ($f) => OrganizationEditRequest::LOCKED_FIELDS[$f] ?? $f)->implode('، ');

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('شناسه')->sortable(),
                Tables\Columns\TextColumn::make('organization.organization_name')->label('سازمان/شرکت')->searchable(),
                Tables\Columns\TextColumn::make('user.phone')->label('موبایل حساب')->searchable(),
                Tables\Columns\TextColumn::make('description')->label('توضیح تغییرات')->limit(60)->tooltip(fn ($record) => $record->description)->wrap(),
                Tables\Columns\TextColumn::make('fields')->label('فیلدهای درخواستی')->formatStateUsing($fieldLabels)->placeholder('همه')->wrap(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(fn ($state) => OrganizationEditRequest::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        OrganizationEditRequest::STATUS_APPROVED => 'success',
                        OrganizationEditRequest::STATUS_REJECTED => 'danger',
                        OrganizationEditRequest::STATUS_USED => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')->label('بررسی‌کننده')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('admin_note')->label('توضیح پنل')->placeholder('-')->limit(40)->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(OrganizationEditRequest::STATUSES),
            ])
            ->actions([
                Tables\Actions\Action::make('letter')
                    ->label('مشاهده نامه')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (OrganizationEditRequest $record) => $record->letterUrl())
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('approve')
                    ->label('تأیید و باز کردن فیلدها')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (OrganizationEditRequest $record) => $record->status === OrganizationEditRequest::STATUS_PENDING && static::canEdit($record))
                    ->form([
                        Forms\Components\CheckboxList::make('unlocked_fields')
                            ->label('فیلدهایی که یک بار قابل ویرایش می‌شوند')
                            ->options(OrganizationEditRequest::LOCKED_FIELDS)
                            ->default(fn (OrganizationEditRequest $record) => $record->fields ?: array_keys(OrganizationEditRequest::LOCKED_FIELDS))
                            ->columns(2)
                            ->required(),
                        Forms\Components\Textarea::make('admin_note')->label('توضیح برای کاربر (اختیاری)')->rows(2),
                    ])
                    ->action(function (OrganizationEditRequest $record, array $data) {
                        $all = array_keys(OrganizationEditRequest::LOCKED_FIELDS);
                        $selected = array_values(array_intersect($all, $data['unlocked_fields'] ?? []));

                        $record->update([
                            'status' => OrganizationEditRequest::STATUS_APPROVED,
                            // همه‌ی فیلدها انتخاب شده‌اند ← null (یعنی همه)
                            'unlocked_fields' => count($selected) === count($all) ? null : $selected,
                            'admin_note' => $data['admin_note'] ?? null,
                            'reviewed_by' => auth('admin')->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('درخواست تأیید شد؛ فیلدها برای کاربر باز شدند.')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (OrganizationEditRequest $record) => in_array($record->status, [OrganizationEditRequest::STATUS_PENDING, OrganizationEditRequest::STATUS_APPROVED], true) && static::canEdit($record))
                    ->form([Forms\Components\Textarea::make('admin_note')->label('دلیل رد')->required()->rows(2)])
                    ->action(function (OrganizationEditRequest $record, array $data) {
                        $record->update([
                            'status' => OrganizationEditRequest::STATUS_REJECTED,
                            'unlocked_fields' => null,
                            'admin_note' => $data['admin_note'],
                            'reviewed_by' => auth('admin')->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('درخواست رد شد.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationEditRequests::route('/'),
        ];
    }
}
