<?php

namespace App\Filament\Resources\CategoryFieldResource\RelationManagers;

use App\Models\Field;
use App\Models\FieldDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;


class CategoryFieldConditionalsRelationManager extends RelationManager
{
    protected static string $relationship = 'category_field_conditionals';
    protected static ?string $title = 'مدیریت مراحل شرطی';
    protected static ?string $modelLabel = 'مراحل شرطی';
    protected static ?string $pluralModelLabel  = 'مراحل شرطی';

    // protected function getTableQuery(): Builder
    // {
    //     return parent::getTableQuery()
    //         ->when(
    //             $this->getOwnerRecord()->field_detail_id,
    //             fn(Builder $query, $fieldDetailId) => $query->where('field_detail_id', $fieldDetailId)
    //         );
    // }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('fieldDetail_id')
                    ->label('انتخاب گزینه')
                    ->options(function ($get, $set) {
                        $fieldId = $this->ownerRecord->field_id;
                        return FieldDetail::where('field_id', $fieldId)
                            ->pluck('title', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->preload()
                    ->afterStateUpdated(function ($state, callable $get, callable $set, RelationManager $livewire) {
                        $items = $get('conditionalFields');


                        $categoryFieldId = $livewire->getOwnerRecord()->id;
                        $maxStep = \App\Models\CategoryFieldConditional::where('category_field_id', $categoryFieldId)
                            ->where('field_detail_id', $state)
                            ->max('step');
                        // dd($categoryFieldId);
                        $nextStep = $maxStep ? $maxStep + 1 : 1;


                        foreach ($items as $i => $item) {
                            $items[$i]['field_detail_id'] = $state;
                            $items[$i]['step'] = $nextStep;
                        }
                        $set('conditionalFields', $items);
                    })->hiddenOn('edit'),
                Forms\Components\Repeater::make('conditionalFields')
                    ->label('فیلدهای این مرحله')
                    ->schema([
                        Forms\Components\Hidden::make('field_detail_id')
                            ->default(function (callable $get) {
                                $items = collect($get('../../conditionalFields'));
                                if ($items->count() > 0) {
                                    $first = $items->first();
                                    return $first['field_detail_id'] ?? null;
                                }
                            }),

                        Forms\Components\Select::make('field_id')
                            ->label('انتخاب فیلد')
                            ->options(
                                function () {
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
                            }
                            ) // چون ریلیشن نداری مستقیم options بده
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
                        Forms\Components\Hidden::make('type'),
                        Forms\Components\Hidden::make('step')
                            ->default(function (RelationManager $livewire, callable $get) {
                                $items = collect($get('../../conditionalFields'));
                                if ($items->count() > 1) {
                                    $first = $items->first();
                                    return $first['step'] ?? null;
                                } else {
                                    $categoryFieldId = $livewire->getOwnerRecord()->id;
                                    $maxStep = \App\Models\CategoryFieldConditional::where('category_field_id', $categoryFieldId)->max('step');
                                    return $maxStep ? $maxStep + 1 : 1;
                                }
                                
                            }),
                        Forms\Components\Hidden::make('sort')->default(1)
                        ->default(function (RelationManager $livewire, callable $get) {
                            $allItems  = collect($get('../../conditionalFields'));
                            $count = $allItems->count()-1 ;
                            $lastItem = $allItems->first();
                            // dd( $count, $lastItem);

                            if ($count > 0) {
                                return $lastItem['sort'] + $count ?? 1;
                            } else {
                                return 1;
                            }
                        }),
                    ])
                    ->default([[]])
                    ->disableItemDeletion(fn($state) => count($state) <= 1)
                    ->minItems(1)
                    ->columns(2)
                    ->columnSpanFull()
                    ->orderable()
                    ->hiddenOn('edit'),

                ///////////////////////////////////////////////////***/////////////////////////////////////////////////////////////////

                Forms\Components\Repeater::make('conditionalFieldsEdit')
                    ->label('فیلدهای این مرحله')
                    ->relationship('relatedFields')
                    ->schema([
                        Forms\Components\Select::make('field_id')
                            ->label('انتخاب فیلد')
                            ->options(Field::pluck('title', 'id')) // چون ریلیشن نداری مستقیم options بده
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
                        Forms\Components\Hidden::make('type'),
                        Forms\Components\Hidden::make('field_detail_id')
                            ->default(function (callable $get) {
                                $items = collect($get('../../conditionalFieldsEdit'));
                                if ($items->count() > 0) {
                                    $first = $items->first();
                                    return $first['field_detail_id'] ?? null;
                                }
                            }),


                        Forms\Components\Hidden::make('category_field_id')
                            ->default(function (RelationManager $livewire, callable $get) {
                                return $livewire->getOwnerRecord()->id ?? null;
                            }),
                        Forms\Components\Hidden::make('step')
                            ->default(function (RelationManager $livewire, callable $get) {
                                $items = collect($get('../../conditionalFieldsEdit'));
                                if ($items->count() > 0) {
                                    $first = $items->first();
                                    return $first['step'] ?? null;
                                } else {
                                    $categoryFieldId = $livewire->getOwnerRecord()->id;
                                    $maxStep = \App\Models\CategoryFieldConditional::where('category_field_id', $categoryFieldId)->max('step');
                                    return $maxStep ? $maxStep + 1 : 1;
                                }
                            }),
                        Forms\Components\Hidden::make('sort')
                            ->default(function (RelationManager $livewire, callable $get) {
                                $allItems  = collect($get('../../conditionalFieldsEdit'));
                                $count = $allItems->count() - 1;
                                $lastItem = $allItems->first();

                                if ($count > 1) {
                                    return $lastItem['sort'] + $count ?? 1;
                                } else {
                                    return 1;
                                }
                            }),
                    ])
                    ->default([[]])
                    ->disableItemDeletion(fn($state) => count($state) <= 1)
                    ->minItems(1)
                    ->columns(2)
                    ->columnSpanFull()
                    ->orderable()
                    ->hiddenOn('create'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $categoryFieldId = $this->getOwnerRecord()->id;

                return $query->whereIn('id', function ($sub) use ($categoryFieldId) {
                    $sub->selectRaw('MIN(id)')
                        ->from('category_field_conditionals')
                        ->where('category_field_id', $categoryFieldId)
                        ->groupBy('step', 'field_detail_id');
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('step')->label('مرحله')->sortable(),
                Tables\Columns\TextColumn::make('field_detail.title')->label('گزینه'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('field_detail_id')
                    ->label('گزینه')
                    ->options(function ($livewire) {
                        $fieldId = $livewire->ownerRecord->field_id;
                        return FieldDetail::where('field_id', $fieldId)
                            ->pluck('title', 'id');
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model, RelationManager $livewire): Model {
                        $categoryFieldId = $livewire->getOwnerRecord()->id;
                        foreach ($data['conditionalFields'] as $item) {
                            $model::create([
                                'category_field_id' => $categoryFieldId,
                                'field_id' => $item['field_id'],
                                'step' => $item['step'],
                                'sort' => $item['sort'],
                                'type' => $item['type'],
                                'field_detail_id' => $item['field_detail_id'],
                            ]);
                        }

                        return $model::latest()->first();
                    }),


            ])
            ->actions([
                Tables\Actions\EditAction::make()
                // ->before(function (Tables\Actions\EditAction $action, RelationManager $livewire, $record) {
                //     $ownerRecord = $livewire->getOwnerRecord();
                //     $relatedFields = $ownerRecord->relatedFields; // دسترسی به داده‌های رابطه
                //     dd( $record->relatedFields);
                //     foreach ($data['conditionalFields'] as $item) {
                //         $model::create([
                //             'category_field_id' => $categoryFieldId,
                //             'field_id' => $item['field_id'],
                //             'step' => $item['step'],
                //             'sort' => $item['sort'],
                //             'type' => $item['type'],
                //             'field_detail_id' => $item['field_detail_id'],
                //         ]);
                //     }

                //     // انجام تغییرات مورد نیاز
                // })
                ,
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
