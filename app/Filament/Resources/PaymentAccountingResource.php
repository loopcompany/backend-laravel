<?php

namespace App\Filament\Resources;

use App\Models\UserTransaction;
use App\Services\OrderAccountingService;
use App\Services\OrderWorkflowException;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

class PaymentAccountingResource extends Resource
{
    protected static ?string $model=UserTransaction::class;
    protected static ?string $navigationGroup='مدیریت مالی';
    protected static ?string $navigationLabel='بررسی وصول پرداخت‌ها';
    protected static ?string $modelLabel='پرداخت';
    protected static ?string $pluralModelLabel='بررسی وصول پرداخت‌ها';
    protected static ?string $navigationIcon='heroicon-o-banknotes';

    public static function canViewAny(): bool
    {
        $admin=auth('admin')->user();
        return $admin && $admin->is_active && ($admin->isSuperAdmin() || $admin->can('review-order-payments'));
    }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status',100)->where(fn ($query) =>
            $query->whereIn('type',[1,2])->orWhereIn('payment_method',['card_to_card','sheba','bank_transfer']));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('تراکنش')->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('کاربر')->searchable(),
            Tables\Columns\TextColumn::make('order_id')->label('سفارش')->placeholder('شارژ کیف پول'),
            Tables\Columns\TextColumn::make('price')->label('مبلغ (تومان)')->numeric(),
            Tables\Columns\TextColumn::make('referenceId')->label('شماره پیگیری')->searchable(),
            Tables\Columns\TextColumn::make('payment_method')->label('روش')->formatStateUsing(fn ($state) => \App\Models\Order::PAYMENT_METHODS[$state]??$state),
            Tables\Columns\TextColumn::make('accounting_status')->label('بررسی حسابداری')->badge()
                ->formatStateUsing(fn ($state) => ['pending'=>'در انتظار بررسی','approved'=>'تأیید شده','rejected'=>'رد شده'][$state]??'در انتظار بررسی')
                ->color(fn ($state) => ['approved'=>'success','rejected'=>'danger'][$state]??'warning'),
            Tables\Columns\TextColumn::make('accounting_review_note')->label('یادداشت بررسی')->wrap(),
            Tables\Columns\TextColumn::make('created_at')->label('تاریخ پرداخت')->jalaliDateTime()->sortable(),
        ])->defaultSort('created_at','desc')->filters([
            Tables\Filters\SelectFilter::make('accounting_status')->label('بررسی')->options(['pending'=>'در انتظار بررسی','approved'=>'تأیید شده','rejected'=>'رد شده'])->default('pending'),
        ])->actions([
            self::reviewAction(true),self::reviewAction(false),
        ]);
    }

    private static function reviewAction(bool $approved): Tables\Actions\Action
    {
        return Tables\Actions\Action::make($approved?'approve_payment':'reject_payment')->label($approved?'تأیید وصول وجه':'رد بررسی')
            ->color($approved?'success':'danger')->visible(fn ($record) => $record->accounting_status==='pending' && self::canViewAny())
            ->requiresConfirmation()->form([Forms\Components\Textarea::make('note')->label('یادداشت حسابداری')->required(!$approved)->maxLength(1000)])
            ->action(function ($record,array $data,Tables\Actions\Action $action) use ($approved) {
                try {
                    app(OrderAccountingService::class)->review($record,$approved,$data['note']??null,auth('admin')->user());
                } catch (OrderWorkflowException $error) {
                    \Filament\Notifications\Notification::make()->danger()->title($error->getMessage())->send();$action->halt();
                }
                \Filament\Notifications\Notification::make()->success()->title('نتیجهٔ بررسی حسابداری ثبت شد.')->send();
            });
    }

    public static function getPages(): array
    {
        return ['index'=>\App\Filament\Resources\PaymentAccountingResource\Pages\ListPaymentAccounting::route('/')];
    }
}
