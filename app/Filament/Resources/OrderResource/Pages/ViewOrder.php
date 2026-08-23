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
