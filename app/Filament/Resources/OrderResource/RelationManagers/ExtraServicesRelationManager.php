<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\ExtraService;
use App\Models\OrderExtraService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExtraServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'extra_services';

    protected static ?string $title = 'هزینه قطعات';
    protected static ?string $modelLabel = 'هزینه قطعات';
    protected static ?string $pluralModelLabel = 'هزینه قطعات';

    public function form(Form $form): Form
    {
        $order = $this->getOwnerRecord();
        $categoryId = $order->category_id;

        return $form
            ->schema([
                Forms\Components\Select::make('extra_service_id')
                    ->label('قطعه ها')
                    ->options(function () use ($categoryId) {
                        return ExtraService::whereHas('extra_service_categories', function ($query) use ($categoryId) {
                            $query->where('category_id', $categoryId);
                        })->pluck('title', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('extra_service_detail_id', null);
                        $set('title', null);
                        $set('unit_price', 0);
                        $set('brand', null);
                        $set('model', null);
                        $set('warranty', null);
                        $set('test_duration', null);
                        $set('barcode', null);
                        $set('price', 0);
                    })
                    ->helperText('فقط هزینه‌های مرتبط با دسته‌بندی سفارش نمایش داده می‌شود'),

                Forms\Components\Select::make('extra_service_detail_id')
                    ->label('گزینه ها')
                    ->options(function (Forms\Get $get) {
                        $extraServiceId = $get('extra_service_id');

                        if (!$extraServiceId) {
                            return [];
                        }

                        return \App\Models\ExtraServiceDetail::where('extra_service_id', $extraServiceId)
                            ->pluck('title', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if (!$state) {
                            return;
                        }

                        $detail = \App\Models\ExtraServiceDetail::find($state);

                        if ($detail) {
                            $number = (int) ($get('number') ?? 1);
                            $unitPrice = (int) $detail->price;

                            $set('title', $detail->title);
                            $set('unit_price', $unitPrice);
                            $set('brand', $detail->brand);
                            $set('model', $detail->model);
                            $set('warranty', $detail->warranty);
                            $set('test_duration', $detail->test_duration);
                            $set('barcode', $detail->barcode);
                            $set('price', $number * $unitPrice);
                        }
                    })
                    ->disabled(fn(Forms\Get $get) => !$get('extra_service_id'))
                    ->helperText('ابتدا قطعه را انتخاب کنید'),

                Forms\Components\TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('brand')
                    ->label('برند')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('model')
                    ->label('مدل')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('warranty')
                    ->label('گارانتی')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('test_duration')
                    ->label('مهلت تست')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('barcode')
                    ->label('بارکد')
                    ->required()
                    ->maxLength(191),

                Forms\Components\TextInput::make('number')
                    ->label('تعداد')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $number = (int) ($state ?? 0);
                        $unitPrice = (int) ($get('unit_price') ?? 0);

                        $set('price', $number * $unitPrice);
                    }),

                Forms\Components\TextInput::make('unit_price')
                    ->label('قیمت واحد (تومان)')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('تومان')
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $number = (int) ($get('number') ?? 0);
                        $unitPrice = (int) ($state ?? 0);

                        $set('price', $number * $unitPrice);
                    }),

                Forms\Components\TextInput::make('price')
                    ->label('قیمت کل')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated(false)
                    ->suffix('تومان'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('extra_service.title')
                    ->label('قطعه')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('extra_service_detail.title')
                    ->label('گزینه انتخابی')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('number')
                    ->label('تعداد')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('قیمت واحد')
                    ->numeric()
                    ->suffix('تومان')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت کل')
                    ->numeric()
                    ->suffix('تومان')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('افزودن هزینه قطعات')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['price'] = ((int) ($data['number'] ?? 0)) * ((int) ($data['unit_price'] ?? 0));

                        return $data;
                    })
                    ->using(function (array $data) {
                        $order = $this->getOwnerRecord();

                        $existing = OrderExtraService::where('order_id', $order->id)
                            ->where('extra_service_id', $data['extra_service_id'])
                            ->where('extra_service_detail_id', $data['extra_service_detail_id'])
                            ->first();

                        if ($existing) {
                            $existing->update($data);
                            return $existing;
                        }

                        return $order->extra_services()->create($data);
                    })
                    ->after(function () {
                        $this->updateOrderExtraPrice();
                        redirect(request()->header('Referer'));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['price'] = ((int) ($data['number'] ?? 0)) * ((int) ($data['unit_price'] ?? 0));

                        return $data;
                    })
                    ->after(function () {
                        $this->updateOrderExtraPrice();
                        redirect(request()->header('Referer'));
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $this->updateOrderExtraPrice();
                        redirect(request()->header('Referer'));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            $this->updateOrderExtraPrice();
                            redirect(request()->header('Referer'));
                        }),
                ]),
            ])
            ->emptyStateHeading('هیچ خدمت اضافی وجود ندارد')
            ->emptyStateDescription('برای افزودن خدمت اضافی از دکمه بالا استفاده کنید.')
            ->emptyStateIcon('heroicon-o-plus-circle');
    }

    protected function updateOrderExtraPrice(): void
    {
        $order = $this->getOwnerRecord();

        $totalExtraPrice = $order->extra_services()->sum('price');

        $order->update([
            'extra_price' => $totalExtraPrice,
        ]);

        \Filament\Notifications\Notification::make()
            ->title('قیمت خدمات اضافی به‌روزرسانی شد')
            ->body('مجموع: ' . number_format($totalExtraPrice) . ' تومان')
            ->success()
            ->send();
    }
}
