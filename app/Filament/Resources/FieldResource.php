<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FieldResource\Pages;
use App\Filament\Resources\FieldResource\RelationManagers;
use App\Filament\Resources\FieldResource\RelationManagers\FieldDetailsRelationManager;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Field;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class FieldResource extends Resource
{
    use HasFilamentPermissions;
    
    protected static ?string $model = Field::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'مدیریت دسته‌بندی و فرمساز';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'مدیریت فیلد‌ها';
    protected static ?string $title = 'فیلد';
    protected static ?string $modelLabel = 'فیلد';
    protected static ?string $pluralModelLabel  = 'فیلد‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-fields';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-fields';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-fields';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-fields';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('نوع فیلد')
                    ->options([
                        'counter' => 'فیلد شمارنده',
                        'input' => 'فیلد تایپی',
                        'checkbox' => 'فیلد انتخاب (چند گزینه ای)',
                        'radioButton' => 'فیلد انتخابی (تک گزینه ای)',
                        'image' => 'فیلد تصویری',
                        'description' => 'فیلد متنی',
                    ])
                    ->disabledOn('edit')
                    ->columnSpanFull()
                    ->reactive()
                    ->required(),

                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxlength(191)
                    ->label('عنوان'),

                Forms\Components\TextInput::make('des')
                    ->label('توضیحات (جهت نمایش به کاربر)')
                    ->visible(fn(Get $get) =>  !in_array($get('type'), ['image', 'description'])),

                Forms\Components\FileUpload::make('image_path')
                    ->image()
                    ->directory('image-field')
                    ->label('انتخاب تصویر')
                    ->visible(fn(Get $get) => $get('type') === 'image')
                    ->required(fn(Get $get) => $get('type') === 'image'),

                Forms\Components\FileUpload::make('icon_name')
                    ->label('تصویر آیکون')
                    ->image()
                    ->visible(fn(Get $get) => !in_array($get('type'), ['image', 'description'])),

                Forms\Components\TextInput::make('guide')
                    ->label('راهنمای ادمین'),

                Forms\Components\Toggle::make('is_required')
                    ->label('اجباری است؟')
                    ->visible(fn(Get $get) => in_array($get('type'), ['counter', 'checkbox', 'radioButton']))
                    ->required(fn(Get $get) => in_array($get('type'), ['counter', 'checkbox', 'radioButton'])),
                Forms\Components\Toggle::make('is_package')
                    ->label('آیا این یک پکیج است؟')
                    ->visible(fn(Get $get) => in_array($get('type'), ['counter']))
                    ->required(fn(Get $get) => in_array($get('type'), ['counter'])),
                Forms\Components\Toggle::make('has_user_descriptions')
                    ->label('آیا کاربر بتواند توضیحات وارد کند؟')
                    ->visible(fn(Get $get) => in_array($get('type'), ['counter', 'checkbox']))
                    ->required(fn(Get $get) => in_array($get('type'), ['counter', 'checkbox'])),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('عنوان')
                    ->searchable()
                    ->sortable()
                    ->placeholder('---'),
                
                TextColumn::make('guide')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('راهنما')
                    ->limit(20)
                    ->searchable()
                    ->toggleable()
                    ->placeholder('---'),
                
                TextColumn::make('type')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('نوع فیلد')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'counter' => 'شمارنده',
                        'input' => 'تایپی',
                        'checkbox' => 'چند گزینه‌ای',
                        'radioButton' => 'تک گزینه‌ای',
                        'image' => 'تصویری',
                        'description' => 'متنی',
                        default => blank($state) ? '---' : $state,
                    })
                    ->searchable()
                    ,
                
                ImageColumn::make('image_path')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تصویر')
                    ->circular()
                    ->size(40)
                    ->toggleable()
                    ->placeholder('---'),
                
                TextColumn::make('icon_name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('آیکون')
                    ->toggleable()
                    ->placeholder('---'),
                
                TextColumn::make('des')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('توضیحات')
                    ->limit(30)
                    ->placeholder('---'),
                
                TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تاریخ ساخت')
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع فیلد')
                    ->options([
                        'counter' => 'شمارنده',
                        'input' => 'تایپی',
                        'checkbox' => 'چند گزینه‌ای',
                        'radioButton' => 'تک گزینه‌ای',
                        'image' => 'تصویری',
                        'description' => 'متنی',
                    ])
                    ->placeholder('همه انواع'),
            ])
            ->actions([
                Action::make('duplicate')
                    ->label('کپی')
                    ->tooltip('ایجاد یک کپی از این سطر')
                    ->action(function (Field $record) {
                        $newRecord = $record->replicate();
                        $newRecord->title = $record->title . '(کپی شده) ';
                        $newRecord->save();

                        foreach ($record->field_details as $field_detail) {
                            $newChild = $field_detail->replicate();
                            $newChild->field_id = $newRecord->id;
                            $newChild->save();

                            foreach ($field_detail->field_charts as $field_chart) {
                                $newChild1 = $field_chart->replicate();
                                $newChild1->field_detail_id = $newChild->id;
                                $newChild1->save();

                                foreach ($field_chart->chart_options as $chart_option) {
                                    $newChild2 = $chart_option->replicate();
                                    $newChild2->field_chart_id = $newChild1->id;
                                    $newChild2->save();
                                }
                            }
                        }

                        Notification::make()
                            ->title('رکورد با موفقیت کپی شد')
                            ->success()
                            ->send();
                    })
                    ->icon(FilamentIcon::resolve('actions::copy-action') ?? 'heroicon-m-document-duplicate')
                    ->color('warning'),
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
            FieldDetailsRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFields::route('/'),
            'create' => Pages\CreateField::route('/create'),
            'edit' => Pages\EditField::route('/{record}/edit'),
        ];
    }

    
}
