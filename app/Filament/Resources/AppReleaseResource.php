<?php
namespace App\Filament\Resources;
use App\Models\AppRelease;
use Filament\{Forms, Tables};
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
class AppReleaseResource extends Resource
{
    use \App\Traits\HasFilamentPermissions;
    protected static ?string $model = AppRelease::class;
    protected static ?string $navigationGroup = 'تنظیمات';
    protected static ?string $navigationLabel = 'نسخه‌های برنامه';
    protected static ?string $modelLabel = 'نسخهٔ برنامه';
    protected static ?string $pluralModelLabel = 'نسخه‌های برنامه';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    public static function getViewPermission(): string { return 'view-app-releases'; }
    public static function getCreatePermission(): string { return 'create-app-releases'; }
    public static function getEditPermission(): string { return 'edit-app-releases'; }
    public static function getDeletePermission(): string { return 'delete-app-releases'; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('artifact')->label('فایل نصب بارگذاری‌شده در سرور')->options(fn () => app(\App\Services\AppReleaseService::class)->artifacts())->searchable()->required()->disabled(fn (?AppRelease $record) => (bool) $record?->is_published)
                ->helperText('ابتدا فایل نهایی را با فرایند انتشار در پوشهٔ دانلودها بارگذاری کنید. نسخه و SHA256 از فایل واقعی محاسبه می‌شوند.'),
            Forms\Components\TextInput::make('version_code')->label('شمارهٔ ساخت اندروید (versionCode)')->numeric()->integer()->minValue(1)->maxValue(2147483647)->disabled(fn (?AppRelease $record) => (bool) $record?->is_published),
            Forms\Components\Textarea::make('notes')->label('توضیحات بروزرسانی')->maxLength(4000),
            Forms\Components\Toggle::make('is_published')->label('انتشار و پیشنهاد بروزرسانی در برنامه‌ها'),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            Tables\Columns\TextColumn::make('app')->label('برنامه')->searchable(), Tables\Columns\TextColumn::make('version')->label('نسخه'),
            Tables\Columns\TextColumn::make('version_code')->label('ساخت'), Tables\Columns\TextColumn::make('variant')->label('سیستم'),
            Tables\Columns\IconColumn::make('is_published')->label('منتشر شده')->boolean(), Tables\Columns\TextColumn::make('artifact')->label('فایل')->searchable(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }
    public static function getPages(): array { return ['index' => AppReleaseResource\Pages\ListAppReleases::route('/'), 'create' => AppReleaseResource\Pages\CreateAppRelease::route('/create'), 'edit' => AppReleaseResource\Pages\EditAppRelease::route('/{record}/edit')]; }
}
