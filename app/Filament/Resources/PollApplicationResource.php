<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollApplicationResource\Pages;
use App\Filament\Resources\PollApplicationResource\RelationManagers;
use App\Models\PollApplication;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasFilamentPermissions;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class PollApplicationResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = PollApplication::class;

    protected static ?string $navigationGroup = 'گزارشات و تحلیل';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'نظرسنجی‌ها';
    protected static ?string $title = 'مدیریت نظرسنجی‌ها';
    protected static ?string $modelLabel = 'نظرسنجی';
    protected static ?string $pluralModelLabel = 'نظرسنجی‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-poll-applications';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-poll-applications';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-poll-applications';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-poll-applications';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات نظرسنجی')->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('کاربر'),
                        
                    Forms\Components\Select::make('app_rate')
                        ->options([
                            'خوب' => 'خوب',
                            'متوسط' => 'متوسط',
                            'ضعیف' => 'ضعیف',
                        ])
                        ->required()
                        ->label('امتیاز اپلیکیشن'),
                        
                    Forms\Components\Select::make('tech_rate')
                        ->options([
                            'خوب' => 'خوب',
                            'متوسط' => 'متوسط',
                            'ضعیف' => 'ضعیف',
                        ])
                        ->required()
                        ->label('امتیاز تکنسین'),
                        
                    Forms\Components\Select::make('support_rate')
                        ->options([
                            'خوب' => 'خوب',
                            'متوسط' => 'متوسط',
                            'ضعیف' => 'ضعیف',
                        ])
                        ->required()
                        ->label('امتیاز پشتیبانی'),
                        
                    Forms\Components\Textarea::make('description')
                        ->label('توضیحات')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('کاربر'),
                    
                Tables\Columns\TextColumn::make('user.phone')
                    ->searchable()
                    ->label('شماره تلفن'),
                    
                Tables\Columns\BadgeColumn::make('app_rate')
                    ->colors([
                        'success' => 'خوب',
                        'warning' => 'متوسط',
                        'danger' => 'ضعیف',
                    ])
                    ->label('امتیاز اپلیکیشن'),
                    
                Tables\Columns\BadgeColumn::make('tech_rate')
                    ->colors([
                        'success' => 'خوب',
                        'warning' => 'متوسط',
                        'danger' => 'ضعیف',
                    ])
                    ->label('امتیاز تکنسین'),
                    
                Tables\Columns\BadgeColumn::make('support_rate')
                    ->colors([
                        'success' => 'خوب',
                        'warning' => 'متوسط',
                        'danger' => 'ضعیف',
                    ])
                    ->label('امتیاز پشتیبانی'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->label('توضیحات')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDateTime()
                    ->sortable()
                    ->label('تاریخ ثبت'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('app_rate')
                    ->options([
                        'خوب' => 'خوب',
                        'متوسط' => 'متوسط',
                        'ضعیف' => 'ضعیف',
                    ])
                    ->label('امتیاز اپلیکیشن'),
                    
                Tables\Filters\SelectFilter::make('tech_rate')
                    ->options([
                        'خوب' => 'خوب',
                        'متوسط' => 'متوسط',
                        'ضعیف' => 'ضعیف',
                    ])
                    ->label('امتیاز تکنسین'),
                    
                Tables\Filters\SelectFilter::make('support_rate')
                    ->options([
                        'خوب' => 'خوب',
                        'متوسط' => 'متوسط',
                        'ضعیف' => 'ضعیف',
                    ])
                    ->label('امتیاز پشتیبانی'),
                    
                Tables\Filters\Filter::make('has_description')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('description'))
                    ->label('دارای توضیحات'),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('از تاریخ')
                            ->jalali(),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('تا تاریخ')
                            ->jalali(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label('بازه زمانی'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('export_excel')
                        ->label('خروجی Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // این قسمت برای export کردن نظرسنجی‌ها
                            Notification::make()
                                ->success()
                                ->title('خروجی Excel')
                                ->body('در حال آماده‌سازی فایل...')
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('statistics')
                    ->label('آمار کلی')
                    ->icon('heroicon-o-chart-pie')
                    ->color('info')
                    ->modalHeading('آمار نظرسنجی‌ها')
                    ->modalContent(function () {
                        $stats = self::getStatistics();
                        
                        return view('filament.pages.poll-statistics', compact('stats'));
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('بستن'),
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
            'index' => Pages\ListPollApplications::route('/'),
            'create' => Pages\CreatePollApplication::route('/create'),
            'view' => Pages\ViewPollApplication::route('/{record}'),
            'edit' => Pages\EditPollApplication::route('/{record}/edit'),
        ];
    }

    /**
     * Get poll statistics
     */
    public static function getStatistics(): array
    {
        $total = PollApplication::count();
        
        if ($total == 0) {
            return [
                'total_responses' => 0,
                'app_ratings' => ['خوب' => 0, 'متوسط' => 0, 'ضعیف' => 0],
                'tech_ratings' => ['خوب' => 0, 'متوسط' => 0, 'ضعیف' => 0],
                'support_ratings' => ['خوب' => 0, 'متوسط' => 0, 'ضعیف' => 0],
                'latest_responses' => [],
                'response_trend' => [],
            ];
        }

        // آمار امتیازات
        $appRatings = PollApplication::selectRaw('app_rate, COUNT(*) as count')
            ->groupBy('app_rate')
            ->pluck('count', 'app_rate')
            ->toArray();
            
        $techRatings = PollApplication::selectRaw('tech_rate, COUNT(*) as count')
            ->groupBy('tech_rate')
            ->pluck('count', 'tech_rate')
            ->toArray();
            
        $supportRatings = PollApplication::selectRaw('support_rate, COUNT(*) as count')
            ->groupBy('support_rate')
            ->pluck('count', 'support_rate')
            ->toArray();

        // آخرین پاسخ‌ها
        $latestResponses = PollApplication::with('user:id,name,phone')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($poll) {
                return [
                    'user_name' => $poll->user->name,
                    'user_phone' => $poll->user->phone,
                    'app_rate' => $poll->app_rate,
                    'tech_rate' => $poll->tech_rate,
                    'support_rate' => $poll->support_rate,
                    'created_at' => $poll->created_at->format('Y-m-d H:i'),
                ];
            });

        // روند پاسخ‌ها در 7 روز گذشته
        $responseTrend = PollApplication::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->count];
            });

        return [
            'total_responses' => $total,
            'app_ratings' => [
                'خوب' => $appRatings['خوب'] ?? 0,
                'متوسط' => $appRatings['متوسط'] ?? 0,
                'ضعیف' => $appRatings['ضعیف'] ?? 0,
            ],
            'tech_ratings' => [
                'خوب' => $techRatings['خوب'] ?? 0,
                'متوسط' => $techRatings['متوسط'] ?? 0,
                'ضعیف' => $techRatings['ضعیف'] ?? 0,
            ],
            'support_ratings' => [
                'خوب' => $supportRatings['خوب'] ?? 0,
                'متوسط' => $supportRatings['متوسط'] ?? 0,
                'ضعیف' => $supportRatings['ضعیف'] ?? 0,
            ],
            'latest_responses' => $latestResponses,
            'response_trend' => $responseTrend,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
