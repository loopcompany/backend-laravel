<?php
namespace App\Filament\RelationManagers;
use App\Models\CodeDelivery;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
class CodeDeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';
    protected static ?string $title = 'سوابق ارسال و ارتباط با کاربران';
    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')->label('گیرنده')->searchable(),
            Tables\Columns\TextColumn::make('user.code')->label('شناسه کاربری')->searchable(),
            Tables\Columns\TextColumn::make('user.account_type')->label('نوع حساب'),
            Tables\Columns\IconColumn::make('user.is_special')->label('ویژه')->boolean(),
            Tables\Columns\TextColumn::make('recipient')->label('شماره تماس'),
            Tables\Columns\TextColumn::make('category')->label('دسته‌بندی'),
            Tables\Columns\TextColumn::make('channel')->label('روش')->formatStateUsing(fn ($state)=>['sms'=>'پیامک','phone'=>'تلفنی','in_person'=>'حضوری'][$state] ?? $state),
            Tables\Columns\TextColumn::make('status')->label('نتیجه')->formatStateUsing(fn ($state)=>CodeDelivery::statuses()[$state] ?? $state),
            Tables\Columns\TextColumn::make('created_at')->label('زمان ثبت')->jalaliDateTime(),
            Tables\Columns\TextColumn::make('sent_at')->label('زمان ارسال/ارتباط')->jalaliDateTime(),
            Tables\Columns\TextColumn::make('error_code')->label('خطا'),
        ])->filters([Tables\Filters\SelectFilter::make('channel')->label('روش')->options(['sms'=>'پیامک','phone'=>'تلفنی','in_person'=>'حضوری']), Tables\Filters\SelectFilter::make('status')->label('نتیجه')->options(CodeDelivery::statuses())])->defaultSort('id','desc');
    }
}
