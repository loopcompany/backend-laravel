<?php

namespace App\Filament\Resources\FieldDetailResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use App\Models\FieldChart;

class FieldChartsRelationManager extends RelationManager
{
    protected static string $relationship = 'field_charts';
    protected static ?string $title = 'جدول';
    protected static ?string $modelLabel = 'جدول';
    protected static ?string $pluralModelLabel  = 'جدول';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات جدول')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان جدول')
                            ->required(),
                        Forms\Components\TextInput::make('columns_count')
                            ->label('تعداد ستون‌ها')
                            ->minValue(2)
                            ->maxValue(3)
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('first_column')
                            ->label('عنوان ستون اول')
                            ->required(),
                        Forms\Components\TextInput::make('second_column')
                            ->label('عنوان ستون دوم')
                            ->required(),
                        Forms\Components\TextInput::make('third_column')
                            ->label('عنوان ستون سوم (در صورت وجود)'),
                    ])
                    ->columns(2),

                Forms\Components\Repeater::make('chart_options')
                    ->relationship('chart_options')
                    ->label('سطر‌های جدول')
                    ->schema([
                        Forms\Components\TextInput::make('first')
                            ->label('مقدار ستون اول')
                            ->required(),

                        Forms\Components\TextInput::make('second')
                            ->label('مقدار ستون دوم')
                            ->required(),

                        Forms\Components\TextInput::make('third')
                            ->label('مقدار ستون سوم (در صورت وجود)'),
                    ])
                    ->columns(3)
                    ->addActionLabel('افزودن سطر')
                    ->default([])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان جدول'),
                Tables\Columns\TextColumn::make('columns_count')->label('تعداد ستون‌ها'),
                Tables\Columns\TextColumn::make('first_column')->label('ستون اول'),
                Tables\Columns\TextColumn::make('second_column')->label('ستون دوم'),
                Tables\Columns\TextColumn::make('third_column')->label('ستون سوم')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('duplicate')
                    ->label('کپی')
                    ->tooltip('ایجاد یک کپی از این سطر')
                    ->action(function (FieldChart $record) {
                        $newRecord = $record->replicate();
                        $newRecord->title = $record->title . '(کپی شده) ';
                        $newRecord->save();
                        
                        foreach ($record->chart_options as $chart_options) {
                            $newChild = $chart_options->replicate();
                            $newChild->field_chart_id = $newRecord->id;
                            $newChild->save();
                        }



                        Notification::make()
                            ->title('رکورد با موفقیت کپی شد')
                            ->success()
                            ->send();
                    })
                    ->icon('heroicon-o-document-duplicate')
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
}
