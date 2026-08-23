<?php

namespace App\Filament\Resources\TechnicianResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'پیام‌های پشتیبانی';

    protected static ?string $label = 'پیام';

    protected static ?string $pluralLabel = 'پیام‌ها';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->label('پیام')
                    ->required()
                    ->rows(5)
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Select::make('role')
                    ->label('فرستنده')
                    ->options([
                        'technician' => 'تکنسین',
                        'admin' => 'ادمین',
                    ])
                    ->default('admin')
                    ->required()
                    ->disabled(fn ($record) => $record !== null)
                    ->helperText('فقط در ایجاد پیام جدید قابل تغییر است'),

                Forms\Components\Toggle::make('is_read')
                    ->label('خوانده شده')
                    ->default(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('پیام')
                    ->limit(50)
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->message),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('فرستنده')
                    ->colors([
                        'primary' => 'technician',
                        'success' => 'admin',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'technician',
                        'heroicon-o-shield-check' => 'admin',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'technician' => 'تکنسین',
                        'admin' => 'ادمین',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('خوانده شده')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ارسال')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('فرستنده')
                    ->options([
                        'technician' => 'تکنسین',
                        'admin' => 'ادمین',
                    ]),

                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('وضعیت خواندن')
                    ->placeholder('همه')
                    ->trueLabel('خوانده شده')
                    ->falseLabel('خوانده نشده'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('پیام جدید')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert toggle to integer
                        $data['is_read'] = $data['is_read'] ?? false ? 1 : 0;
                        $data['role'] = 'admin'; // Admin is sending
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده'),

                Tables\Actions\EditAction::make()
                    ->label('ویرایش')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert toggle to integer
                        if (isset($data['is_read'])) {
                            $data['is_read'] = $data['is_read'] ? 1 : 0;
                        }
                        return $data;
                    }),

                Tables\Actions\Action::make('mark_as_read')
                    ->label('علامت به عنوان خوانده شده')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_read === 0)
                    ->action(fn ($record) => $record->markAsRead())
                    ->requiresConfirmation(),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب شده‌ها'),
                ]),
            ])
            ->emptyStateHeading('هیچ پیامی وجود ندارد')
            ->emptyStateDescription('پیام‌های پشتیبانی اینجا نمایش داده می‌شوند')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}
