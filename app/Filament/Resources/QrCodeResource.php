<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QrCodeResource\Pages;
use App\Filament\Resources\QrCodeResource\RelationManagers;
use App\Models\QrCode;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QrCodeResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = QrCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'تولید QR Code';

    protected static ?string $modelLabel = 'QR Code';

    protected static ?string $pluralModelLabel = 'QR Code ها';

    protected static ?string $navigationGroup = 'ابزارها';

    protected static ?int $navigationSort = 10;

    protected static function getViewPermission(): string
    {
        return 'view-qr-codes';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-qr-codes';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-qr-codes';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-qr-codes';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات اصلی')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('مثال: QR کد وبسایت')
                            ->helperText('یک عنوان توصیفی برای QR Code وارد کنید'),

                        Forms\Components\Select::make('type')
                            ->label('نوع محتوا')
                            ->options([
                                'text' => 'متن ساده',
                                'url' => 'لینک وبسایت',
                                'email' => 'ایمیل',
                                'phone' => 'شماره تلفن',
                            ])
                            ->default('text')
                            ->required()
                            ->live()
                            ->helperText('نوع محتوای QR Code را انتخاب کنید'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('محتوای QR Code')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('محتوا')
                            ->required()
                            ->rows(4)
                            ->placeholder(fn($get) => match ($get('type')) {
                                'url' => 'https://example.com',
                                'email' => 'info@example.com',
                                'phone' => '+989123456789',
                                'sms' => '+989123456789:متن پیام',
                                'wifi' => 'SSID:MyWiFi;PASSWORD:12345678;TYPE:WPA',
                                'vcard' => 'BEGIN:VCARD\nFN:John Doe\nTEL:+989123456789\nEND:VCARD',
                                default => 'متن دلخواه خود را وارد کنید'
                            })
                            ->helperText(fn($get) => match ($get('type')) {
                                'url' => 'آدرس کامل وبسایت را وارد کنید (شامل https://)',
                                'email' => 'آدرس ایمیل را وارد کنید',
                                'phone' => 'شماره تلفن را با کد کشور وارد کنید (+98...)',
                                'sms' => 'فرمت: شماره تلفن:متن پیام',
                                'wifi' => 'فرمت: SSID:نام شبکه;PASSWORD:رمز عبور;TYPE:نوع امنیت (WPA/WEP/nopass)',
                                'vcard' => 'فرمت vCard 3.0 را وارد کنید',
                                default => 'متن یا داده‌ای که می‌خواهید در QR Code قرار گیرد'
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('تنظیمات QR Code')
                    ->schema([
                        Forms\Components\TextInput::make('size')
                            ->label('اندازه (پیکسل)')
                            ->required()
                            ->numeric()
                            ->default(300)
                            ->minValue(100)
                            ->maxValue(1000)
                            ->suffix('px')
                            ->helperText('اندازه تصویر QR Code (100 تا 1000 پیکسل)'),

                        Forms\Components\Select::make('format')
                            ->label('فرمت خروجی')
                            ->options([
                                'png' => 'PNG (توصیه می‌شود)',
                                'svg' => 'SVG (وکتور)',
                            ])
                            ->default('png')
                            ->required()
                            ->helperText('فرمت فایل خروجی را انتخاب کنید'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('file_path')
                    ->label('QR Code')
                    ->disk('public')
                    ->size(60)
                    ->defaultImageUrl(url('/assets/images/qr-placeholder.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'text' => 'متن',
                        'url' => 'لینک',
                        'email' => 'ایمیل',
                        'phone' => 'تلفن',
                        'sms' => 'پیامک',
                        'wifi' => 'WiFi',
                        'vcard' => 'کارت ویزیت',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'text',
                        'success' => 'url',
                        'info' => 'email',
                        'warning' => 'phone',
                        'danger' => 'sms',
                        'secondary' => ['wifi', 'vcard'],
                    ]),

                Tables\Columns\TextColumn::make('content')
                    ->label('محتوا')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->content)
                    ->searchable(),

                Tables\Columns\TextColumn::make('size')
                    ->label('اندازه')
                    ->formatStateUsing(fn($state) => $state . ' px')
                    ->sortable(),

                Tables\Columns\TextColumn::make('format')
                    ->label('فرمت')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'text' => 'متن ساده',
                        'url' => 'لینک وبسایت',
                        'email' => 'ایمیل',
                        'phone' => 'شماره تلفن',
                        'sms' => 'پیامک',
                        'wifi' => 'WiFi',
                        'vcard' => 'کارت ویزیت',
                    ]),

                Tables\Filters\SelectFilter::make('format')
                    ->label('فرمت')
                    ->options([
                        'png' => 'PNG',
                        'svg' => 'SVG',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('دانلود')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn($record) => $record->image_url)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('preview')
                    ->label('پیش‌نمایش')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('پیش‌نمایش QR Code')
                    ->modalContent(fn($record) => view('filament.qr-code-preview', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('بستن'),

                Tables\Actions\EditAction::make()
                    ->label('ویرایش'),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب شده‌ها'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQrCodes::route('/'),
            'create' => Pages\CreateQrCode::route('/create'),
            'edit' => Pages\EditQrCode::route('/{record}/edit'),
        ];
    }
}
