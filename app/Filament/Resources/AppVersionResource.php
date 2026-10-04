<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppVersionResource\Pages;
use App\Models\AppVersion;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class AppVersionResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = AppVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?string $navigationLabel = 'نسخه‌ی اپلیکیشن‌ها';

    protected static ?string $modelLabel = 'نسخه‌ی اپلیکیشن';

    protected static ?string $pluralModelLabel = 'نسخه‌ی اپلیکیشن‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-app-versions';
    }

    protected static function getCreatePermission(): string
    {
        return 'edit-app-versions';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-app-versions';
    }

    protected static function getDeletePermission(): string
    {
        return 'edit-app-versions';
    }

    public static function form(Form $form): Form
    {
        $versionRule = 'regex:/^\d+(\.\d+){0,3}$/';

        return $form->schema([
            Forms\Components\Select::make('app')
                ->label('اپلیکیشن')
                ->options(AppVersion::APPS)
                ->default('user')
                ->required(),
            Forms\Components\Select::make('platform')
                ->label('پلتفرم')
                ->options(AppVersion::PLATFORMS)
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Forms\Get $get) => $rule->where('app', $get('app')))
                ->validationMessages(['unique' => 'برای این اپ و پلتفرم قبلاً نسخه ثبت شده است.']),
            Forms\Components\TextInput::make('latest_version')
                ->label('آخرین نسخه')
                ->placeholder('2.75.0')
                ->required()
                ->rule($versionRule),
            Forms\Components\TextInput::make('min_supported_version')
                ->label('حداقل نسخه‌ی پشتیبانی‌شده')
                ->helperText('نسخه‌های قدیمی‌تر از این، بروزرسانی اجباری می‌گیرند (دکمه‌ی «بعداً» نمایش داده نمی‌شود).')
                ->placeholder('2.70.0')
                ->rule($versionRule),
            Forms\Components\TextInput::make('update_url')
                ->label('لینک بروزرسانی')
                ->url()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('release_notes')
                ->label('تغییرات این نسخه')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('app')->label('اپلیکیشن')->formatStateUsing(fn ($state) => AppVersion::APPS[$state] ?? $state),
                Tables\Columns\TextColumn::make('platform')->label('پلتفرم')->formatStateUsing(fn ($state) => AppVersion::PLATFORMS[$state] ?? $state),
                Tables\Columns\TextColumn::make('latest_version')->label('آخرین نسخه'),
                Tables\Columns\TextColumn::make('min_supported_version')->label('حداقل نسخه')->placeholder('-'),
                Tables\Columns\TextColumn::make('updated_at')->label('آخرین تغییر')->jalaliDateTime('Y/m/d H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAppVersions::route('/'),
        ];
    }
}
