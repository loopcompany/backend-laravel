<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoopLearnRegisterationResource\Pages;
use App\Models\LoopLearnRegisteration;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoopLearnRegisterationResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = LoopLearnRegisteration::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'ثبت نام در کلاس های آموزشی';

    protected static ?string $modelLabel = 'ثبت نام در کلاس های آموزشی';

    protected static ?string $pluralModelLabel = 'ثبت نام در کلاس های آموزشی';
    protected static function getViewPermission(): string
    {
        return 'view-loop-learn-registeration';
    }
    protected static function getDeletePermission(): string
    {
        return 'delete-loop-learn-registeration';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('class')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('register_as')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('mastery_soft_level')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('mastery_hard_level')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('goal')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('lname')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('birth_date')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('marriage')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('gender')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('nationality')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('education')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('telephone')
                    ->tel()
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('vehicle')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('certificate')
                    ->required()
                    ->maxLength(191),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('اطلاعات ثبت‌نام')
                    ->schema([
                        TextEntry::make('class')
                            ->label('ثبت نام در کلاس های'),

                        TextEntry::make('register_as')
                            ->label('ثبت نام بعنوان'),

                        TextEntry::make('mastery_soft_level')
                            ->label('(نرم افزار) نصب ویندوزها و برنامه ها'),

                        TextEntry::make('mastery_hard_level')
                            ->label('سخت افزار (عیب یابی و رفع آن)'),

                        TextEntry::make('goal')
                            ->label('آیا پس از آموزش و تسط کامل موافق به استخدام بعنوان تکنسین میدانی هستید؟'),

                        TextEntry::make('name')
                            ->label('نام'),

                        TextEntry::make('lname')
                            ->label('نام خانوادگی'),

                        TextEntry::make('birth_date')
                            ->label('تاریخ تولد'),

                        TextEntry::make('marriage')
                            ->label('تأهل'),

                        TextEntry::make('gender')
                            ->label('جنسیت'),

                        TextEntry::make('nationality')
                            ->label('ملیت'),

                        TextEntry::make('education')
                            ->label('تحصیلات'),

                        TextEntry::make('phone')
                            ->label('شماره همراه'),

                        TextEntry::make('telephone')
                            ->label('تلفن ثابت')
                        ,

                        TextEntry::make('address')
                            ->label('آدرس محل سکونت')
                            ->columnSpanFull(),

                        TextEntry::make('vehicle')
                            ->label('دارای وسیله نقلیه:'),

                        TextEntry::make('certificate')
                            ->label('دارای گواهی نامه'),

                        TextEntry::make('created_at')
                            ->label('تاریخ ثبت')
                            ->jalaliDateTime(),

                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('class')
                    ->label('نوع کلاس')
                    ->searchable(),


                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),

                Tables\Columns\TextColumn::make('lname')
                    ->label('نام خانوادگی')
                    ->searchable(),



                Tables\Columns\TextColumn::make('phone')
                    ->label('شماره همراه')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('شماره ثابت')
                    ->searchable(),


                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDateTime()
                    ->sortable()
                    ->label('تاریخ ثبت')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListLoopLearnRegisterations::route('/'),
            'create' => Pages\CreateLoopLearnRegisteration::route('/create'),
            'view' => Pages\ViewLoopLearnRegisteration::route('/{record}'),
            'edit' => Pages\EditLoopLearnRegisteration::route('/{record}/edit'),
        ];
    }
}
