<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Models\CategoryField;
use App\Models\Field;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CategoryFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'category_fields';
    protected static ?string $title = 'مدیریت فرم سفارشات';
    protected static ?string $modelLabel = 'فیلد';
    protected static ?string $pluralModelLabel  = 'فرم سفارشات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('relatedFields')
                    // ->relationship('relatedFields')
                    ->label('فیلدهای این مرحله')
                    ->schema([

                        Forms\Components\Hidden::make('category_id')
                            ->default(fn(RelationManager $livewire) => $livewire->getOwnerRecord()->id),


                        Forms\Components\Select::make('field_id')
                            ->label('انتخاب فیلد')
                            ->options(function () {
                                return \App\Models\Field::all()->map(function ($field) {

                                    switch ($field->type) {
                                        case 'input':
                                            $type = 'فیلد تایپی';
                                            break;
                                        case 'radioButton':
                                            $type = 'انتخابی تک گزینه‌ای';
                                            break;
                                        case 'checkbox':
                                            $type = 'انتخابی چند گزینه‌ای';
                                            break;
                                        case 'image':
                                            $type = 'فیلد تصویری';
                                            break;
                                        case 'description':
                                            $type = 'توضیحات متنی';
                                            break;
                                        case 'counter':
                                            $type = 'فیلد شمارنده';
                                            break;

                                        default:
                                            $type = 'نامشخص';
                                            break;
                                    }
                                    return [
                                        'label' => $field->title . ' (راهنما: ' . ($field->guide ?? '-') . ' | ' . $type . ')',
                                        'value' => $field->id,
                                    ];
                                })->pluck('label', 'value');
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $field = Field::find($state);
                                if ($field) {
                                    $set('type', $field->type);
                                }
                            }),

                        Forms\Components\Hidden::make('step')
                            ->label('مرحله')
                            ->default(function (RelationManager $livewire) {
                                $categoryId = $livewire->getOwnerRecord()->id;
                                $maxStep = \App\Models\CategoryField::where('category_id', $categoryId)->max('step');
                                return $maxStep ? $maxStep + 1 : 1;
                            })
                            ->required(),



                        Forms\Components\Toggle::make('is_conditional')
                            ->label('شرطی است؟')
                            ->visible(fn(Get $get) => $get('type') === 'radioButton')
                            ->inline(false),

                        Forms\Components\Hidden::make('type'),

                        Forms\Components\Hidden::make('sort'),

                        Forms\Components\Placeholder::make('edit_link')
                            ->label('')
                            ->columnSpanFull()
                            ->content(function ($get) {
                                $id = $get('id'); // آی‌دی آیتم داخل رپیتر
                                
                                if (!$id) {
                                    return new HtmlString('<p class="text-gray-500">ابتدا رکورد را ذخیره کنید</p>');
                                }
                                
                                // Direct URL construction to avoid route issues
                                $url = url("/admin/category-fields/{$id}/edit");

                                return new HtmlString(
                                    "<a href='{$url}' target='_blank' class='inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition' style='background: #9333ea;font-family: sans-serif;'>
                                        تعیین مراحل شرطی
                                    </a>"
                                );
                            })
                            ->visible(fn(Get $get) => $get('type') === 'radioButton' && $get('is_conditional') === true)

                    ])
                    ->afterStateHydrated(function (callable $set, $state, RelationManager $livewire) {
                        $record = $livewire->getMountedTableActionRecord();

                        if (!$record) return;

                        $categoryId = $record->category_id;
                        $step = $record->step;

                        $items = \App\Models\CategoryField::where('category_id', $categoryId)
                            ->where('step', $step)
                            ->get()
                            ->map(fn($item) => $item->toArray())
                            ->toArray();

                        $set('relatedFields', $items);
                    })
                    ->default([[]])
                    ->disableItemDeletion(fn($state, $context) => count($state) <= 1)
                    ->minItems(1)
                    ->columns(2)
                    ->orderable()
                    ->columnSpanFull()
                    ->hiddenOn('create'),




                Forms\Components\Repeater::make('relatedFields')
                    ->label('فیلدهای این مرحله')
                    ->schema([
                        Forms\Components\Hidden::make('category_id')
                            ->default(fn(RelationManager $livewire) => $livewire->getOwnerRecord()->id),

                        Forms\Components\Select::make('field_id')
                            ->label('انتخاب فیلد')
                            ->options(function () {
                                return \App\Models\Field::all()->map(function ($field) {

                                    switch ($field->type) {
                                        case 'input':
                                            $type = 'فیلد تایپی';
                                            break;
                                        case 'radioButton':
                                            $type = 'انتخابی تک گزینه‌ای';
                                            break;
                                        case 'checkbox':
                                            $type = 'انتخابی چند گزینه‌ای';
                                            break;
                                        case 'image':
                                            $type = 'فیلد تصویری';
                                            break;
                                        case 'description':
                                            $type = 'توضیحات متنی';
                                            break;
                                        case 'counter':
                                            $type = 'فیلد شمارنده';
                                            break;

                                        default:
                                            $type = 'نامشخص';
                                            break;
                                    }
                                    return [
                                        'label' => $field->title . ' (راهنما: ' . ($field->guide ?? '-') . ' | ' . $type . ')',
                                        'value' => $field->id,
                                    ];
                                })->pluck('label', 'value');
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $field = Field::find($state);
                                if ($field) {
                                    $set('type', $field->type);
                                }
                            }),

                        Forms\Components\Hidden::make('step')
                            ->label('مرحله')
                            ->default(function (RelationManager $livewire) {
                                $categoryId = $livewire->getOwnerRecord()->id;
                                $maxStep = \App\Models\CategoryField::where('category_id', $categoryId)->max('step');
                                return $maxStep ? $maxStep + 1 : 1;
                            })
                            ->required(),



                        Forms\Components\Toggle::make('is_conditional')
                            ->label('شرطی است؟')
                            ->visible(fn(Get $get) => $get('type') === 'radioButton')
                            ->inline(false),

                        Forms\Components\Hidden::make('type'),

                        Forms\Components\Hidden::make('sort')->default(1),

                    ])
                    ->default([[]])
                    ->disableItemDeletion(fn($state, $context) => count($state) <= 1)
                    ->minItems(1)
                    ->columns(2)
                    ->orderable()
                    ->columnSpanFull()
                    ->hiddenOn('edit'),
            ]);
    }




    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->whereIn('id', function ($sub) {
                    $sub->select(DB::raw('MIN(id)'))
                        ->from('category_fields')
                        ->where('category_id', $this->getOwnerRecord()->id)
                        ->groupBy('step');
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('step')
                    ->label('مرحله')
                    ->sortable(), // اجازه مرتب‌سازی می‌دهد


            ])
            ->defaultSort('step')
            ->filters([
                Tables\Filters\SelectFilter::make('step')
                    ->label('مرحله')
                    ->options(
                        fn() =>
                        \App\Models\CategoryField::distinct()->pluck('step', 'step')->toArray()
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): Model {
                        foreach ($data['relatedFields'] as $fieldData) {
                            $categoryFieldData = [
                                'category_id' => $fieldData['category_id'],
                                'field_id' => $fieldData['field_id'],
                                'step' => $fieldData['step'],
                                'sort' => $fieldData['sort'],
                                'is_conditional' => $fieldData['is_conditional']??0,
                                'type' => $fieldData['type'],
                            ];

                            $model::create($categoryFieldData);
                        }

                        return $model::latest()->first();
                    }),


            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        // پاک کردن همه فیلدهای این مرحله و دسته
                        CategoryField::where('category_id', $record->category_id)
                            ->where('step', $record->step)
                            ->delete();

                        // ذخیره‌ی فیلدهای جدید
                        foreach ($data['relatedFields'] as $index => $fieldData) {
                            CategoryField::create([
                                'category_id' => $record->category_id,
                                'field_id' => $fieldData['field_id'],
                                'step' => $record->step,
                                'sort' => $index ?? 1,
                                'is_conditional' => $fieldData['is_conditional'] ?? false,
                                'type' => $fieldData['type'],
                            ]);
                        }

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }




    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->has_subcategory == 0;
    }
}
