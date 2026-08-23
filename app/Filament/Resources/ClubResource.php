<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubResource\Pages;
use App\Filament\Resources\ClubResource\RelationManagers;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Category;
use App\Models\Club;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use App\Traits\HasFilamentPermissions;

class ClubResource extends Resource
{
    use HasFilamentPermissions;
    
    protected static ?string $model = Club::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'مدیریت طرح های تشویقی کاربران';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'طرح های تشویقی کاربران';
    protected static ?string $title = 'مدیریت طرح های تشویقی کاربران';
    protected static ?string $modelLabel = 'طرح های تشویقی کاربران';
    protected static ?string $pluralModelLabel  = 'مدیریت طرح های تشویقی کاربران';

    protected static function getViewPermission(): string
    {
        return 'view-clubs';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-clubs';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-clubs';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-clubs';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('category_id')
                    ->label('دسته‌بندی')
                    ->options(function () {
                        return Category::where('has_subcategory', 0)
                            ->pluck('title', 'id'); // ['id' => 'title']
                    })
                    ->required(),

                Forms\Components\TextInput::make('gems')
                    ->label('میزان امتیاز مورد نیاز برای دریافت این تخفیف')
                    ->numeric()
                    ->minValue(1)
                    ->default(1),

                Forms\Components\TextInput::make('count')
                    ->label('تعداد دفعات استفاده از این تخفیف به ازای هر نفر ')
                    ->numeric()
                    ->default(1),

                Forms\Components\TextInput::make('expire')
                    ->label('زمان انقضای کد (تعداد روز بعد از دریافت کد توسط کاربر)')
                    ->numeric()
                    ->required(),

                // Forms\Components\Toggle::make('is_weekly')
                // ->label('آیا هفتگی است؟')
                // ->default(false),

                Forms\Components\TextInput::make('discount_percent')
                    ->label('درصد تخفیف')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0),

                Forms\Components\DateTimePicker::make('expired_at')
                    ->required()
                    ->jalali()
                    ->label('تاریخ انقضا'),

                Forms\Components\TextInput::make('max_price')
                    ->label('ماکزیمم مبلغ (تومان)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                Forms\Components\FileUpload::make('image_path')
                    ->label('تصویر')
                    ->image()
                    ->required()
                    ->directory('yourmodel-images'),

                Forms\Components\Textarea::make('des')
                    ->label('توضیح کوتاه')
                    ->columnSpanFull()
                    ->nullable(),

                Forms\Components\Textarea::make('long_des')
                    ->label('توضیح کامل')
                    ->columnSpanFull()
                    ->nullable(),

                Forms\Components\Textarea::make('meta')
                    ->label('اطلاعات متا')
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('دسته‌بندی')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('عنوان')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('gems')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('امتیاز مورد نیاز'),

                Tables\Columns\TextColumn::make('count')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تعداد دفعات'),

                Tables\Columns\TextColumn::make('expire')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('مدت اعتبار (روز)'),

                // Tables\Columns\IconColumn::make('is_weekly')
                //     ->label('هفتگی')
                //     ->boolean(),

                Tables\Columns\TextColumn::make('discount_percent')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تخفیف')
                    ->numeric()
                    ->suffix('درصد'),

                Tables\Columns\ImageColumn::make('image_path')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تصویر')
                    ->disk('public'),
                    
                

                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تاریخ ایجاد')
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListClubs::route('/'),
            'create' => Pages\CreateClub::route('/create'),
            'edit' => Pages\EditClub::route('/{record}/edit'),
        ];
    }

    
}
