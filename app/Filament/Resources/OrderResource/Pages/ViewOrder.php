<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('print_label')
                ->label('چاپ لیبل')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('admin.orders.label', ['order' => $this->record->getKey()]))
                ->openUrlInNewTab(),
        ];
    }

    // protected function resolveRecord($key): Model
    // {
    //     return static::getResource()::resolveRecordRouteBinding($key)
    //         ->load([
    //             'details.field',
    //             'details.fieldDetail',
    //             'user_address',
    //         ]);
    // }
}
