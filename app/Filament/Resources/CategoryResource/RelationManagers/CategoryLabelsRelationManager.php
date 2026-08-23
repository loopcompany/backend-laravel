<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use App\Models\Label;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryLabelsRelationManager extends RelationManager
{
    protected static string $relationship = 'category_labels';
    protected static ?string $title = 'برچسب ها';
    protected static ?string $modelLabel = 'برچسب ها';
    protected static ?string $pluralModelLabel  = 'برچسب ها';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make([

                    Forms\Components\Select::make('label_id')
                        ->label('برچسب')
                        ->options(
                                Label::query()
                                    ->get()
                                    ->mapWithKeys(function ($label) {
                                        return [
                                            $label->id => $label->title . ' - ' . $label->guide
                                        ];
                                    })
                            )
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('type')
                        ->label('محل نمایش برچسب')
                        ->options([
                            '1' => 'روی تصویر',
                            '0' => 'روی کارت',
                        ])
                        ->required()
                        ->rules([
                            function () {
                                return function (string $attribute, $value, $fail) {
                                    $existing = $this->getOwnerRecord()
                                        ->category_labels()
                                        ->where('type', $value)
                                        ->exists();

                                    if ($existing) {
                                        $fail('فقط یک برچسب از این نوع قابل ثبت است.');
                                    }
                                };
                            },
                        ]),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label.title')->label('برچسب'),
                Tables\Columns\TextColumn::make('type')->label('محل نمایش برچسب')
                    ->formatStateUsing(function ($state) {
                        return $state == 1 ? 'روی تصویر' : 'روی کارت';
                    }),
                Tables\Columns\TextColumn::make('created_at')->jalaliDate()->label('تاریخ ایجاد'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(function () {
                        $hasType0 = $this->getOwnerRecord()
                            ->category_labels()
                            ->where('type', 0)
                            ->exists();
                        $hasType1 = $this->getOwnerRecord()
                            ->category_labels()
                            ->where('type', 1)
                            ->exists();

                        return $hasType0 && $hasType1;
                    }),
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
}
