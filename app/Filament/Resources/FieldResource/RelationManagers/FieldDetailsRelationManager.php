<?php

namespace App\Filament\Resources\FieldResource\RelationManagers;

use App\Models\ChartOption;
use App\Models\Field;
use Filament\Forms\Components\DateTimePicker;
use Livewire\Component as Livewire;
use App\Models\FieldChart;
use App\Models\FieldDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;


class FieldDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'field_details';
    protected static ?string $title = 'گزینه';
    protected static ?string $modelLabel = 'گزینه';
    protected static ?string $pluralModelLabel = 'گزینه‌ها';

    public function form(Form $form): Form
    {
        $type = $this->getOwnerRecord()->type; // گرفتن type از مدل Field
        $is_package = $this->getOwnerRecord()->is_package; // گرفتن type از مدل Field

        return $form->schema([

            Forms\Components\Select::make('field_id')
                ->label('فیلد اصلی')
                ->preload()
                ->searchable()
                ->options(function () {
                    return Field::where('type', $this->getOwnerRecord()->type)->get()->map(function ($field) {

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
                ->helperText('در صورت انتخاب، این آیتم به عنوان زیرمجموعه فیلد دیگر شناخته می‌شود.')
                ->hiddenOn('create'),

            $is_package == 1
            ? Forms\Components\Textarea::make('title')
                ->columnSpanFull()
                ->label('آیتم های پکیج')
                ->required()
            : Forms\Components\TextInput::make('title')
                ->columnSpanFull()
                ->label('عنوان')
                ->required(),
            Forms\Components\FileUpload::make('image_path')
                ->columnSpanFull()
                ->image()
                ->label('تصویر')
            ,
            DateTimePicker::make('end_at')->jalali()->visible($is_package == 1)->label('زمان پایان تخفیف'),

            Forms\Components\TextInput::make('second_title')
                ->columnSpanFull()
                ->visible(in_array($type, ['input']))
                ->label('عنوان دوم'),

            Forms\Components\Toggle::make('is_required')
                ->columnSpanFull()
                ->label('اجباری است؟')
                ->visible(in_array($type, ['input'])),

            Forms\Components\FileUpload::make('image_path')
                ->columnSpanFull()
                ->label('تصویر')
                ->image()
                ->visible(in_array($type, ['image'])),

            Forms\Components\TextInput::make('price')
                ->columnSpanFull()
                ->label($is_package == 1 ? 'هزینه پکیج' : 'هزینه آیتم')
                ->suffix(' تومان ')
                ->visible(in_array($type, ['counter', 'checkbox', 'radioButton']))
                ->numeric()
                ->reactive()
                ->rules(function (\Filament\Forms\Get $get) {
                    return $get('show_price') ? ['required', 'numeric'] : ['nullable', 'numeric'];
                })
                ->validationMessages([
                    'required' => 'لطفا هزینه ی این آیتم را وارد نمایید.',
                    'numeric' => 'هزینه آیتم باید به صورت عددی وارد شود.',
                ]),
            Forms\Components\TextInput::make('precentage')
                ->columnSpanFull()
                ->label('درصد تخفیفی (صرفا برای نمایش می باشد)')
                ->visible($is_package == 1)
                ->numeric()
                ->reactive()
                ->required()
                ->validationMessages([
                    'required' => 'لطفا درصد تخفیف این پکیج را وارد کنید'
                ]),

            Forms\Components\Toggle::make('show_price')
                ->columnSpanFull()
                ->visible(in_array($type, ['counter', 'checkbox', 'radioButton']))
                ->label('قیمت نمایش داده شود')
                ->reactive(),
            Forms\Components\Toggle::make('affect_on_price')
                ->columnSpanFull()
                ->visible(in_array($type, ['counter', 'checkbox', 'radioButton']))
                ->label('آیا در قیمت تأثیری دارد؟')
                ->default(true)
                ->reactive(),

            // Forms\Components\TextInput::make('price')
            //     ->columnSpanFull()
            //     ->label('هزینه آیتم')
            //     ->suffix(' تومان ')
            //     ->visible(in_array($type, ['counter', 'checkbox', 'radioButton']))
            //     ->numeric()
            //     ->requiredIf('show_price', true)
            //     ->reactive(), 

            // Forms\Components\Toggle::make('show_price')
            //     ->columnSpanFull()
            //     ->visible(in_array($type, ['counter', 'checkbox', 'radioButton']))
            //     ->label('قیمت نمایش داده شود')
            //     ->reactive(),

            Forms\Components\Toggle::make('is_checked')
                ->columnSpanFull()
                ->visible(in_array($type, ['checkbox']))
                ->label(' این آیتم بصورت پیش‌فرض انتخاب شده باشد'),

            Forms\Components\Toggle::make('has_counter')
                ->columnSpanFull()
                ->visible(in_array($type, ['checkbox', 'radioButton']))
                ->label('این آیتم دارای شمارنده باشد'),

            // Forms\Components\TextInput::make('min')
            //     ->columnSpanFull()
            //     ->label('حداقل کاراکتر مورد پذیرش')
            //     ->visible(in_array($type, ['input'])),

            // Forms\Components\TextInput::make('max')
            //     ->columnSpanFull()
            //     ->label('حداکثر کاراکتر مورد پذیرش')
            //     ->visible(in_array($type, ['input'])),

            Forms\Components\FileUpload::make('icon_name')
                ->columnSpanFull()
                ->image()
                ->visible(in_array($type, ['input', 'description', 'input']))
                ->label('آیکن'),

            Forms\Components\TextInput::make('guide')
                ->columnSpanFull()
                ->visible(in_array($type, ['counter', 'checkbox', 'radioButton', 'description', 'input']))
                ->label('راهنمای ادمین'),

            Forms\Components\Textarea::make('des')
                ->columnSpanFull()
                ->visible(fn() => in_array($type, ['description', 'input', 'checkbox', 'radioButton', 'counter']))
                ->label('توضیحات (جهت نمایش به کاربر)'),


        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('شناسه')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('عنوان')->limit(40)->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data, Livewire $livewire): array {
                        $field = Field::find($livewire->ownerRecord->id);
                        if ($field->type == 'input' || $field->type == 'counter') {
                            $data['has_counter'] = 1;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                // Tables\Actions\Action::make('field_charts.chart_options')
                //     ->label('افزودن جدول')
                //     ->icon('heroicon-o-chart-bar')
                //     ->modalHeading('افزودن جدول جدید')
                //     ->form([
                //         Forms\Components\Section::make('اطلاعات جدول')
                //             ->schema([
                //                 Forms\Components\TextInput::make('title')
                //                     ->label('عنوان جدول')
                //                     ->required(),
                //                 Forms\Components\TextInput::make('columns_count')
                //                     ->label('تعداد ستون‌ها')
                //                     ->minValue(2)
                //                     ->maxValue(3)
                //                     ->numeric()
                //                     ->required(),
                //                 Forms\Components\TextInput::make('first_column')
                //                     ->label('عنوان ستون اول')
                //                     ->required(),
                //                 Forms\Components\TextInput::make('second_column')
                //                     ->label('عنوان ستون دوم')
                //                     ->required(),
                //                 Forms\Components\TextInput::make('third_column')
                //                     ->label('عنوان ستون سوم (در صورت وجود)'),
                //             ])
                //             ->columns(2),

                //         Forms\Components\Repeater::make('data_points')
                //             ->label('سطر‌های جدول')
                //             ->schema([
                //                 Forms\Components\TextInput::make('first')
                //                     ->label('مقدار ستون اول')
                //                     ->required(),

                //                 Forms\Components\TextInput::make('second')
                //                     ->label('مقدار ستون دوم')
                //                     ->required(),

                //                 Forms\Components\TextInput::make('third')
                //                     ->label('مقدار ستون سوم (در صورت وجود)'),
                //             ])
                //             ->columns(3)
                //             ->addActionLabel('افزودن سطر')
                //             ->default([]),

                //     ])
                //     ->color('success')
                //     ->action(function (array $data, $record): void {
                //         // dd( $livewire->ownerRecord->id);
                //         $fieldChart = FieldChart::create([
                //             'field_detail_id' => $record->id,
                //             'title' => $data['title'],
                //             'columns_count' => $data['columns_count'],
                //             'first_column' => $data['first_column'],
                //             'second_column' => $data['second_column'],
                //             'third_column' => $data['third_column'],
                //         ]);

                //         if ($fieldChart->id) {
                //             foreach ($data['data_points'] as $point) {
                //                 ChartOption::create([
                //                     'field_chart_id' => $fieldChart->id,
                //                     'first' => $point['first'],
                //                     'second' => $point['second'],
                //                     'third' => $point['third'] ?? null,
                //                 ]);
                //             }
                //         }
                //     })->visible(fn($record) => $record->field->type === 'description'),
                Tables\Actions\Action::make('duplicate')
                    ->label('کپی')
                    ->tooltip('ایجاد یک کپی از این سطر')
                    ->action(function (FieldDetail $record) {
                        $newRecord = $record->replicate();
                        $newRecord->title = $record->title . '(کپی شده) ';
                        $newRecord->save();


                        Notification::make()
                            ->title('رکورد با موفقیت کپی شد')
                            ->success()
                            ->send();
                    })
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning'),


                Tables\Actions\Action::make('edit_charts')
                    ->label('ویرایش جدول‌ها')
                    ->color('warning') // رنگ زرد
                    ->icon('heroicon-o-pencil-square') // آیکن ویرایش اختیاری
                    ->url(fn($record) => url("/dashboard/field-details/{$record->id}/edit"))
                    ->visible(fn($record) => $record->field->type === 'description'),

                Tables\Actions\EditAction::make(),
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
        return $ownerRecord->type != 'image';
    }
}
