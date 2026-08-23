<?php

namespace App\Filament\Resources\TechnicianResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Models\TechnicianTransaction;

class SettlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlements';

    protected static ?string $title = 'تسویه‌حساب‌ها';
    protected static ?string $modelLabel = 'تسویه';
    protected static ?string $pluralModelLabel = 'تسویه‌حساب‌ها';

    public function getTableDescription(): ?string
    {
        $technician = $this->getOwnerRecord();
        $totalSettlements = $technician->settlements()->sum('amount');
        
        return sprintf(
            '💰 مجموع کل تسویه‌های ثبت شده: **%s تومان** | موجودی فعلی کیف پول: **%s تومان**',
            number_format($totalSettlements),
            number_format($technician->wallet)
        );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('مبلغ (تومان)')
                    ->required()
                    ->numeric()
                    ->prefix('تومان')
                    ->helperText('مبلغی که از کیف پول تکنسین کسر و تسویه می‌شود')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $technician = $this->getOwnerRecord();
                        if ($state > $technician->wallet) {
                            Notification::make()
                                ->warning()
                                ->title('توجه!')
                                ->body("موجودی کیف پول تکنسین: " . number_format($technician->wallet) . " تومان")
                                ->send();
                        }
                    }),

                Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('توضیحات مربوط به تسویه...'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ (تومان)')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('مجموع کل تسویه‌ها')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => number_format($state) . ' تومان'),
                    ]),

                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->placeholder('بدون توضیحات'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ تسویه')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('تسویه جدید')
                    ->modalHeading('ثبت تسویه جدید')
                    ->modalDescription('مبلغ تسویه از کیف پول تکنسین کسر و تراکنش ثبت می‌شود.')
                    ->modalButton('ثبت تسویه')
                    ->successNotificationTitle('تسویه با موفقیت ثبت شد')
                    ->before(function (array $data) {
                        $technician = $this->getOwnerRecord();
                        
                        // بررسی موجودی کیف پول
                        if ($data['amount'] > $technician->wallet) {
                            Notification::make()
                                ->danger()
                                ->title('خطا!')
                                ->body('موجودی کیف پول تکنسین کافی نیست.' . "\n" . 
                                      'موجودی فعلی: ' . number_format($technician->wallet) . ' تومان' . "\n" .
                                      'مبلغ درخواستی: ' . number_format($data['amount']) . ' تومان')
                                ->persistent()
                                ->send();
                            
                            $this->halt();
                        }
                    })
                    ->using(function (array $data, RelationManager $livewire): \Illuminate\Database\Eloquent\Model {
                        $technician = $livewire->getOwnerRecord();
                        
                        return DB::transaction(function () use ($data, $technician) {
                            // ایجاد تسویه
                            $settlement = $technician->settlements()->create([
                                'amount' => $data['amount'],
                                'description' => $data['description'] ?? null,
                            ]);

                            // کسر مبلغ از کیف پول تکنسین
                            $technician->decrement('wallet', $data['amount']);

                            // ثبت تراکنش
                            TechnicianTransaction::create([
                                'technician_id' => $technician->id,
                                'order_id' => null,
                                'price' => -1 * $data['amount'], // منفی چون برداشت است
                                'commission' => 0,
                                'referenceId' => 'SETTLEMENT-' . $settlement->id . '-' . time(),
                                'type' => 2, // 2: برداشت
                                'status' => 100, // 100: موفق
                                'description' => 'تسویه حساب' . ($data['description'] ? ': ' . $data['description'] : ''),
                            ]);

                            return $settlement;
                        });
                    })
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('تسویه با موفقیت انجام شد')
                            ->body('مبلغ از کیف پول کسر و تراکنش ثبت شد.')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده'),
                    
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('حذف تسویه‌های انتخاب شده')
                        ->modalDescription('توجه: با حذف تسویه‌ها، مبلغ به کیف پول بازگردانده نمی‌شود.')
                        ->modalButton('بله، حذف شوند'),
                ]),
            ])
            ->emptyStateHeading('هیچ تسویه‌ای ثبت نشده')
            ->emptyStateDescription('برای ثبت تسویه جدید از دکمه بالا استفاده کنید.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
