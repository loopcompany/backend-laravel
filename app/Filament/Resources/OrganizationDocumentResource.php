<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationDocumentResource\Pages;
use App\Models\OrganizationDocument;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * بررسی مدارکی که سازمان‌ها/شرکت‌ها از اپ بارگذاری می‌کنند.
 */
class OrganizationDocumentResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = OrganizationDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'مدارک سازمان‌ها';

    protected static ?string $modelLabel = 'مدرک سازمان';

    protected static ?string $pluralModelLabel = 'مدارک سازمان‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-user';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-user';
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
        $count = OrganizationDocument::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('شناسه')->sortable(),
                Tables\Columns\TextColumn::make('organization.organization_name')->label('سازمان/شرکت')->searchable(),
                Tables\Columns\TextColumn::make('organization.organization_code')->label('کد سازمان')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('عنوان')->default('-')->searchable(),
                Tables\Columns\TextColumn::make('original_name')->label('نام فایل')->limit(30),
                Tables\Columns\TextColumn::make('size')->label('حجم')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024) . ' KB' : '-'),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(fn ($state) => OrganizationDocument::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ بارگذاری')->jalaliDateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')->label('بررسی‌کننده')->placeholder('-')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(OrganizationDocument::STATUSES),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('مشاهده فایل')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (OrganizationDocument $record) => $record->signedUrl())
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('approve')
                    ->label('تأیید')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (OrganizationDocument $record) => $record->status !== 'approved' && static::canEdit($record))
                    ->requiresConfirmation()
                    ->action(function (OrganizationDocument $record) {
                        $record->update([
                            'status' => 'approved',
                            'rejection_reason' => null,
                            'reviewed_by' => auth('admin')->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('مدرک تأیید شد.')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (OrganizationDocument $record) => $record->status !== 'rejected' && static::canEdit($record))
                    ->form([Forms\Components\Textarea::make('reason')->label('دلیل رد')->required()->rows(2)])
                    ->action(function (OrganizationDocument $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['reason'],
                            'reviewed_by' => auth('admin')->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('مدرک رد شد.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationDocuments::route('/'),
        ];
    }
}
